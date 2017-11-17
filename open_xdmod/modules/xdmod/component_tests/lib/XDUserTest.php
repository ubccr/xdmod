<?php

namespace ComponentTests;

use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_Error_Notice;
use ReflectionClass;
use User\Roles\CenterDirectorRole;
use \XDUser;
use Models\Services\Acls;
use \Exception;

/**
 * modify the isDeveloper function.
 * @group skip
 **/
class XDUserTest extends \PHPUnit_Framework_TestCase
{

    const TEST_ARTIFACT_OUTPUT_PATH = "/../artifacts/xdmod-test-artifacts/xdmod/acls/output";

    const PUBLIC_USER_NAME = 'Public User';
    const PUBLIC_ACL_NAME = 'pub';
    const PUBLIC_USER_EXPECTED = '/public_user.json';

    const CENTER_DIRECTOR_USER_NAME = 'centerdirector';
    const CENTER_DIRECTOR_ACL_NAME = 'cd';
    const CENTER_DIRECTOR_EXPECTED = '/center_director.json';

    const CENTER_STAFF_USER_NAME = 'centerstaff';
    const CENTER_STAFF_ACL_NAME = 'cs';
    const CENTER_STAFF_EXPECTED = '/center_staff.json';

    const PRINCIPAL_INVESTIGATOR_USER_NAME = 'principal';
    const PRINCIPAL_INVESTIGATOR_ACL_NAME = 'pi';
    const PRINCIPAL_INVESTIGATOR_EXPECTED = '/principal.json';

    const NORMAL_USER_USER_NAME = 'normaluser';
    const NORMAL_USER_ACL = 'usr';
    const NORMAL_USER_EXPECTED = '/normal_user.json';

    const VALID_SERVICE_PROVIDER_ID = 1;
    const VALID_SERVICE_PROVIDER_NAME = 'screw';

    const INVALID_ID = -999;
    const INVALID_ACL_NAME = 'babbaganoush';

    /**
     * @dataProvider provideGetUserByUserName
     * @param string $userName     the name of the user to be requested.
     * @param string $expectedFile the name of the file that holds the expected
     *                             results.
     */
    public function testGetUserByUserName($userName, $expectedFile)
    {
        $user = XDUser::getUserByUserName($userName);
        $expected = JSON::loadFile(__DIR__ . self::TEST_ARTIFACT_OUTPUT_PATH . DIRECTORY_SEPARATOR . $expectedFile);
        $actual = json_decode(json_encode($user), true);
        $this->assertEquals($expected, $actual);
    }

    public function provideGetUserByUserName()
    {
        return array(
            array(self::PUBLIC_USER_NAME,'public_user.json'),
            array(self::CENTER_STAFF_USER_NAME , 'center_staff.json'),
            array(self::CENTER_DIRECTOR_USER_NAME , 'center_director.json'),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME , 'principal.json'),
            array(self::NORMAL_USER_USER_NAME , 'normal_user.json')
        );
    }

    public function testGetPublicUser()
    {
        $user = XDUser::getPublicUser();
        $this->assertNotNull($user);
    }

    public function testPublicUserIsPublicUser()
    {
        $user = XDUser::getPublicUser();
        $this->assertTrue($user->isPublicUser());
    }

    public function testNonPublicUserIsNotPublicUser()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $this->assertFalse($user->isPublicUser());
    }

    public function testIsDeveloperInvalid()
    {
        $user = XDUser::getUserByUserName(self::PUBLIC_USER_NAME);

        $this->assertFalse($user->isDeveloper());
    }

    public function testIsManagerInvalid()
    {
        $user = XDUser::getUserByUserName(self::PUBLIC_USER_NAME);

        $this->assertFalse($user->isManager());
    }

    public function testGetTokenAsPublic()
    {
        $user = XDUser::getPublicUser();

        $token = $user->getToken();
        $this->assertEquals('', $token);
    }

    public function testGetTokenAsNonPublic()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $token = $user->getToken();
        $this->assertNotEquals('', $token);
    }

    public function testGetTokenExpirationAsPublic()
    {
        $user = XDUser::getPublicUser();

        $expiration = $user->getTokenExpiration();
        $this->assertEquals('', $expiration);
    }

    public function testGetTokenExpirationAsNonPublic()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $expiration = $user->getTokenExpiration();
        $this->assertNotEquals('', $expiration);
    }

    public function testSetOrganizationsValid()
    {
        // NOTE: there is no validation of the organization id

        // TACC original is 561 ( IU )
        $validOrganizationId = 476;
        $defaultConfig = array('active' => true, 'primary' => true);

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        // RETRIEVE: the initial organizations
        $originalOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertTrue(count($originalOrganizations) > 0);

        // SET: the new one in it's place.
        $user->setOrganizations(
            array(
                $validOrganizationId => $defaultConfig
            ),
            self::CENTER_DIRECTOR_ACL_NAME
        );

        // RETRIEVE: them again, this should now be the new one.
        $newOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertNotEmpty($newOrganizations);
        $this->assertTrue(in_array($validOrganizationId, $newOrganizations));

        $original = array();
        foreach (array_values($originalOrganizations) as $organizationId) {
            $original[$organizationId] = $defaultConfig;
        }

        // RESET: the organizations to the original.
        $user->setOrganizations($original, self::CENTER_DIRECTOR_ACL_NAME);
    }

    public function testSetInstitution()
    {
        $validInstitutionId = 476;

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalInstitution = $user->getInstitution();
        $this->assertTrue($originalInstitution !== null);
        $this->assertNotEquals('0', $originalInstitution);

        $user->setInstitution($validInstitutionId);

        $newInstitution = $user->getInstitution();
        $this->assertTrue($newInstitution !== null);
        $this->assertEquals($validInstitutionId, $newInstitution);

        $user->setInstitution($originalInstitution);

        $checkInstitution = $user->getInstitution();
        $this->assertEquals($originalInstitution, $checkInstitution);
    }

    public function testDisassociateWithInstitution()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalInstitution = $user->getInstitution();

        $user->disassociateWithInstitution();

        $newInstitution = $user->getInstitution();
        $this->assertEquals('-1', $newInstitution);

        $user->setInstitution($originalInstitution);

        $institution = $user->getInstitution();

        $this->assertEquals($originalInstitution, $institution);
    }

    public function testSetOrganizationsEmpty()
    {
        $defaultConfig = array('active' => true, 'primary' => true);

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);

        $this->assertTrue($originalOrganizations !== null);
        $this->assertTrue(count($originalOrganizations) > 0);

        $user->setOrganizations(array(), self::CENTER_DIRECTOR_ACL_NAME);

        $newOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertEmpty($newOrganizations);

        $original = array();
        foreach (array_values($originalOrganizations) as $organizationId) {
            $original[$organizationId] = $defaultConfig;
        }
        $user->setOrganizations($original, self::CENTER_DIRECTOR_ACL_NAME);

        $organizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertEquals($originalOrganizations, $organizations);
    }

    public function testGetRolesPublic()
    {
        $user = XDUser::getPublicUser();

        $roles = $user->getRoles();

        $this->assertTrue($roles !== null);
        $this->assertTrue(count($roles) === 1);
        $this->assertTrue(in_array(self::PUBLIC_ACL_NAME, $roles));
    }

    public function testGetRolesInformalEqualsGetAclNames()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $roles = $user->getRoles();
        $acls = $user->getAcls(true);

        $this->assertEquals($roles, $acls);
    }

    public function testGetRolesFormal()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $roles = $user->getRoles('formal');

        $this->assertNotNull($roles);
        $this->assertTrue(count($roles) > 0);
        foreach ($roles as $roleDisplay => $roleAbbrev) {
            $abbrevLength = strlen($roleAbbrev);
            $displayLength = strlen($roleDisplay);
            $this->assertTrue($displayLength >= $abbrevLength);
        }
    }

    public function testGetRolesCasual()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $roles = $user->getRoles('casual');
        $this->assertNull($roles);
    }

    public function testSetRolesEmpty()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalRoles = $user->getRoles();

        $this->assertTrue(count($originalRoles) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $originalRoles));

        $user->setRoles(array());
        $user->saveUser();

        $newRoles = $user->getRoles();
        $this->assertEmpty($newRoles);

        $user->setRoles($originalRoles);
        $user->saveUser();

        $roles = $user->getRoles();
        $this->assertEquals($originalRoles, $roles);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $roles));
    }

    public function testGetAcls()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $acls = $user->getAcls();

        $this->assertTrue(count($acls) > 0);

        foreach ($acls as $acl) {
            $class = get_class($acl);
            $isAcl = $class === 'Models\Acl';
            $this->assertTrue($isAcl);
        }
    }

    public function testGetAclNames()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $acls = $user->getAcls(true);
        $this->assertTrue(count($acls) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $acls));
    }

    public function testSetAclsEmpty()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls();
        $this->assertTrue(count($originalAcls) > 0);

        $user->setAcls(array());
        $user->saveUser();

        $newAcls = $user->getAcls();
        $this->assertTrue(count($newAcls) === 0);

        $user->setAcls($originalAcls);
        $user->saveUser();

        $acls = $user->getAcls();
        $this->assertEquals($originalAcls, $acls);
    }

    public function testAddNewAcl()
    {
        $newAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGATOR_ACL_NAME);
        $this->assertNotNull($newAcl);

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertFalse(in_array($newAcl->getName(), $originalAcls));

        $user->addAcl($newAcl);
        $user->saveUser();

        $newAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertTrue(in_array($newAcl->getName(), $newAcls));

        $user->removeAcl($newAcl);
        $user->saveUser();

        $acls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertFalse(in_array($newAcl->getName(), $acls));
    }

    public function testAddExistingAclNoOverwrite()
    {
        $existingAcl = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertTrue(in_array($existingAcl->getName(), $originalAcls));

        $user->addAcl($existingAcl);
        $user->saveUser();

        $newAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertTrue(in_array($existingAcl->getName(), $newAcls));
        $this->assertEquals($originalAcls, $newAcls);
    }


    public function testAddExistingAclOverwrite()
    {
        $existingAcl = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertTrue(in_array($existingAcl->getName(), $originalAcls));

        $user->addAcl($existingAcl, true);
        $user->saveUser();

        $newAcls = $user->getAcls(true);
        $this->assertTrue(count($originalAcls) > 0);
        $this->assertTrue(in_array($existingAcl->getName(), $newAcls));
        $this->assertEquals($originalAcls, $newAcls);
    }

    public function testHasAclExists()
    {
        $existingAcl = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertTrue($hasAcl);
    }

    public function testHasAclNotExists()
    {
        $existingAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGATOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertFalse($hasAcl);
    }

    public function testHasAclsExists()
    {
        $acls = array();
        $acls [] = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $acls [] = Acls::getAclByName('usr');

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertTrue($hasAcls);
    }

    public function testHasAclsNotExists()
    {
        $acls = array('dev', 'mgr');

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    public function testHasAclsPartialExists()
    {
        $acls = array('dev', 'cd');
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    public function testGetUserByUserNameValid()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $this->assertNotNull($user);
        $this->assertNotNull($user->getUserID());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage User "bilbo" not found
     */
    public function testGetUserByUserNameInvalid()
    {
        XDUser::getUserByUserName("bilbo");
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage User "" not found
     */
    public function testGetUserByUserNameEmptyString()
    {
        XDUser::getUserByUserName("");
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No username provided
     */
    public function testGetUserByUserNameNull()
    {
        XDUser::getUserByUserName(null);
    }

    /**
     * @expectedException Exception
     **/
    public function testHasAclWithNonAclTypeShouldThrowException()
    {
        $acl = new \StdClass;
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $user->hasAcl($acl);
    }

    public function testUserIsManager()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $isManager = $user->isManager();
        $this->assertFalse($isManager);
    }

    public function testGetPrimaryRoleWithPublicUser()
    {
        $user = XDUser::getPublicUser();
        $primaryRole = $user->getPrimaryRole();

        $this->assertNull($primaryRole);
    }

    /**
     * @expectedException Exception
     **/
    public function testGetPrimaryRoleWithNewUser()
    {
        $user = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');
        $user->getPrimaryRole();
    }

    public function testGetActiveRoleWithPublicUser()
    {
        $user = XDUser::getPublicUser();
        $activeRole = $user->getActiveRole();

        $this->assertNotNull($activeRole);
    }

    /**
     * @expectedException Exception
     **/
    public function testGetActiveRoleWithNewUserShouldFail()
    {
        $user = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');
        $user->getActiveRole();
    }

    /**
     * Expect that it should complain about not having a valid user type.
     *
     * @expectedException Exception
     **/
    public function testCreateUserWithoutUserTypeShouldFail()
    {
        $user = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    public function testCreateUser()
    {
        $user = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(XSEDE_USER_TYPE);

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    /**
     * Expect that it should complain about there already being a test user.
     *
     * @expectedException Exception
     **/
    public function testCreateUserWithExistingUserNameShouldFail()
    {
        $anotherUser = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');
        $anotherUser->setUserType(XSEDE_USER_TYPE);
        $anotherUser->saveUser();
    }

    /**
     * @expectedException Exception
     **/
    public function testCreateUserWithExistingEmailShouldFail()
    {
        new XDUser('test2', null, 'public@ccr.xdmod.org', 'public', 'a', 'user');
    }

    /**
     * @expectedException Exception
     **/
    public function testSaveUserWithSameEmailAndNotXsedeTypeAndNoIdShouldFail()
    {
        $anotherUser = new XDUser('test2', null, 'public@ccr.xdmod.org', 'public', 'a', 'user');
        $anotherUser->setUserType(DEMO_USER_TYPE);
        $anotherUser->saveUser();
    }

    /**
     * @expectedException Exception
     **/
    public function testSavePublicUserShouldFail()
    {
        $user = XDUser::getPublicUser();
        $user->saveUser();
    }

    /**
     * @expectedException Exception
     **/
    public function testSaveUserWithDefaultUserType()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $user->setUserType(0);
        $user->saveUser();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage User "test" not found
     */
    public function testRemoveUser()
    {
        $user = XDUser::getUserByUserName('test');

        $this->assertNotNull($user);

        $user->removeUser();

        XDUser::getUserByUserName('test');
    }

    /**
     * Cannot remove the public user
     *
     * @expectedException Exception
     **/
    public function testRemovePublicUserShouldFail()
    {
        $user = XDUser::getPublicUser();

        $user->removeUser();
    }


    public function testGetUserByIDInvalidUID()
    {
        $user = XDUser::getUserByID(self::INVALID_ID);
        $this->assertNull($user);
    }

    public function testGetuserByIDNull()
    {
        $user = XDUser::getUserByID(null);
        $this->assertNull($user);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage You must call saveUser() on this newly created XDUser prior to using getActiveRole()
     */
    public function testGetActiveRoleOnUnSavedUserFails()
    {
        $anotherUser = new XDUser('test3', null, 'public3@ccr.xdmod.org', 'public', 'a', 'user');
        $anotherUser->getActiveRole();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage An additional parameter must be passed for this role (organization id)
     */
    public function testSetActiveRoleForCenterDirectorWithNoRoleParamShouldFail()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $user->setActiveRole(self::CENTER_DIRECTOR_ACL_NAME);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage An invalid organization id has been specified for the role you are attempting to make active
     */
    public function testSetActiveRoleForCenterDirectorWithInvalidOrgIDShouldFail()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $user->setActiveRole(self::CENTER_DIRECTOR_ACL_NAME, self::INVALID_ID);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage An additional parameter must be passed for this role (organization id)
     */
    public function testSetActiveRoleForCenterStaffWithNoRoleParamShouldFail()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);

        $user->setActiveRole(self::CENTER_STAFF_ACL_NAME);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage An invalid organization id has been specified for the role you are attempting to make active
     */
    public function testSetActiveRoleForCenterStaffWithInvalidOrgIDShouldFail()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);

        $user->setActiveRole(self::CENTER_STAFF_ACL_NAME, self::INVALID_ID);
    }

    public function testSetActiveRoleForCenterDirectorWithValidOrgID()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $user->setActiveRole(self::CENTER_DIRECTOR_ACL_NAME, self::VALID_SERVICE_PROVIDER_ID);

        $activeRole = $user->getActiveRole();
        $this->assertNotNull($activeRole);

        $activeRoleName = $activeRole->getIdentifier();
        $this->assertEquals(self::CENTER_DIRECTOR_ACL_NAME, $activeRoleName);
    }

    public function testSetActiveRoleForCenterStaffWithValidOrgID()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);

        $user->setActiveRole(self::CENTER_STAFF_ACL_NAME, self::VALID_SERVICE_PROVIDER_ID);

        $activeRole = $user->getActiveRole();
        $this->assertNotNull($activeRole);

        $activeRoleName = $activeRole->getIdentifier();
        $this->assertEquals(self::CENTER_STAFF_ACL_NAME, $activeRoleName);
    }

    public function testUpgradeStaffMemberSaveUser()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);

        $cd->upgradeStaffMember($user);
        $newRoles = $user->getRoles();

        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $newRoles));
    }

    /**
     * @depends testUpgradeStaffMemberSaveUser
     */
    public function testDowngradeStaffMemberSaveUser()
    {

        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $cd = new CenterDirectorRole();
        $cd->configure($user);
        $cd->downgradeStaffMember($user);
        $newRoles = $user->getRoles();

        $this->assertTrue(!in_array(self::CENTER_DIRECTOR_ACL_NAME, $newRoles));
    }

    public function testSaveUserUpdatePassword()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $user->setPassword(self::INVALID_ACL_NAME);
        $user->saveUser();

        $updatedUser = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $reflection = new ReflectionClass($updatedUser);
        $password = $reflection->getProperty('_password');
        $password->setAccessible(true);
        $newPassword = $password->getValue($updatedUser);
        $this->assertEquals(md5(self::INVALID_ACL_NAME), $newPassword);

        $user->setPassword(self::CENTER_STAFF_USER_NAME);
        $user->saveUser();
    }

    public function testGetRoleIDForValidRole()
    {
        $user = XDUSer::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $reflection = new ReflectionClass($user);
        $method = $reflection->getMethod('_getRoleID');
        $method->setAccessible(true);
        $roleId = $method->invoke($user, self::CENTER_STAFF_ACL_NAME);

        $this->assertNotNull($roleId);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage Undefined offset: 0
     */
    public function testGetRoleIDForInvalidRole()
    {
        $user = XDUSer::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $reflection = new ReflectionClass($user);
        $method = $reflection->getMethod('_getRoleID');
        $method->setAccessible(true);
        $method->invoke($user, self::INVALID_ACL_NAME);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage Undefined offset: 0
     */
    public function testGetRoleWithNull()
    {
        $user = XDUSer::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $reflection = new ReflectionClass($user);
        $method = $reflection->getMethod('_getRoleID');
        $method->setAccessible(true);
        $method->invoke($user, null);
    }

    public function testCenterDirectorEnumAllAvailableRoles()
    {
        $expected = JSON::loadFile(__DIR__ . self::TEST_ARTIFACT_OUTPUT_PATH . DIRECTORY_SEPARATOR . 'center_director_all_available_roles.json');
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $allAvailableRoles = $user->enumAllAvailableRoles();
        $this->assertEquals($expected, $allAvailableRoles);
    }

    /**
     * @dataProvider provideGetMostPrivilegedRole
     * @param string $userName the username of the user to request
     * @param string $expected the expected result
     */
    public function testGetMostPrivilegedRole($userName, $expected)
    {
        $user = XDUser::getUserByUserName($userName);
        $mostPrivilegedRole = $user->getMostPrivilegedRole();
        $this->assertNotNull($mostPrivilegedRole);
        $this->assertEquals($mostPrivilegedRole->getIdentifier(), $expected);

    }

    public function provideGetMostPrivilegedRole()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME , self::CENTER_DIRECTOR_ACL_NAME),
            array(self::CENTER_STAFF_USER_NAME , self::CENTER_STAFF_ACL_NAME),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME , self::PRINCIPAL_INVESTIGATOR_ACL_NAME),
            array(self::NORMAL_USER_USER_NAME , self::NORMAL_USER_ACL),
            array(self::PUBLIC_USER_NAME , self::PUBLIC_ACL_NAME)
        );
    }

    /**
     * @dataProvider provideGetAllRoles
     * @param string $userName
     * @param array  $expected
     */
    public function testGetAllRoles($userName, array $expected)
    {

        $user = XDUser::getUserByUserName($userName);
        $allRoles = $user->getAllRoles();
        $actual = array_reduce(
            $allRoles,
            function ($carry, $item) {
                $carry[] = $item->getIdentifier();
                return $carry;
            },
            array()
        );
        $this->assertEquals($expected, $actual);
    }

    public function provideGetAllRoles()
    {
        return array(
            array(
                self::CENTER_DIRECTOR_USER_NAME,
                array(
                    self::CENTER_DIRECTOR_ACL_NAME,
                    self::NORMAL_USER_ACL
                )
            ),
            array(
                self::CENTER_STAFF_USER_NAME,
                array(
                    self::CENTER_STAFF_ACL_NAME,
                    self::NORMAL_USER_ACL
                )),
            array(
                self::PRINCIPAL_INVESTIGATOR_USER_NAME,
                array(
                    self::PRINCIPAL_INVESTIGATOR_ACL_NAME,
                    self::NORMAL_USER_ACL
                )),
            array(
                self::NORMAL_USER_USER_NAME,
                array(
                    self::NORMAL_USER_ACL
                )),
            array(
                self::PUBLIC_USER_NAME,
                array(
                    self::PUBLIC_ACL_NAME
                ))
        );
    }

    /**
     * @dataProvider provideIsCenterDirectorOfOrganizationValidCenter
     * @param string $userName
     * @param bool   $expected
     */
    public function testIsCenterDirectorOfOrganizationValidCenter($userName, $expected)
    {
        $validOrganizationId = 1;

        $user = XDUser::getUserByUserName($userName);
        $actual = $user->isCenterDirectorOfOrganization($validOrganizationId);
        $this->assertEquals($expected, $actual);
    }

    public function provideIsCenterDirectorOfOrganizationValidCenter()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME, true),
            array(self::CENTER_STAFF_USER_NAME, false),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, false),
            array(self::NORMAL_USER_USER_NAME, false),
            array(self::PUBLIC_USER_NAME, false)
        );
    }

    /**
     * @dataProvider provideIsCenterDirectorOfOrganizationInvalidCenter
     * @param string $userName
     * @param bool   $expected
     */
    public function testIsCenterDirectorOfOrganizationInvalidCenter($userName, $expected)
    {
        $invalidOrganizationId = -999;

        $user = XDUser::getUserByUserName($userName);
        $actual = $user->isCenterDirectorOfOrganization($invalidOrganizationId);
        $this->assertEquals($expected, $actual);
    }

    public function provideIsCenterDirectorOfOrganizationInvalidCenter()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME, false),
            array(self::CENTER_STAFF_USER_NAME, false),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, false),
            array(self::NORMAL_USER_USER_NAME, false),
            array(self::PUBLIC_USER_NAME, false)
        );
    }

    public function testIsCenterDirectorOfOrganizationNull()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $actual = $user->isCenterDirectorOfOrganization(null);
        $this->assertEquals(false, $actual);
    }

    public function testIsCenterDirectorOfOrganizationEmptyString()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $actual = $user->isCenterDirectorOfOrganization("");
        $this->assertEquals(false, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling enumCenterDirectorSites()
     */
    public function testEnumCenterDirectorSitesWithUnsavedUserFails()
    {
        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');
        $user->enumCenterDirectorSites();
    }

    /**
     * @dataProvider provideEnumCenterDirectorsSites
     * @param string $userName
     * @param bool $expected
     */
    public function testEnumCenterDirectorSites($userName, $expected)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->enumCenterDirectorSites();
        $this->assertEquals($expected, $actual);

    }

    public function provideEnumCenterDirectorsSites()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME, array(array('provider' => '1', 'is_primary' => '1'))),
            array(self::CENTER_STAFF_USER_NAME,  array()),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME,  array()),
            array(self::NORMAL_USER_USER_NAME,  array()),
            array(self::PUBLIC_USER_NAME,  array())
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling enumCenterStaffSites()
     */
    public function testEnumCenterStaffSitesWithUnsavedUserFails()
    {
        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');
        $user->enumCenterStaffSites();
    }

    /**
     * @dataProvider provideEnumCenterStaffSites
     * @param string $userName
     * @param array  $expected
     */
    public function testEnumCenterStaffSites($userName, $expected)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->enumCenterStaffSites();
        $this->assertEquals($expected, $actual);
    }

    public function provideEnumCenterStaffSites()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME, array()),
            array(self::CENTER_STAFF_USER_NAME, array(array('provider' => '1', 'is_primary' => '1'))),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, array()),
            array(self::NORMAL_USER_USER_NAME, array()),
            array(self::PUBLIC_USER_NAME, array())
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling getPrimaryOrganization()
     */
    public function testGetPrimaryOrganizationForUnsavedUserFails()
    {
        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');
        $user->getPrimaryOrganization();
    }

    /**
     * @dataProvider provideGetPrimaryOrganization
     * @param $userName
     * @param $expected
     */
    public function testGetPrimaryOrganization($userName, $expected)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->getPrimaryOrganization();
        $this->assertEquals($expected, $actual);
    }

    public function provideGetPrimaryOrganization()
    {
        return array(
            array(self::CENTER_DIRECTOR_USER_NAME, '1'),
            array(self::CENTER_STAFF_USER_NAME, '-1'),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, '-1'),
            array(self::NORMAL_USER_USER_NAME, '-1'),
            array(self::PUBLIC_USER_NAME, '-1')
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling getOrganizationCollection()
     */
    public function testGetOrganizationCollectionWithUnsavedUserFails()
    {
        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');
        $user->getOrganizationCollection();
    }

    /**
     * @dataProvider provideGetOrganizationCollection
     * @param string $userName
     * @param array $expectedData
     */
    public function testGetOrganizationCollection($userName, $expectedData)
    {
        foreach ($expectedData as $centerStaffOrDirector => $expected) {
            $user = XDUser::getUserByUserName($userName);
            $actual = $user->getOrganizationCollection($centerStaffOrDirector);
            $this->assertEquals($expected, $actual);
        }
    }

    public function provideGetOrganizationCollection()
    {
        return array(
            array(
                self::CENTER_DIRECTOR_USER_NAME, array(
                    self::CENTER_STAFF_ACL_NAME => array(),
                    self::CENTER_DIRECTOR_ACL_NAME => array(1),
                    null => array(),
                    '' => array()
                )
            ),
            array(
                self::CENTER_STAFF_USER_NAME, array(
                    self::CENTER_STAFF_ACL_NAME => array(1),
                    self::CENTER_DIRECTOR_ACL_NAME => array(),
                    null => array(),
                    '' => array()
                )
            ),
            array(
                self::PRINCIPAL_INVESTIGATOR_USER_NAME, array(
                    self::CENTER_STAFF_ACL_NAME => array(),
                    self::CENTER_DIRECTOR_ACL_NAME => array(),
                    null => array(),
                    '' => array()
                )
            ),
            array(
                self::NORMAL_USER_USER_NAME, array(
                    self::CENTER_STAFF_ACL_NAME => array(),
                    self::CENTER_DIRECTOR_ACL_NAME => array(),
                    null => array(),
                    '' => array()
                )
            ),
            array(
                self::PUBLIC_USER_NAME, array(
                    self::CENTER_STAFF_ACL_NAME => array(),
                    self::CENTER_DIRECTOR_ACL_NAME => array(),
                    null => array(),
                    '' => array()
                )
            )
        );
    }

    /**
     * @dataProvider provideGetRoleIDFromIdentifierInvalidFails
     * @param string $roleName
     */
    public function testGetRoleIDFromIdentifierInvalidFails($roleName)
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $reflection = new ReflectionClass($user);
        $getRoleIdFromIdentifier = $reflection->getMethod('_getRoleIDFromIdentifier');
        $getRoleIdFromIdentifier->setAccessible(true);


        $actual = $getRoleIdFromIdentifier->invoke($user, $roleName);
        $this->assertEquals(-1, $actual);

    }

    public function provideGetRoleIDFromIdentifierInvalidFails()
    {
        return array(
            array(self::INVALID_ACL_NAME),
            array(''),
            array(null)
        );
    }

    /**
     * @dataProvider provideGetRoleIDFromIdentifier
     * @param string $role
     */
    public function testGetRoleIDFromIdentifier($role)
    {
        $db = DB::factory('database');
        $results = array();

        $row = $db->query(
            "SELECT role_id FROM Roles WHERE abbrev = :abbrev",
            array(':abbrev' => $role)
        );
        $this->assertNotEmpty($row);
        $results[$role] = $row[0]['role_id'];


        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $reflection = new ReflectionClass($user);
        $getRoleIdFromIdentifier = $reflection->getMethod('_getRoleIDFromIdentifier');
        $getRoleIdFromIdentifier->setAccessible(true);

        foreach($results as $roleName => $expected) {
            $actual = $getRoleIdFromIdentifier->invoke($user, $roleName);
            $this->assertEquals($expected, $actual);
        }
    }

    public function provideGetRoleIDFromIdentifier()
    {
        return array(
            array(self::CENTER_DIRECTOR_ACL_NAME),
            array(self::CENTER_STAFF_ACL_NAME),
            array(self::PRINCIPAL_INVESTIGATOR_ACL_NAME),
            array(self::NORMAL_USER_ACL),
            array(self::PUBLIC_ACL_NAME)
        );
    }

    public function testGetPromoterUnsavedUser()
    {

        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');
        $promoter = $user->getPromoter(self::CENTER_DIRECTOR_ACL_NAME, 1);
        $this->assertEquals(-1, $promoter);
    }

    /**
     * @dataProvider provideGetPromoter
     * @param $userName
     * @param $aclData
     */
    public function testGetPromoter($userName, $aclData)
    {
        $user = XDUser::getUserByUserName($userName);
        foreach ($aclData as $roleId => $expectedData) {
            foreach ($expectedData as $organizationId => $expected) {
                $actual = $user->getPromoter($roleId, $organizationId);
                $this->assertEquals($expected, $actual);
            }
        }
    }

    public function provideGetPromoter()
    {
        return array(
            array(
                self::CENTER_DIRECTOR_USER_NAME, array(
                    self::CENTER_DIRECTOR_ACL_NAME => array(
                        '1' => '-1',
                        '' => -1,
                        null => -1
                    ),
                    self::CENTER_DIRECTOR_ACL_NAME => array(
                        '1' => -1,
                        '' => -1,
                        null => -1
                    )
                )
            ),
            array(
                self::CENTER_STAFF_USER_NAME, array(
                    self::CENTER_DIRECTOR_ACL_NAME => array(
                        '1' => '-1',
                        '' => -1,
                        null => -1
                    ),
                    self::CENTER_STAFF_ACL_NAME => array(
                        '1' => -1,
                        '' => -1,
                        null => -1
                    )
                )
            )
        );
    }

    /**
     * @dataProvider provideGetFormalRoleName
     * @param string $roleName
     * @param string $expected
     */
    public function testGetFormalRoleName($roleName, $expected)
    {
        $user = new XDUser('test4', null, 'test4@ccr.xdmod.org', 'test', 'a', 'user');

        $actual = $user->_getFormalRoleName($roleName);
        $this->assertEquals($expected, $actual);
    }

    public function provideGetFormalRoleName()
    {
        return array(
            array(self::CENTER_DIRECTOR_ACL_NAME, 'Center Director'),
            array(self::CENTER_STAFF_ACL_NAME, 'Center Staff'),
            array(self::PRINCIPAL_INVESTIGATOR_ACL_NAME, 'Principal Investigator'),
            array(self::NORMAL_USER_ACL, 'User'),
            array(self::PUBLIC_ACL_NAME, 'Public'),
            array(self::INVALID_ACL_NAME, 'Public'),
            array(null, 'Public'),
            array('', 'Public')
        );
    }
}
