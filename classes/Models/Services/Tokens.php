<?php

namespace Models\Services;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use XDUser;

/**
 *
 */
class Tokens
{

    /**
     * Perform token authentication for the provided $userId & $token combo. If the authentication is successful, an
     * XDUser object will be returned for the provided $userId. If not, an exception will be thrown.
     *
     * @param int|string $userId        The id used to look up the the users hashed token.
     * @param string     $password The value to be checked against the retrieved hashed token.
     *
     * @return XDUser for the provided $userId, if the authentication is successful else an exception will be thrown.
     *
     * @throws Exception if unable to retrieve a database connection.
     * @throws Exception if no token can be found for the provided $userId
     * @throws Exception if the stored token for $userId has expired.
     * @throws Exception if the provided $token doesn't match the stored hash.
     */
    public static function authenticate($userId, $password)
    {
        $db = \CCR\DB::factory('database');
        $query = <<<SQL
        SELECT
            token,
            expires_on
        FROM moddb.user_tokens AS ut
            JOIN moddb.Users u ON u.id = ut.user_id
        WHERE u.id = :user_id and u.is_active = 1
SQL;

        $row = $db->query($query, array(':user_id' => $userId));

        if (count($row) === 0) {
            throw new BadRequestHttpException('Malformed token.');
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];

        // Check that expected token isn't expired.
        $now = new \DateTime();
        $expires = new \DateTime($expiresOn);
        if ($expires < $now) {
            throw new BadRequestHttpException('The API Token has expired.');
        }

        // finally check that the provided token matches it's stored hash.
        if (!password_verify($password, $expectedToken)) {
            throw new AccessDeniedException('Invalid API token.');
        }

        // and if we've made it this far we can safely return the requested Users data.
        return XDUser::getUserByID($userId);
    }

    /**
     * A helper function that takes a raw API token and deconstructs it into user_id, api_token.
     *
     * @param string $rawToken
     * @return array in the form array(user_id, token)
     * @throws Exception if the provided $rawToken is in an invalid format.
     */
    public static function parseToken($rawToken)
    {
        $delimPosition = strpos($rawToken, '.');
        if ($delimPosition === false) {
            throw new Exception('Invalid token format.');
        }

        // Check that the token is in a valid form
        $userId = substr($rawToken,0, $delimPosition);
        $token = substr($rawToken, $delimPosition + 1);

        return array($userId, $token);
    }
}
