<?php

namespace Models\Services;

use Exception;
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
    public static function authenticate($authorizationHeader)
    {
        if (0 !== strpos($authorizationHeader, Tokens::HEADER_KEY . ' ')) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                'No Token Provided.'
            );
        }
        $rawToken = substr($authorizationHeader, strlen(Tokens::HEADER_KEY) + 1);
        $delimPosition = strpos($rawToken, Tokens::DELIMITER);
        if (false === $delimPosition) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                'Invalid API token.'
            );
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

        // finally check that the provided token matches its stored hash.
        if (!password_verify($token, $expectedToken)) {
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid API token.');
        }

        // and if we've made it this far we can safely return the requested Users data.
        return XDUser::getUserByID($dbUserId);
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
        $headers = getallheaders();
        if (empty($headers['Authorization'])) {
            throw new UnauthorizedHttpException(
                Tokens::HEADER_KEY,
                'No Token Provided.'
            );
        }
        return Tokens::authenticate($headers['Authorization']);
    }
}
