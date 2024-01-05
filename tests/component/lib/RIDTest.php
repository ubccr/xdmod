<?php

namespace ComponentTests;

use XDUser;

class RIDTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider provideGenerateRID
     * @throws \Exception
     */
    public function testGenerateAndValidateRID(array $options): void
    {
        $username = $options['user'];
        $expiration = $options['expiration'];

        $user = XDUser::getUserByUserName($username);

        $rid = $user->generateRID($expiration);

        $matched = preg_match(RESTRICTION_RID, $rid);

        $this->assertEquals(1, $matched);

        if ($expiration !== null) {
            sleep($expiration + 1);
        }

        $valid = XDUser::validateRID($rid);

        $this->assertArrayHasKey('status', $valid);
        $this->assertArrayHasKey('user_id', $valid);
        $this->assertArrayHasKey('user_first_name', $valid);

        if ($expiration !== null) {
            $this->assertEquals(INVALID, $valid['status']);
            $this->assertEquals(INVALID, $valid['user_id']);
            $this->assertEquals('INVALID', $valid['user_first_name']);
        } else {
            $this->assertNotEquals(INVALID, $valid['status']);
            $this->assertNotEquals(INVALID, $valid['user_id']);
            $this->assertNotEquals('INVALID', $valid['user_first_name']);
        }
    }

    public function provideGenerateRID()
    {
        return [[['user' => 'centerdirector', 'expiration' => null]], [['user' => 'principal', 'expiration' => null]], [['user' => 'centerstaff', 'expiration' => null]], [['user' => 'normaluser', 'expiration' => null]], [['user' => 'centerdirector', 'expiration' => 1]], [['user' => 'centerstaff', 'expiration' => 1]], [['user' => 'principal', 'expiration' => 1]], [['user' => 'normaluser', 'expiration' => 1]]];
    }
}
