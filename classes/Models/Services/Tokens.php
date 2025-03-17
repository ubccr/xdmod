<?php

namespace Models\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use XDUser;

/**
 * A static helper function for authenticating using API Tokens. REST endpoints are meant to use the `authenticate`
 * function while controller functions should use `authenticateToken`.
 */
class Tokens
{
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
    public static function authenticate($userId, $password)
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

    public static function authenticateJSONWebToken($jwt)
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

        $rawToken = self::getRawToken();
        if (empty($rawToken)) {
            // we want to the token authentication to be optional so instead of throwing an exception we return null.
            // This allows us to provide token authentication to existing endpoints without impeding their normal use.
            return null;
        }

        // We expect the token to be in the form /^(\d+).(.*)$/ so just make sure it at least has the required delimiter.
        $delimPosition = strpos($rawToken, Tokens::DELIMITER);
        if ($delimPosition === false) {
            // Same as above, token authentication is optional so we return null instead of throwing an exception.
            return null;
        }

        $tokenParts = explode(Tokens::DELIMITER, $rawToken);
        $tokenPartsSize = sizeof($tokenParts);
        if ($tokenPartsSize === 2) {
            $userId = $tokenParts[0];
            $token = $tokenParts[1];
            return Tokens::authenticate($userId, $token);
        } elseif ($tokenPartsSize === 3) {
            return Tokens::authenticateJSONWebToken($rawToken);
        } else {
            return null;
            //throw new UnauthorizedHttpException(
            //    Tokens::HEADER_KEY,
            //    'Invalid token format.'
            //);
        }
    }

    /**
     * Attempt to retrieve the raw API Token from one of the following sources:
     *   - Headers
     *   - GET Parameters
     *   - POST Parameters
     *
     * @return null|string returns the api token if found else it returns null.
     */
    private static function getRawToken()
    {
        // Try to find the token in the `Authorization` header.
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            $authorizationHeader = $headers['Authorization'];
            if (is_string($authorizationHeader) && strpos($authorizationHeader, Tokens::HEADER_KEY) !== false) {
                // The format for including the token in the header is slightly different then when included as a get or
                // post parameter. Here the value will be in the form: `Bearer <token>`
                return substr(
                    $authorizationHeader,
                    strpos($authorizationHeader, Tokens::HEADER_KEY) + strlen(Tokens::HEADER_KEY) + 1
                );
            }

        }

        // If it's not in the headers, try $_GET
        if (isset($_GET[Tokens::HEADER_KEY]) && is_string($_GET[Tokens::HEADER_KEY])) {
            return $_GET[Tokens::HEADER_KEY];
        }

        if (isset($_POST[Tokens::HEADER_KEY]) && is_string($_POST[Tokens::HEADER_KEY])) {
            return $_POST[Tokens::HEADER_KEY];
        }

        return null;
    }
}
