<?php

namespace Models\Services;

use CCR\Log;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Models\Services\JsonWebToken;
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

    const MISSING_TOKEN_MESSAGE = 'No token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid token.';
    const EXPIRED_TOKEN_MESSAGE = 'Token has expired.';

    /**
     * Perform token authentication given the value of an Authorization header.
     *
     * @param string $authorizationHeader
     * @param string | null $endpoint the endpoint being requested, used only for logging.
     *
     * @return XDUser the authenticated user.
     *
     * @throws Exception                 if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    private static function authenticateAPIToken($rawToken)
    {
        $userId = $rawToken[0];
        $token = $rawToken[1];

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
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::INVALID_TOKEN_MESSAGE
            );
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];
        $dbUserId = $row[0]['user_id'];

        // Check that expected token isn't expired.
        $now = new \DateTime();
        $expires = new \DateTime($expiresOn);
        if ($expires < $now) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::EXPIRED_TOKEN_MESSAGE
            );
        }

        // finally check that the provided token matches its stored hash.
        if (!password_verify($token, $expectedToken)) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::INVALID_TOKEN_MESSAGE
            );
        }

        return XDUser::getUserByID($dbUserId);
    }

    /**
     *
     * Authenticate
     */
    private static function authenticateJSONWebToken($jwt)
    {
        $claims = JsonWebToken::decode($jwt);
        $username = $claims->sub;

        $db = \CCR\DB::factory('database');
        $query = <<<SQL
        SELECT
            username
        FROM moddb.Users
        WHERE username = :username
            AND account_is_active = 1
SQL;

        $row = $db->query($query, array(':username' => $username));
        if (count($row) !== 1) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::INVALID_TOKEN_MESSAGE
            );
        }
        $dbUsername = $row[0]['username'];
        return XDUser::getUserByUserName($dbUsername);

    }

    /**
     * This function is a stop-gap that is meant to be used to protect controller endpoints until they can be moved to
     * the new REST stack.
     *
     * @return XDUser the authenticated user.
     *
     * @throws Exception                 if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    public static function authenticateToken($request = null)
    {
        // Only necessary to support old controllers
        if (is_null($request)) {
            $request = Request::createFromGlobals();
            $headers = getallheaders();
            if (array_key_exists(Tokens::HEADER_NAME, $headers)) {
                $header = $headers[Tokens::HEADER_NAME];
                $request->headers->set(Tokens::HEADER_NAME, $header);
            }
        }

        // Check for existence of header
        if (!$request->headers->has(Tokens::HEADER_NAME)) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::MISSING_TOKEN_MESSAGE
            );
        }

        // Check for header key
        $header = $request->headers->get(Tokens::HEADER_NAME);
        $headerKey  = Tokens::HEADER_KEY . ' ';
        if (0 !== strpos($header, $headerKey)) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::INVALID_TOKEN_MESSAGE
            );
        }

        $rawToken = substr($header, strlen($headerKey));

        // Determine token type
        $tokenParts = explode(Tokens::DELIMITER, $rawToken);
        $tokenPartsSize = sizeof($tokenParts);
        if ($tokenPartsSize === 2) {
            $tokenType = 'API Token';
            $authenticatedUser = Tokens::authenticateAPIToken($rawToken);
        } elseif ($tokenPartsSize === 3) {
            $tokenType = 'JSON Web Token';
            $authenticatedUser = Tokens::authenticateJSONWebToken($rawToken);
        } else {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                Tokens::INVALID_TOKEN_MESSAGE
            );
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

        $endpoint = $request->getPathInfo();
        $logger->info(
            'User ' . $authenticatedUser->getUserName()
            . ' (' . $authenticatedUser->getUserID() . ')'
            . ' requested '
            . (!is_null($endpoint) ? $endpoint : $_SERVER['SCRIPT_NAME'])
            . ' with type ' . $tokenType
            . ' using ' . $_SERVER['HTTP_USER_AGENT']
        );

        return $authenticatedUser;
    }
}
