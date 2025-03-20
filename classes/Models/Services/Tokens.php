<?php

namespace Models\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use XDUser;

/**
 * A static helper function for authenticating using API Tokens. REST endpoints are meant to use the `authenticate`
 * function while controller functions should use `authenticateToken`.
 */
class Tokens
{
    /**
     *
     */
    const HEADER_NAME = 'Authorization';

    /**
     * This is the key that will be used when adding an API Token to a request's headers.
     */
    const HEADER_KEY = 'Bearer';

    /**
     * This is the delimiter that's used when returning a newly created API token to the user.
     */
    const DELIMITER = '.';

    /**
     * Perform token authentication for the provided $userId & $token combo. If the authentication is successful, an
     * XDUser object will be returned for the provided $userId. If not, an exception will be thrown.
     *
     * @param int|string $userId   The id used to look up the the users hashed token.
     * @param string     $password The value to be checked against the retrieved hashed token.
     *
     * @return XDUser for the provided $userId, if the authentication is successful else an exception will be thrown.
     *
     * @throws Exception                 if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if no token can be found for the provided $userId,
     *                                   if the stored token for $userId has expired, or
     *                                   if the provided $token doesn't match the stored hash.
     */
    private static function authenticateAPIToken($userId, $password)
    {
        $db = \CCR\DB::factory('database');
        $query = <<<SQL
        SELECT
            ut.user_id,
            ut.token,
            ut.expires_on
        FROM moddb.user_tokens AS ut
            JOIN moddb.Users u ON u.id = ut.user_id
        WHERE u.id = :user_id and u.account_is_active = 1
SQL;

        $row = $db->query($query, array(':user_id' => $userId));

        if (count($row) === 0) {
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid API token.');
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];
        $dbUserId = $row[0]['user_id'];

        // Check that expected token isn't expired.
        $now = new \DateTime();
        $expires = new \DateTime($expiresOn);
        if ($expires < $now) {
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'The API Token has expired.');
        }

        // finally check that the provided token matches it's stored hash.
        if (!password_verify($password, $expectedToken)) {
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid API token.');
        }

        // and if we've made it this far we can safely return the requested Users data.
        return XDUser::getUserByID($dbUserId);
    }

    /**
     *
     * Authenticate
     */
    private static function authenticateJSONWebToken($jwt)
    {
        $configuredSecretKey = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $secretKey = new Key($configuredSecretKey, 'HS256');
        $decodedToken = JWT::decode($jwt, $secretKey);

        // Cast to PHP array to get token claims
        $decodedToken = (array) $decodedToken;

        // Claims
        $issuedAtTime   = $decodedToken['iat'];
        $jwtID          = $decodedToken['jti'];
        $expiresOn      = $decodedToken['exp'];
        $username       = $decodedToken['upn'];

        $db = \CCR\DB::factory('database');
        $query = <<<SQL
        SELECT
            username
        FROM moddb.Users
        WHERE username = :username
            AND account_is_active = 1
SQL;

        $row = $db->query($query, array(':username' => $username));
        $rows = count($row);
        if ($rows === 0) {
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid JSON Web Token for user ' . $username);
        } elseif ($rows > 1) {
            throw new UnauthorizedHttpException('', 'Invalid User');
        }

        $dbUsername = $row[0]['username'];

        return XDUser::getUserByUserName($dbUsername);

    }

    /**
     * This function is a stop-gap that is meant to be used to protect controller endpoints until they can be moved to
     * the new REST stack.
     *
     * @return XDUser|null if the authentication is successful then an XDUser instance for the authenticated user will
     * be returned, if the authentication is not successful then null will be returned.
     */
    public static function authenticateToken()
    {
        $request = Request::createFromGlobals();
        $rawToken = Tokens::getRawTokenFromRequest($request);
        if (empty($rawToken)) {
            return null;
        }

        return Tokens::authenticateRawToken($rawToken);
    }

    /**
     * @param String rawToken
     * @return XDUser or null
     */
    public static function authenticateRawToken($rawToken)
    {
        $tokenParts = explode(Tokens::DELIMITER, $rawToken);
        $tokenPartsSize = sizeof($tokenParts);
        if ($tokenPartsSize === 2) {
            $userId = $tokenParts[0];
            $token = $tokenParts[1];
            return Tokens::authenticateAPIToken($userId, $token);
        } elseif ($tokenPartsSize === 3) {
            return Tokens::authenticateJSONWebToken($rawToken);
        } else {
            return null;
        }
    }

    /**
     * Attempt to retrieve the raw Token from one of the following sources:
     *   - Headers
     *   - GET Parameters
     *   - POST Parameters
     *
     * @return null|string returns the token if found else it returns null.
     */
    public static function getRawTokenFromRequest($request)
    {
        $headerName = Tokens::HEADER_NAME;
        $headerKey  = Tokens::HEADER_KEY;
        $rawToken   = null;

        if ($request->headers->has($headerName)) {
            $header = $request->headers->get($headerName);
            $rawToken = self::stripHeaderKey($header);
        } elseif ($request->query->has($headerKey)) {
            $rawToken = $request->query->get($headerKey);
        } elseif ($request->request->has($headerKey)) {
            $rawToken = $request->request->get($headerKey);
        } else {
            $allHeaders = getallheaders();
            if (array_key_exists($headerName, $allHeaders)) {
                $header = $allHeaders[$headerName];
                $rawToken = self::stripHeaderKey($header);
            } elseif (isset($_GET[$headerKey]) && is_string($_GET[$headerKey])) {
                $rawToken = $_GET[$headerKey];
            } elseif (isset($_POST[$headerKey]) && is_string($_POST[$headerKey])) {
                $rawToken = $_POST[$headerKey];
            }
        }

        return $rawToken;
    }

    private static function stripHeaderKey($header)
    {
        $headerKey = Tokens::HEADER_KEY .' ';
        $rawToken = str_replace($headerKey, '', $header);
        if ($rawToken === '')
        {
            return null;
        }
        return $rawToken;
    }
}
