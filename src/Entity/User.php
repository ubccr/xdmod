<?php
declare(strict_types=1);

namespace Access\Entity;

use DateTime;
use Models\DBObject;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface
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
     * @param string $username
     * @param array $roles
     * @param string|null $password
     * @param string|null $salt
     */
    public function __construct(string $username, array $roles, string $userId = '', string $token = '', string $password = '', string $salt = '')
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

        return array_unique($roles);
    }

    /**
     * @inheritDoc
     **/
    public function getPassword(): string
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
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @inheritDoc
     **/
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     **/
    public function getUserIdentifier(): string{
        return $this->username;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isPublicUser(): bool
    {
        return in_array('pub', $this->roles);
    }

    public static function getPublicUser(): User
    {
        return new User('Public User', ['pub']);
    }

    public static function fromXDUser(\XDUser $xdUser): User
    {
        return new User($xdUser->getUsername(), $xdUser->getRoles(), $xdUser->getUserID(), $xdUser->getToken(), $xdUser->getPassword());
    }
}