<?php namespace ComponentTests;

use Exception;
use User\Roles\CenterStaffRole;
use XDUser;

/**
 * Tests meant to exercise the functions in the CenterStaffRole class
 **/
class CenterStaffRoleTest extends \PHPUnit_Framework_TestCase
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
        $expected = XDUserTest::CENTER_STAFF_ACL_NAME . ';1';
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_STAFF_USER_NAME);
        $cs = new CenterStaffRole();
        $cs->configure($user);
        $actual = $cs->getIdentifier(true);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIdentifier()
    {
        $expected = XDUserTest::CENTER_STAFF_ACL_NAME;
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_STAFF_USER_NAME);
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
            XDUserTest::CENTER_STAFF_USER_NAME => 1,
            XDUserTest::CENTER_DIRECTOR_USER_NAME => -1,
            XDUserTest::PRINCIPAL_INVESTIGATOR_USER_NAME => -1,
            XDUserTest::NORMAL_USER_USER_NAME => -1,
            XDUserTest::PUBLIC_USER_NAME => -1
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
