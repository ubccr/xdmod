<?php
declare(strict_types=1);

namespace Access\Entity;

use Nbgrp\OneloginSamlBundle\Security\User\SamlUserInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, SamlUserInterface
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var array
     */
    protected $roles;

    protected $userId;

    protected $samlAttributes;

    protected $token;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var PasswordHasherInterface
     */
    protected $hasher;

    /**
     * @param string $username
     * @param array $roles
     * @param int $userId
     * @param string $token
     * @param string|null $password
     * @param string|null $salt
     */
    public function __construct(
        string  $username,
        array   $roles,
        int     $userId = -1,
        string  $token = '',
        ?string $password = '',
        ?string $salt = null)
    {
        $this->username = $username;
        $this->roles = $roles;
        $this->userId = $userId;
        $this->token = $token;
        $this->password = $password;
        $this->salt = $salt;
    }


    /**
     * @inheritDoc
     **/
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        if (in_array('mgr', $this->roles)) {
            $roles[] = 'ROLE_ALLOWED_TO_SWITCH';
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    /**
     * @inheritDoc
     **/
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     **/
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     **/
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function isPublicUser(): bool
    {
        return in_array('pub', $this->roles);
    }

    /**
     * @return User
     */
    public static function getPublicUser(): User
    {
        return new User('Public User', ['pub']);
    }

    /**
     * @param \XDUser $xdUser
     * @return User
     */
    public static function fromXDUser(\XDUser $xdUser): User
    {
        return new User(
            $xdUser->getUsername(),
            $xdUser->getRoles(),
            $xdUser->getUserID(),
            $xdUser->getToken(),
            $xdUser->getPassword()
        );
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function setSamlAttributes(array $attributes): void
    {
        $this->samlAttributes = $attributes;
    }
}
