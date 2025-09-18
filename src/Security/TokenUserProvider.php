<?php
declare(strict_types=1);

namespace Access\Security;

use Access\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use XDUser;

/**
 * Provides a method of retrieving users by their session token.
 */
class TokenUserProvider implements UserProviderInterface
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
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $this->logger->debug('Refreshing User', [$user]);
        try {
            return User::fromXDUser(XDUser::getUserByUserName($user->getUserIdentifier()));
        } catch (\Exception $e) {
            throw new UserNotFoundException(sprintf('No user found for username %s', $user->getUserIdentifier()), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        try {
            return User::fromXDUser( XDUser::getUserByUserName($username));
        } catch (\Exception $e) {
            throw new UserNotFoundException(sprintf('No user found for username %s', $username), $e->getCode(), $e);
        }

    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = XDUser::getUserByToken($identifier);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        return User::fromXDUser($user);
    }
}
