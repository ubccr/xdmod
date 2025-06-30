<?php

namespace Models\Services;

use CCR\DB;
use CCR\Log;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Models\Services\JsonWebToken;
use UnexpectedValueException;
use XDUser;

/**
 * A static helper class for authenticating either API tokens or JSON Web Tokens.
 * REST endpoints are meant to use the `authenticate` function while controller functions
 * should use `authenticateController`.
 */
class Tokens
{
    const MISSING_TOKEN_MESSAGE = 'No token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid token.';
    const EXPIRED_TOKEN_MESSAGE = 'Token has expired.';


    /**
     * Attempt to authenticate a user via an authentication token included in a given request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return XDUser the succesfully authenticated user.
     *
     * @throws \Exception                if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    public static function authenticate($request)
    {
        $token = null;
        // Try to extract the token from the header.
        if ($request->headers->has('Authorization')) {
            $token = self::getTokenFromHeader($request->headers->get('Authorization'));
        }
        // If the token is not in the header, then fall back to extracting from
        // the GET/POST params.
        if (empty($token)) {
            $token = $request->get('Bearer');
        }
        // If we still haven't found a token, then authentication fails.
        if (empty($token)) {
            self::throwUnauthorized(self::MISSING_TOKEN_MESSAGE);
        }
        return self::authenticateToken($token, $request->getPathInfo());
    }

    /**
     * This function is a stop-gap that is meant to be used to protect controller endpoints until they can be moved to
     * the new REST stack.
     *
     * @return XDUser the successfully authenticated user.
     *
     * @throws \Exception                if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    public static function authenticateController()
    {
        $token = null;
        // Try to extract the token from the header.
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            $token = self::getTokenFromHeader($headers['Authorization']);
        }
        // If the token is not in the header, then fall back to extracting from
        // the GET/POST params.
        if (empty($token)) {
            if (isset($_GET['Bearer']) && is_string($_GET['Bearer'])) {
                $token = $_GET['Bearer'];
            } elseif (isset($_POST['Bearer']) && is_string($_POST['Bearer'])) {
                $token = $_POST['Bearer'];
            }
        }
        // If we still haven't found a token, then authentication fails.
        if (empty($token)) {
            self::throwUnauthorized(self::MISSING_TOKEN_MESSAGE);
        }
        return self::authenticateToken($token);
    }

    /**
     * Authenticate either an API token or a JSON Web Token.
     *
     * @param string $rawToken
     * @param string | null $endpoint the endpoint being requested, used only for logging.
     *
     * @return XDUser the successfully authenticated user.
     *
     * @throws \Exception                if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    private static function authenticateToken($rawToken, $endpoint = null)
    {
        // Determine token type
        $tokenParts = explode('.', $rawToken);
        $tokenPartsSize = sizeof($tokenParts);
        if ($tokenPartsSize === 2) {
            $userId = $tokenParts[0];
            $token = $tokenParts[1];
            $tokenType = 'API token';
            $authenticatedUser = self::authenticateAPIToken($userId, $token);
        } elseif ($tokenPartsSize === 3) {
            $tokenType = 'JSON Web Token';
            $authenticatedUser = self::authenticateJSONWebToken($rawToken);
        } else {
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }

        // Log the request so we can count it in our reporting of usage of the
        // Data Analytics Framework.
        $logger = Log::factory(
            'daf',
            [
                'console' => false,
                'file' => false,
                'mail' => false
            ]
        );

        $logger->info(
            'User ' . $authenticatedUser->getUserName()
            . ' (' . $authenticatedUser->getUserID() . ')'
            . ' requested '
            . (!is_null($endpoint) ? $endpoint : $_SERVER['SCRIPT_NAME'])
            . ' with ' . $tokenType
            . ' using ' . $_SERVER['HTTP_USER_AGENT']
        );

        return $authenticatedUser;
    }

    /**
     * Authenticate a user using an API token.
     *
     * @param string $userId
     * @param string $token
     *
     * @return XDUser The successfully authenticated user.
     *
     * @throws UnauthorizedHttpException if the token is malformed, invalid, or expired
     */
    private static function authenticateAPIToken($userId, $token)
    {
        $db = DB::factory('database');
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
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];
        $dbUserId = $row[0]['user_id'];

        // Check that expected token isn't expired.
        $now = new \DateTime();
        $expires = new \DateTime($expiresOn);
        if ($expires < $now) {
            self::throwUnauthorized(self::EXPIRED_TOKEN_MESSAGE);
        }

        // finally check that the provided token matches its stored hash.
        if (!password_verify($token, $expectedToken)) {
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }

        return XDUser::getUserByID($dbUserId);
    }

    /**
     * Authenticate a user using a JSON Web Token.
     *
     * @param string $jwt
     *
     * @return XDUser The successfully authenticated user.
     *
     * @throws UnauthorizedHttpException if the token is invalid or expired
     *
     */
    private static function authenticateJSONWebToken($jwt)
    {
        try {
            $claims = JsonWebToken::decode($jwt);
        } catch (UnexpectedValueException | SignatureInvalidException $e) {
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        } catch (ExpiredException $e) {
            self::throwUnauthorized(self::EXPIRED_TOKEN_MESSAGE);
        }
        $username = $claims->sub;

        $db = DB::factory('database');
        $query = <<<SQL
        SELECT
            username
        FROM moddb.Users
        WHERE username = :username
            AND account_is_active = 1
SQL;

        $row = $db->query($query, array(':username' => $username));
        if (count($row) !== 1) {
            self:throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }
        return XDUser::getUserByUserName($username);
    }

    /**
     * Extract the bearer token from an authorization header string.
     *
     * @param string $header
     * @return string | null the token if the header has the 'Bearer' key, null otherwise.
     */
    public static function getTokenFromHeader($header)
    {
        if (0 !== strpos($header, 'Bearer ')) {
            return null;
        }
        return substr($header, strlen('Bearer') + 1);
    }

    /**
     * Throw a 401 Unauthorized exception with the given message and indicating
     * that a Bearer token should be used for authentication.
     *
     * @param string $message
     * @throws UnauthorizedHttpException
     */
    public static function throwUnauthorized($message)
    {
        throw new UnauthorizedHttpException('Bearer', $message);
    }
}
