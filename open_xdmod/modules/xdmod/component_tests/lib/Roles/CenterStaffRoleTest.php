<?php

namespace ComponentTests\Roles;

use ComponentTests\BaseTest;
use Exception;
use User\Roles\CenterStaffRole;
use XDUser;

/**
 * Tests meant to exercise the functions in the CenterStaffRole class
 **/
class CenterStaffRoleTest extends BaseTest
{
    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testGetIdentifierAbsoluteNoUser()
    {
        $cs = new CenterStaffRole();
        $cs->getIdentifier(true);
    }

    public function testGetIdentifierAbsolute()
    {
        // It is expected that when `getIdentifier` is called w/ an
        // `absolute_identifier` of true. That result will contain the center
        // that the user is associated with in the form: <acl_name>;<center>
        $expected = self::CENTER_STAFF_ACL_NAME . ';1';
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $cs = new CenterStaffRole();
        $cs->configure($user);
        $actual = $cs->getIdentifier(true);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIdentifier()
    {
        $expected = self::CENTER_STAFF_ACL_NAME;
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $cs = new CenterStaffRole();
        $cs->configure($user);
        $actual = $cs->getIdentifier();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testGetActiveCenterNoUser()
    {
        $cs = new CenterStaffRole();
        $cs->getActiveCenter();
    }

    public function testGetActiveCenter()
    {
        $users = array(
            self::CENTER_STAFF_USER_NAME => 1,
            self::CENTER_DIRECTOR_USER_NAME => -1,
            self::PRINCIPAL_INVESTIGATOR_USER_NAME => -1,
            self::NORMAL_USER_USER_NAME => -1,
            self::PUBLIC_USER_NAME => -1
        );
        foreach($users as $userName => $expected) {
            $user = XDUser::getUserByUserName($userName);
            $cs = new CenterStaffRole();
            $cs->configure($user);
            $actual = $cs->getActiveCenter();
            $this->assertEquals($expected, $actual);
        }
    }
}
