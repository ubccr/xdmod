<?php
declare(strict_types=1);

namespace CCR\Security\TokenHandlers;

use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

use CCR\DB;
use XDUser;

class JSONWebTokenHandler implements AccessTokenHandlerInterface
{
    const MISSING_TOKEN_MESSAGE = 'No token provided.';
    const INVALID_TOKEN_MESSAGE = 'Invalid token.';
    const EXPIRED_TOKEN_MESSAGE = 'Token has expired.';
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $accesToken
     *
     * @throws UnauthorizedHttpException if the token is invalid or expired
     * @throws \Exception if there is a problem decoding $jwt
     * @throws \Exception if there is a problem retrieving a connection to the database.
     * @throws \Exception if a user is not found for the provided $jwt.
     */
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        // Determine token type
        $tokenParts = explode('.', $accessToken);
        $tokenPartsSize = sizeof($tokenParts);
        if ($tokenPartsSize === 2) {
            $userId = $tokenParts[0];
            $token = $tokenParts[1];
            $tokenType = 'API token';
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
                self::throwUnauthorized(Tokens::INVALID_TOKEN_MESSAGE);
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

            $username = XDUser::getUserByID($dbUserId)->getUsername();
        } elseif ($tokenPartsSize === 3) {
            $tokenType = 'JSON Web Token';
            try {
                $claims = JsonWebToken::decode($accessToken);
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
            } else {
                self::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
            }
        }
        return new UserBadge($username);
    }

    /**
     * Throw a 401 Unauthorized exception with the given message and indicating
     * that a Bearer token should be used for authentication.
     *
     * @param string $message
     * @throws UnauthorizedHttpException
     */
    private static function throwUnauthorized(string $message)
    {
        throw new UnauthorizedHttpException('Bearer', $message);
    }
}
