<?php

declare(strict_types=1);

namespace Access\Security;

use Access\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 *
 */
class PublicUserProvider implements UserProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        return User::getPublicUser();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername(string $username)
    {
        return User::getPublicUser();
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByIdentifier(string $identifier)
    {
        return User::getPublicUser();
    }
}
