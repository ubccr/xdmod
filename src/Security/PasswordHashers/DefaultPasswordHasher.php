<?php
declare(strict_types=1);

namespace Access\Security\PasswordHashers;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class DefaultPasswordHasher implements PasswordHasherInterface
{

    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_DEFAULT);
    }
}



