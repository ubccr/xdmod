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

class JSONWebTokenAuthenticator implements AccessTokenHandlerInterface
{
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
        try {
            $claims = JsonWebToken::decode($accessToken);
        } catch (ExpiredException $e) {
            Tokens::throwUnauthorized(self::EXPIRED_TOKEN_MESSAGE);
        } catch (UnexpectedValueException | SignatureInvalidException $e) {
            Tokens::throwUnauthorized(self::INVALID_TOKEN_MESSAGE);
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

        return new UserBadge($username)
    }
}
