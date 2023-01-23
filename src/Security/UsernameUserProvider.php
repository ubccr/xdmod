<?php

namespace Access\Security;

use Access\Entity\User;
use Psr\Log\LoggerInterface;
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
    public function refreshUser(UserInterface $user)
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
    public function supportsClass(string $class)
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $this->logger->debug("Loading User By Identifier: $identifier");
        $user = XDUser::getUserByUserName($identifier);
        if (!isset($user)) {
            // Symfony code expects that an exception is thrown when loadUserByIdentifier fails.
            throw new UserNotFoundException("Unable to find User identified by $identifier");
        }
        $this->logger->debug("XDUser found by username: {$user->getUserID()} {$user->getUsername()}");
        $foundUser = User::fromXDUser($user);
        $this->logger->debug(sprintf('Final User Found:  %s %s', $foundUser->getUserIdentifier(), $foundUser->getPassword()));
        return $foundUser;
    }

    /**
     * @inerhitDoc
     */
    public function loadUserByUsername(string $username): ?UserInterface
    {
        $this->logger->debug("Loading User By Username: $username");
        return $this->loadUserByIdentifier($username);
    }

    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        $this->logger->debug('Attempting to upgrade password');
    }
}