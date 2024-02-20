<?php
declare(strict_types=1);

namespace Access\Repository;

use Access\Entity\User;
use CCR\DB;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 *
 */
class UserRepository
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DB\iDatabase
     */
    protected $db;


    /**
     * @param LoggerInterface $logger
     * @param DB\iDatabase $db
     */
    public function __construct(LoggerInterface $logger, DB\iDatabase $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * @param string $username
     * @return User
     */
    public function findByUsername(string $username): User
    {
        $usernameStatement = $this->db->prepare('SELECT u.username FROM moddb.Users u WHERE u.username = :username');
        $params =[':username' => $username];
        if (!$usernameStatement->execute($params)) {
            throw new UserNotFoundException("Unable to find a user for $username");
        }

        $rolesStatement = $this->db->prepare('SELECT a.name FROM moddb.acls a JOIN moddb.user_acls ua ON a.acl_id = ua.acl_id JOIN moddb.Users u ON ua.user_id = u.id WHERE u.username = :username');
        if (!$rolesStatement->execute($params)) {
            throw new UserNotFoundException();
        }

    }

    /**
     * @param string $token
     * @return User
     */
    public function findByToken(string $token): User
    {
        throw new UserNotFoundException("Unable to find a user for token: $token");
    }

}
