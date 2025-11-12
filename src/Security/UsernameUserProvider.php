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
     * @inheritDoc
     * @throws \Exception
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {

        $this->logger->debug("Loading User By Identifier: $identifier");
        $isSamlUser = $this->classesContains('saml', (new \Exception())->getTrace());
        try {
            $xdUser = XDUser::getUserByUserName($identifier);

            if ($isSamlUser && $xdUser->getUserType() !== SSO_USER_TYPE) {
                $this->logger->error('SSO User attempted to log in as a local user.');
                throw new InsufficientAuthenticationException();
            }
        } catch (\Exception $e) {
            $this->logger->debug("Loading User By Id instead");
            $xdUser = XDUser::getUserByID($identifier);
            if ($isSamlUser && isset($xdUser) && $xdUser->getUserType() !== SSO_USER_TYPE) {
                $this->logger->error('SSO User attempted to log in as a local user.');
                throw new InsufficientAuthenticationException();
            }
            if (!isset($xdUser)) {
                $this->logger->debug(sprintf('User %s not found', $identifier));
                throw new UserNotFoundException("Unable to find User identified by $identifier");
            }
        }

        $this->logger->debug("XDUser found by username: {$xdUser->getUserID()} {$xdUser->getUsername()}");
        $foundUser = User::fromXDUser($xdUser);
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

    /**
     * @param $classPart
     * @param $trace
     * @return bool
     */
    private function classesContains($classPart, $trace): bool
    {
        if (is_null($classPart)) {
            return false;
        }
        $classes = $this->getCallingClasses($trace);
        foreach($classes as $class) {
            if (is_null($class)) {
                continue;
            }
            $pos = strpos(strtolower($class), strtolower($classPart));
            if ($pos !== false && is_numeric($pos)) {
                return true;
            }
        }
        return false;
    }

    private function getCallingClasses($trace): array
    {
        return array_reduce(
            $trace,
            function ($carry, $item) {
                $value = array_key_exists('class', $item) ? $item['class'] : null;
                $carry[] = $value;
                return $carry;
            },
            []
        );
    }
}
