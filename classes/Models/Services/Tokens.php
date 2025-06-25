<?php

namespace Models\Services;

use CCR\Log;
use Exception;
use Symfony\Component\HttpFoundation\Request;
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
     * This is the delimiter that's used when returning a newly created API token to the user.
     */
    const DELIMITER = '.';

    const MISSING_TOKEN_MESSAGE = 'No API token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid API token.';
    const EXPIRED_TOKEN_MESSAGE = 'API token has expired.';

    /**
     * Attempt to authenticate a user via an API token included in a given request.
     *
     * @param Request $request
     *
     * @return XDUser the succesfully authenticated user.
     *
     * @throws Exception                 if unable to retrieve a database connection.
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
     * @throws Exception                 if unable to retrieve a database connection.
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
     * Perform authentication given a token.
     *
     * @param string $rawToken
     * @param string | null $endpoint the endpoint being requested, used only for logging.
     *
     * @return XDUser the successfully authenticated user.
     *
     * @throws Exception                 if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    private static function authenticateToken($rawToken, $endpoint = null)
    {
        $delimPosition = strpos($rawToken, Tokens::DELIMITER);
        if (false === $delimPosition) {
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }
        $userId = substr($rawToken, 0, $delimPosition);
        $token = substr($rawToken, $delimPosition + 1);

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
            'User '
            . $dbUserId
            . ' requested '
            . (!is_null($endpoint) ? $endpoint : $_SERVER['SCRIPT_NAME'])
            . ' with API token using '
            . $_SERVER['HTTP_USER_AGENT']
        );

        // and if we've made it this far we can safely return the requested user's data.
        return XDUser::getUserByID($dbUserId);
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
