<?php

namespace ComponentTests\Roles;

use CCR\Json;
use ComponentTests\BaseTest;
use ComponentTests\XDUserTest;
use Exception;
use TestHarness\TestFiles;
use User\Roles\CenterDirectorRole;
use XDUser;

/**
 * Tests meant to exercise the functions in the CenterDirectorRole class.
 **/
class CenterDirectorRoleTest extends BaseTest
{
    protected $testFiles;

    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../');
        }
        return $this->testFiles;
    }
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

    /**
     * @dataProvider provideGetActiveCenter
     * @param $expectedCenterId
     */
    public function testGetActiveCenter($expectedCenterId)
    {
        $user = XDUser::getUserByUserName(XDUserTest::CENTER_DIRECTOR_USER_NAME);

        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->getActiveCenter();
        $this->assertEquals($expectedCenterId, $actual);
    }

    public function provideGetActiveCenter()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'center_director_get_active_center')
        );
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

        // As the formal name for Center Director will be in the form:
        // Center Director - <CENTER>
        // The exact value will vary from system to system.
        // We can instead test that the expected value is present in the actual
        // and now the test can be run on any number of systems.
        // NOTE: this test was written before the use of artifacts.
        $this->assertNotFalse(strpos($actual, $expected), "Expected to find '$expected' in '$actual'");
    }

    /**
     * @dataProvider provideGetIdentifier
     * @param $param
     * @param $expected
     */
    public function testGetIdentifier($param, $expected)
    {

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->getIdentifier($param);
        $this->assertEquals($expected, $actual);

    }

    public function provideGetIdentifier()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'center_director_get_identifier')
        );
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
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'center_director_staff_members')
        );

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $actual = $cd->enumCenterStaffMembers();
        $this->assertEquals(count($expected), count($actual));
    }
}
