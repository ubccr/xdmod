<?php

namespace CCR\Security;

use CCR\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use XDUser;

/**
 *
 */
class UsernameUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $this->logger->debug('Refreshing User ' . $user->getUserIdentifier(), [$user]);
        try {
            return $user;
        } catch (\Exception $e) {
            throw new UserNotFoundException($e->getMessage());
        }

    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * @inerhitDoc
     */
    public function loadUserByUsername(string $username): ?UserInterface
    {
        $this->logger->debug("Loading User By Username: $username");
        $xdUser = XDUser::getUserByUserName($username);
        if (isset($xdUser)) {
            return User::fromXDUser($xdUser);
        } else {
            $this->logger->error('No XDUser found.');
            throw new UserNotFoundException("Unable to find User identified by $identifier");
        }
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     *
     * This method should persist the new password in the user storage and update the $user object accordingly.
     * Because you don't want your users not being able to log in, this method should be opportunistic:
     * it's fine if it does nothing or if it fails without throwing any exception.
     */
    public function upgradePassword(UserInterface|PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $this->logger->debug('Attempting to upgrade password');
    }
}
