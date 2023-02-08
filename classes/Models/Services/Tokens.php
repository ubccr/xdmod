<?php

namespace Models\Services;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use XDUser;

/**
 *
 */
class Tokens
{

    const TOKEN_FORMAT = '|^([\w=]+)\.([a-zA-Z0-9/.$]+)$|';

    /**
     * Perform token authentication for the provided $userId & $token combo. If the authentication is successful, an
     * XDUser object will be returned for the provided $userId. If not, an exception will be thrown.
     *
     * @param int|string $userId        The id used to look up the the users hashed token.
     * @param string     $unHashedToken The value to be checked against the retrieved hashed token.
     *
     * @return XDUser for the provided $userId, if the authentication is successful else an exception will be thrown.
     *
     * @throws Exception if unable to retrieve a database connection.
     * @throws Exception if no token can be found for the provided $userId
     * @throws Exception if the token stored in the database for $userId does not match the expected format.
     * @throws Exception if the provided $userId does not match the user id of the stored token.
     * @throws Exception if the stored token for $userId has expired.
     * @throws Exception if the provided $token doesn't match the stored hash.
     */
    public static function authenticate($userId, $unHashedToken)
    {
        $db = \CCR\DB::factory('database');

        $row = $db->query('SELECT token, expires_on FROM moddb.user_tokens WHERE user_id = :user_id', array(':user_id' => $userId));

        if (count($row) === 0) {
            throw new BadRequestHttpException('Malformed token.');
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];

        $match = preg_match(self::TOKEN_FORMAT, $expectedToken, $matches);
        if ($match === 0 || $match === false || count($matches) !== 3) {
            throw new HttpException(500, 'An unexpected error has occurred. Invalid stored token format.');
        }

        $encodedExpectedUserId = $matches[1];
        $expectedHash = $matches[2];

        // Check that the user ids match.
        $expectedUserId = base64_decode($encodedExpectedUserId);
        if ($expectedUserId !== $userId) {
            $msg = <<<TXT
Mismatched Users:
Matches:                  %s
Encoded Expected User Id: %s
Expected User Id:         %s
User Id:                  %s
TXT;

            throw new BadRequestHttpException(
                sprintf(
                    $msg,
                    var_export($matches, true),
                    var_export($encodedExpectedUserId, true),
                    var_export($expectedUserId, true),
                    var_export($userId, true)
                )
            );
        }

        // Check that expected token isn't expired.
        $now = new \DateTime();
        $expires = new \DateTime($expiresOn);
        if ($expires < $now) {
            throw new BadRequestHttpException('The API Token has expired.');
        }

        // finally check that the provided token matches it's stored hash.
        if (!password_verify($unHashedToken, $expectedHash)) {
            throw new AccessDeniedException('Invalid API token.');
        }

        // and if we've made it this far we can safely return the requested Users data.
        return XDUser::getUserByID($expectedUserId);
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
        // because I hate having variable declaration as a side effect of providing function arguments.
        $matches = array();

        // Check that the token is in a valid form
        $match = preg_match(Tokens::TOKEN_FORMAT, $rawToken, $matches);
        if ($match === 0 || $match === false || count($matches) !== 3) {
            throw new Exception('A valid API token must be supplied to use this endpoint.');
        }

        $userId = html_entity_decode(base64_decode($matches[1]));
        $token = $matches[2];

        return array($userId, $token);
    }
}
