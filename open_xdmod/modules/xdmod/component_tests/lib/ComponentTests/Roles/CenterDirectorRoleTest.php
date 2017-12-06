<?php

namespace ComponentTests\Roles;

require __DIR__ . '/../../../bootstrap.php';

use CCR\Json;
use ComponentTests\BaseTest;
use ComponentTests\XDUserTest;
use Exception;
use User\Roles\CenterDirectorRole;
use XDUser;

/**
 * Tests meant to exercise the functions in the CenterDirectorRole class.
 **/
class CenterDirectorRoleTest extends BaseTest
{
    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testGetCorrespondingUserIDWithNoUser()
    {
        $cd = new CenterDirectorRole();
        $cd->getCorrespondingUserID();
    }

    public function testGetCorrespondingUserID()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $expected = $user->getUserID();

        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->getCorrespondingUserID();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testGetActiveCenterWithNoUser()
    {
        $cd = new CenterDirectorRole();
        $cd->getActiveCenter();
    }

    public function testGetActiveCenter()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $expected = 1;

        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->getActiveCenter();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testGetFormalNameWithNoUser()
    {
        $cd = new CenterDirectorRole();
        $cd->getFormalName();
    }

    public function testGetFormalName()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $expected = 'Center Director';
        $cd = new CenterDirectorRole();
        $cd->configure($user);

        $actual =  $cd->getFormalName();
        $this->assertNotFalse(strpos($actual, $expected), "Expected to find '$expected' in '$actual'");
    }

    public function testGetIdentifier()
    {
        $params = array(
            false => XDUserTest::CENTER_DIRECTOR_ACL_NAME,
            true => XDUserTest::CENTER_DIRECTOR_ACL_NAME . ';1'
        );

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        foreach($params as $param => $expected) {
            $actual = $cd->getIdentifier($param);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()
     */
    public function testEnumStaffMembersNoUser()
    {
        $cd = new CenterDirectorRole();
        $cd->enumCenterStaffMembers();
    }

    public function testEnumStaffMembers()
    {
        $expected = Json::loadFile($this->getTestFile('center_director_staff_members.json'));

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->enumCenterStaffMembers();
        $this->assertEquals($expected, json_decode(json_encode($actual), true));
    }
}
