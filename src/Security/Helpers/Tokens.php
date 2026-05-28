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
 * A helper class for authenticating users with API tokens and JSON Web Tokens.
 * */
class Tokens
{

    const MISSING_TOKEN_MESSAGE = 'No token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid token.';
    const EXPIRED_TOKEN_MESSAGE = 'Token has expired.';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Authenticate a user with either an API token or JSON Web Token from a Symfony request
     *
     * @return XDUser|null if the authentication is successful then an XDUser instance for the authenticated user will
     * be returned, if the authentication is not successful then null will be returned.
     *
     * @throws UnauthorizedHttpException if the token is missing, malformed, invalid, or expired.
     */
    public function authenticate(Request $request): ?XDUser
    {
        $rawToken = null;

        // Try to extract the token from the header.
        $authHeader = $request->headers->get('Authorization', '');
        $rawToken = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, strlen('Bearer ')) : $rawToken;

        // If the token is not in the header, then fall back to extracting from
        // the GET/POST params.
        if (empty($rawToken)) {
            $rawToken = $request->get('Bearer');
        }

        if (empty($rawToken)) {
            self::throwUnauthorized(self::MISSING_TOKEN_MESSAGE);
        }

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

        $endpoint = $request->getPathInfo();
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
     * @throws \Exception if unable to retrieve a database connection.
     */
    private static function authenticateAPIToken(string $userId, string $token): XDUser
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
    private static function authenticateJSONWebToken(string $jwt): XDUser
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
