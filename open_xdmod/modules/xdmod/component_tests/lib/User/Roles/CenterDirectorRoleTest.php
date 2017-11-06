<?php namespace ComponentTests;

use CCR\Json;
use Exception;
use User\Roles\CenterDirectorRole;
use XDUser;

/**
 * Tests meant to exercise the functions in the CenterDirectorRole class.
 **/
class CenterDirectorRoleTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_OUTPUT_PATH = "/../../../../tests/artifacts/xdmod-test-artifacts/xdmod/acls/output";
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
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);
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
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);
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
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);
        $expected = 'Center Director - screw';
        $cd = new CenterDirectorRole();
        $cd->configure($user);

        $actual =  $cd->getFormalName();
        $this->assertEquals($expected, $actual);
    }

    public function testGetIdentifier()
    {
        $params = array(
            false => XDUserTest::CENTER_DIRECTOR_ACL_NAME,
            true => XDUserTest::CENTER_DIRECTOR_ACL_NAME . ';1'
        );

        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);
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
        $expected = Json::loadFile(__DIR__ . self::TEST_ARTIFACT_OUTPUT_PATH . '/center_director_staff_members.json');

        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->enumCenterStaffMembers();
        $this->assertEquals($expected, json_decode(json_encode($actual), true));
    }
}
