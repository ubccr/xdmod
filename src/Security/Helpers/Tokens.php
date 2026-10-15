<?php

namespace CCR\Security\Helpers;

use CCR\DB;
use CCR\Log;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Models\Services\JsonWebToken;
use UnexpectedValueException;
use XDUser;

/**
 * A static helper function for authenticating using API Tokens. REST endpoints are meant to use the `authenticate`
 * function while controller functions should use `authenticateToken`.
 */
class Tokens
{

    const MISSING_TOKEN_MESSAGE = 'No token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid token.';
    const EXPIRED_TOKEN_MESSAGE = 'Token has expired.';

    /**
     * This is the key that will be used when adding an API Token to a request's headers.
     */
    const HEADER_KEY = 'Bearer';

    /**
     * This is the delimiter that's used when returning a newly created API token to the user.
     */
    const DELIMITER = '.';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Perform token authentication for the provided $userId & $token combo. If the authentication is successful, an
     * XDUser object will be returned for the provided $userId. If not, an exception will be thrown.
     *
     * @param int|string $userId   The id used to look up the the users hashed token.
     * @param string     $token The value to be checked against the retrieved hashed token.
     *
     * @return XDUser for the provided $userId, if the authentication is successful else an exception will be thrown.
     *
     * @throws Exception                 if unable to retrieve a database connection.
     * @throws UnauthorizedHttpException if no token can be found for the provided $userId,
     *                                   if the stored token for $userId has expired, or
     *                                   if the provided $token doesn't match the stored hash.
     */
    /*public function authenticate($userId, string $token): ?XDUser
    {
        $this->logger->info(sprintf('Beginning Authentication for %s', $userId));

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
            $this->logger->debug('User (%s) does not have an active token.');
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid API token.');
        }

        $expectedToken = $row[0]['token'];
        $expiresOn = $row[0]['expires_on'];
        $dbUserId = $row[0]['user_id'];

        // Check that expected token isn't expired.
        $now = new DateTime();
        $expires = new DateTime($expiresOn);
        if ($expires < $now) {
            $this->logger->debug(sprintf('User\'s (%s) token is expired.', $userId));
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Token has expired.', null, 0);
        }

        // finally check that the provided token matches it's stored hash.
        if (!password_verify($token, $expectedToken)) {
            $this->logger->debug(sprintf('User\'s (%s) token is invalid.', $userId));
            throw new UnauthorizedHttpException(Tokens::HEADER_KEY, 'Invalid token.');
        }

        // and if we've made it this far we can safely return the requested Users data.
        return XDUser::getUserByID($dbUserId);
    }*/

    /**
     * This function is a stop-gap that is meant to be used to protect controller endpoints until they can be moved to
     * the new REST stack.
     *
     * @return XDUser|null if the authentication is successful then an XDUser instance for the authenticated user will
     * be returned, if the authentication is not successful then null will be returned.
     *
     * @throws \Exception if there is a problem w/ authenticating the token for this request.
     */
    public function authenticate(Request $request, $strict = true): ?XDUser
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
            // if we're being strict about things, throw an exception
            if ($strict) {
                self::throwUnauthorized(self::MISSING_TOKEN_MESSAGE);
            }

            // else, this is for endpoints that have optional token authentication. By returning null we allow normal
            // authentication to continue.
            return null;
        }

        return self::authenticateToken($token, $request->getPathInfo());
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
    private static function authenticateToken(string $rawToken, string $endpoint = null): XDUser
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
     * @throws \Exception if there is an error encountered constructing the $expires DateTime.
     */
    private static function authenticateAPIToken($userId, $token): XDUser
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
     * @throws \Exception if there is a problem decoding $jwt
     * @throws \Exception if there is a problem retrieving a connection to the database.
     * @throws \Exception if a user is not found for the provided $jwt.
     */
    private static function authenticateJSONWebToken($jwt): XDUser
    {
        try {
            $claims = JsonWebToken::decode($jwt);
        } catch (ExpiredException $e) {
            self::throwUnauthorized(self::EXPIRED_TOKEN_MESSAGE);
        } catch (UnexpectedValueException | SignatureInvalidException $e) {
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
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
            self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
        }
        return XDUser::getUserByUserName($username);
    }

    /**
     * Extract the bearer token from an authorization header string.
     *
     * @param string $header
     * @return string | null the token if the header has the 'Bearer' key, null otherwise.
     */
    public static function getTokenFromHeader(string $header): ?string
    {
        if (!str_starts_with($header, 'Bearer ')) {
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
    public static function throwUnauthorized(string $message)
    {
        throw new UnauthorizedHttpException('Bearer', $message);
    }
}
