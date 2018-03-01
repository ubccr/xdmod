<?php

namespace ComponentTests;

use CCR\DB;
use CCR\Json;
use Models\Acl;
use ReflectionClass;
use User\Roles\CenterDirectorRole;
use \XDUser;
use Models\Services\Acls;
use \Exception;
use TestHarness\TestFiles;

/**
 * modify the isDeveloper function.
 * @group skip
 **/
class XDUserTest extends BaseTest
{
    private static $users = array();

    /**
     * @var TestFiles
     */
    private $testFiles;

    const DEFAULT_TEST_ENVIRONMENT = 'open_xdmod';

    protected function setUp()
    {
        $this->testFiles = new TestFiles(__DIR__ . '/../');
    }

    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../');
        }
        return $this->testFiles;
    }

    /**
     * @dataProvider provideGetUserByUserName
     * @param string $userName the name of the user to be requested.
     * @param string $expectedFile the name of the file that holds the expected
     *                             results.
     * @throws Exception
     */
    public function testGetUserByUserName($userName, $expectedFile)
    {
        $user = XDUser::getUserByUserName($userName);
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('acls', $expectedFile)
        );
        $actual = json_decode(json_encode($user), true);
        if ($expected['_password'] !== null) {
            $this->assertTrue(password_verify($userName, $actual['_password']));
            unset($expected['_password']);
            unset($actual['_password']);
        }
        $this->assertEquals($expected, $actual);
    }

    public function provideGetUserByUserName()
    {
        return array(
            array(self::PUBLIC_USER_NAME,'public_user'),
            array(self::CENTER_STAFF_USER_NAME , 'center_staff'),
            array(self::CENTER_DIRECTOR_USER_NAME , 'center_director-update_enumAllAvailableRoles'),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME , 'principal-update_enumAllAvailableRoles'),
            array(self::NORMAL_USER_USER_NAME , 'normal_user')
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

    /**
     * @throws Exception
     */
    public function testNonPublicUserIsNotPublicUser()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $this->assertFalse($user->isPublicUser());
    }

    /**
     * @throws Exception
     */
    public function testIsDeveloperInvalid()
    {
        $user = XDUser::getUserByUserName(self::PUBLIC_USER_NAME);

        $this->assertFalse($user->isDeveloper());
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testGetTokenExpirationAsNonPublic()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $expiration = $user->getTokenExpiration();
        $this->assertNotEquals('', $expiration);
    }

    /**
     * @dataProvider provideSetOrganizationsValid
     * @param array $options
     * @throws Exception
     */
    public function testSetOrganizationsValid(array $options)
    {
        $this->assertArrayHasKey('username', $options);
        $this->assertArrayHasKey('acl', $options);
        $this->assertArrayHasKey('organizations', $options);

        $username = $options['username'];
        $organizations = $options['organizations'];
        $acl = $options['acl'];
        $defaultConfig= array('primary' => true, 'active' => true);

        $user = XDUser::getUserByUserName($username);

        // RETRIEVE: the initial organizations
        $originalOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertTrue(count($originalOrganizations) > 0);

        $user->setOrganizations($organizations, $acl);

        // RETRIEVE: them again, this should now be the new one.
        $newOrganizations = $user->getOrganizationCollection($acl);
        $this->assertNotEmpty($newOrganizations);
        $diff = array_diff(array_keys($organizations), $newOrganizations);
        $this->assertEmpty($diff);

        $original = array();
        foreach (array_values($originalOrganizations) as $organizationId) {
            $original[$organizationId] = $defaultConfig;
        }

        // RESET: the organizations to the original.
        $user->setOrganizations($original, $acl);
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideSetOrganizationsValid()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'set_organization_valid', 'input')
        );
    }

    /**
     * @dataProvider provideSetInstitution
     * @param $validInstitutionId
     * @throws Exception
     */
    public function testSetInstitution($validInstitutionId)
    {
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

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideSetInstitution()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'center_director_valid_organization_ids')
        );
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testSetOrganizationsEmpty()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);

        $this->assertTrue($originalOrganizations !== null);
        $this->assertTrue(count($originalOrganizations) > 0);

        $user->setOrganizations(array(), self::CENTER_DIRECTOR_ACL_NAME);

        $newOrganizations = $user->getOrganizationCollection(self::CENTER_DIRECTOR_ACL_NAME);
        $this->assertEmpty($newOrganizations);

        $original = array();
        $originalOrganizationValues = array_values($originalOrganizations);
        for ($i = 0; $i < count($originalOrganizationValues); $i++) {
            $primary = $i === count($originalOrganizationValues) - 1;
            $value = $originalOrganizationValues[$i];
            $original[$value] = array('active' => true, 'primary' => $primary);
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

    /**
     * @throws Exception
     */
    public function testGetRolesInformalEqualsGetAclNames()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $roles = $user->getRoles();
        $acls = $user->getAcls(true);

        $this->assertEquals($roles, $acls);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testGetRolesCasual()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $roles = $user->getRoles('casual');
        $this->assertNull($roles);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage A user must have at least one role.
     */
    public function testSetRolesEmpty()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalRoles = $user->getRoles();

        $this->assertTrue(count($originalRoles) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $originalRoles));

        $user->setRoles(array());
        $user->saveUser();
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testGetAclNames()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $acls = $user->getAcls(true);
        $this->assertTrue(count($acls) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $acls));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage A user must have at least one acl.
     */
    public function testSetAclsEmpty()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls();
        $this->assertTrue(count($originalAcls) > 0);

        $user->setAcls(array());
        $user->saveUser();
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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


    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testHasAclExists()
    {
        $existingAcl = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertTrue($hasAcl);
    }

    /**
     * @throws Exception
     */
    public function testHasAclNotExists()
    {
        $existingAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGATOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertFalse($hasAcl);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsExists()
    {
        $acls = array();
        $acls [] = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $acls [] = Acls::getAclByName('usr');

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertTrue($hasAcls);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsNotExists()
    {
        $acls = array('dev', 'mgr');

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsPartialExists()
    {
        $acls = array('dev', 'cd');
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testUserIsManager()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $isManager = $user->isManager();
        $this->assertFalse($isManager);
    }

    /**
     * @throws Exception
     */
    public function testGetPrimaryRoleWithPublicUser()
    {
        $user = XDUser::getPublicUser();
        $primaryRole = $user->getPrimaryRole();

        $this->assertNotNull($primaryRole);
    }

    /**
     * @expectedException Exception
     **/
    public function testGetPrimaryRoleWithNewUser()
    {
        $user = self::getUser(null, 'test', 'a', 'user');

        $user->getPrimaryRole();
    }

    /**
     * @throws Exception
     */
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
        $user = self::getUser(null, 'test', 'a', 'user');
        $user->getActiveRole();
    }

    /**
     * Expect that it should complain about not having a valid user type.
     *
     * @expectedException Exception
     **/
    public function testCreateUserWithoutUserTypeShouldFail()
    {
        $user = self::getUser(null, 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    /**
     * @throws Exception
     */
    public function testCreateUser()
    {
        $user = self::getUser(null, 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(FEDERATED_USER_TYPE);

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage At least one role must be associated with this user
     */
    public function testCreateUserWithNoRoles()
    {
        $user = self::getUser(null, 'test', 'a', 'user', array());
        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(FEDERATED_USER_TYPE);

        $user->saveUser();
        $this->assertNotNull($user->getUserID());
    }

    /**
     * @throws Exception
     */
    public function testCreateUserWithNonStandardPrimaryRole()
    {
        $user = self::getUser(null, 'test', 'a', 'user', array(ROLE_ID_USER, ROLE_ID_MANAGER), ROLE_ID_MANAGER);

        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(DEMO_USER_TYPE);

        $user->saveUser();

        $actual = $user->getActiveRole()->getIdentifier();

        // This is due to the way 'most privileged' works. It prefers acls that
        // take part in a hierarchy as opposed to those that do not. Since 'usr'
        // is a part of a hierarchy ( 'mgr' just enables access to certain
        // features ) then that is what is returned.
        $this->assertEquals(ROLE_ID_USER, $actual);
    }

    /**
     * Expect that it should complain about there already being a test user.
     *
     * @expectedException Exception
     **/
    public function testCreateUserWithExistingUserNameShouldFail()
    {
        $username = array_keys(self::$users)[count(self::$users) - 1];
        $anotherUser = self::getUser(null, 'test', 'a', 'user', array(ROLE_ID_USER), ROLE_ID_USER, null, $username);
        $anotherUser->setUserType(FEDERATED_USER_TYPE);
        $anotherUser->saveUser();
    }

    /**
     * @expectedException Exception
     **/
    public function testCreateUserWithExistingEmailShouldFail()
    {
        $username = array_keys(self::$users)[count(self::$users) - 1];
        self::getUser(null, 'public', 'a', 'user', array(ROLE_ID_USER), ROLE_ID_USER, $username . self::DEFAULT_EMAIL_ADDRESS_SUFFIX);
    }

    /**
     * @expectedException Exception
     **/
    public function testSaveUserWithSameEmailAndNotXsedeTypeAndNoIdShouldFail()
    {
        $username = array_keys(self::$users)[count(self::$users) - 1];
        $anotherUser = self::getUser(null, 'public', 'a', 'user', array(ROLE_ID_USER), ROLE_ID_USER, $username . self::DEFAULT_EMAIL_ADDRESS_SUFFIX);
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
     * @expectedExceptionMessageRegExp /User "(\w+)" not found/
     */
    public function testRemoveUser()
    {
        $user = self::getUser(null, 'Test', 'A', 'User', array('usr'));
        $user->setUserType(self::DEFAULT_USER_TYPE);
        $user->saveUser();
        $userName = $user->getUsername();

        $this->assertNotNull($user);

        $user->removeUser();

        XDUser::getUserByUserName($userName);
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
        $anotherUser = self::getUser(null, 'public', 'a', 'user');
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

    /**
     * @dataProvider provideSetActiveRoleForCenterDirectorWithValidOrgID
     * @param $validServiceProviderId
     * @throws Exception
     */
    public function testSetActiveRoleForCenterDirectorWithValidOrgID($validServiceProviderId)
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $user->setActiveRole(self::CENTER_DIRECTOR_ACL_NAME, $validServiceProviderId);

        $activeRole = $user->getActiveRole();
        $this->assertNotNull($activeRole);

        $activeRoleName = $activeRole->getIdentifier();
        $this->assertEquals(self::CENTER_DIRECTOR_ACL_NAME, $activeRoleName);
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideSetActiveRoleForCenterDirectorWithValidOrgID()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'center_director_valid_organization_ids')
        );
    }

    /**
     * @throws Exception
     */
    public function testSetActiveRoleForCenterStaffWithValidOrgID()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);

        $user->setActiveRole(self::CENTER_STAFF_ACL_NAME, self::VALID_SERVICE_PROVIDER_ID);

        $activeRole = $user->getActiveRole();
        $this->assertNotNull($activeRole);

        $activeRoleName = $activeRole->getIdentifier();
        $this->assertEquals(self::CENTER_STAFF_ACL_NAME, $activeRoleName);
    }

    /**
     * @throws Exception
     */
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
     * @throws Exception
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

    /**
     * @throws Exception
     */
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
        $this->assertTrue(password_verify(self::INVALID_ACL_NAME, $newPassword));

        $user->setPassword(self::CENTER_STAFF_USER_NAME);
        $user->saveUser();
    }

    /**
     * @throws Exception
     */
    public function testGetRoleIDForValidRole()
    {
        $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
        $reflection = new ReflectionClass($user);
        $method = $reflection->getMethod('_getRoleID');
        $method->setAccessible(true);
        $roleId = $method->invoke($user, self::CENTER_STAFF_ACL_NAME);

        $this->assertNotNull($roleId);
    }

    /**
     *
     */
    public function testGetRoleIDForInvalidRole()
    {
        try {
            $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
            $reflection = new ReflectionClass($user);
            $method = $reflection->getMethod('_getRoleID');
            $method->setAccessible(true);
            $result = $method->invoke($user, self::INVALID_ACL_NAME);
            $this->assertNull($result);
        } catch (Exception $e) {
            $expectedMessage = 'Undefined offset: 0';
            $isCorrectClass = $e instanceof \PHPUnit_Framework_Error_Notice;
            $message = $e->getMessage();

            $this->assertTrue(
                $isCorrectClass,
                "Expected an exception of type [\PHPUnit_Framework_Error_Notice]. Received: [" . get_class($e) . "]"
            );
            $this->assertNotFalse(
                strpos($message, $expectedMessage),
                "Expected the message to contain [$expectedMessage]. Received: [$message]"
            );
        }
    }

    public function testGetRoleWithNull()
    {
        try {
            $user = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME);
            $reflection = new ReflectionClass($user);
            $method = $reflection->getMethod('_getRoleID');
            $method->setAccessible(true);
            $result = $method->invoke($user, null);
            $this->assertNull($result, "Expected [null]. Received [$result]");
        } catch (Exception $e) {
            $expectedMessage = 'Undefined offset: 0';
            $isCorrectClass = $e instanceof \PHPUnit_Framework_Error_Notice;
            $message = $e->getMessage();

            $this->assertTrue(
                $isCorrectClass,
                "Expected an exception of type [\PHPUnit_Framework_Error_Notice]. Received: [" . get_class($e) . "]"
            );
            $this->assertNotFalse(
                strpos($message, $expectedMessage),
                "Expected the message to contain [$expectedMessage]. Received: [$message]"
            );
        }
    }

    private function allCombinations(array $data)
    {
        $results = array(array());
        foreach ($data as $element) {
            foreach ($results as $combination) {
                array_push($results, array_merge(array($element), $combination));
            }
        }
        return $results;
    }

    /**
     * @dataProvider provideEnumAllAvailableRoles
     *
     * @param string $userName the name of the user to be tested.
     * @param string $expectedFile the name of the file that holds the expected
     *                             results of the test.
     * @throws Exception
     */
    public function testEnumAllAvailableRoles($userName, $expectedFile)
    {
        $expected = JSON::loadFile($this->getTestFile($expectedFile));
        $user = XDUser::getUserByUserName($userName);

        $allAvailableRoles = $user->enumAllAvailableRoles();
        $this->assertEquals($expected, $allAvailableRoles);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function provideEnumAllAvailableRoles()
    {
        $results = array(
            array(self::CENTER_DIRECTOR_USER_NAME, 'center_director_all_available_roles.json'),
            array(self::CENTER_STAFF_USER_NAME, 'center_staff_all_available_roles.json'),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, 'principal_user_all_available_roles.json'),
            array(self::NORMAL_USER_USER_NAME, 'normal_user_all_available_roles.json')
        );

        // Retrieve all acls except for 'pub' and convert them to an array of
        // acl names.
        $acls = array_map(
            function (Acl $acl) {
                return $acl->getName();
            },
            array_filter(
                Acls::getAcls(),
                function (Acl $acl) {
                    return $acl->getName() !== self::PUBLIC_ACL_NAME;
                }
            )
        );

        // retrieve all possible combinations of the acls that were retrieved.
        $allAclCombinations = $this->allCombinations($acls);

        // Here we setup a user per acl combination
        foreach ($allAclCombinations as $aclCombination) {
            // replace the hardcoded array on rhs of || with a call to
            // Acls::getAclsForAclType when it get's merged in.
            if (empty($aclCombination) || count(array_diff($aclCombination, array('mgr', 'dev'))) < 1 ) {
                continue;
            }

            // check if we're in testing in anything but OpenXDMoD, if we are
            // then make sure to not include XSEDE specific acls
            $environment = getenv('TEST_ENV');
            if ($environment === 'xdmod-xsede' &&
                (in_array('po', $aclCombination) || in_array('cc', $aclCombination) || in_array('acl.custom-query-tab', $aclCombination))
            ) {
                continue;
            }

            $user = self::getUser(null, 'Test', 'Acl', 'User', $aclCombination);
            $user->setUserType(self::DEFAULT_USER_TYPE);

            // Save 'um so that we get an id + the db records we need.
            $user->saveUser();


            // check to see if the user has either of the 'center' acls
            $hasCenterDirector = in_array(self::CENTER_DIRECTOR_ACL_NAME, $aclCombination);
            $hasCenterStaff = in_array(self::CENTER_STAFF_ACL_NAME, $aclCombination);

            // and if so then make sure the correct relations get setup.
            if ($hasCenterStaff) {
                $user->setOrganizations(array(self::DEFAULT_CENTER => array('active' => 1, 'primary' => 1)), self::CENTER_STAFF_ACL_NAME);
            }

            if ($hasCenterDirector){
                $user->setOrganizations(array(self::DEFAULT_CENTER => array('active' => 1, 'primary' => 1)), self::CENTER_DIRECTOR_ACL_NAME);
            }

            $userName = $user->getUsername();
            $fileName = implode('_', $aclCombination) . "_acls.json";
            $results []= array(
                $userName,
                $fileName
            );
        }
        return $results;
    }

    /**
     * @dataProvider provideGetMostPrivilegedRole
     * @param string $userName the username of the user to request
     * @param string $expected the expected result
     * @throws Exception
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
            array(self::CENTER_DIRECTOR_USER_NAME, self::CENTER_DIRECTOR_ACL_NAME),
            array(self::CENTER_STAFF_USER_NAME, self::CENTER_STAFF_ACL_NAME),
            array(self::PRINCIPAL_INVESTIGATOR_USER_NAME, self::PRINCIPAL_INVESTIGATOR_ACL_NAME),
            array(self::NORMAL_USER_USER_NAME, self::NORMAL_USER_ACL),
            array(self::PUBLIC_USER_NAME, self::PUBLIC_ACL_NAME)
        );
    }

    /**
     * @dataProvider provideGetAllRoles
     * @param string $userName
     * @param $output
     * @throws Exception
     */
    public function testGetAllRoles($userName, $output)
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
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('acls', $output)
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideGetAllRoles()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile(
                'acls',
                'get_all_roles',
                'input'
            )
        );
    }

    /**
     * @dataProvider provideIsCenterDirectorOfOrganizationValidCenter
     * @param string $userName
     * @param bool $expected
     * @throws Exception
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
     * @param bool $expected
     * @throws Exception
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

    /**
     * @throws Exception
     */
    public function testIsCenterDirectorOfOrganizationNull()
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $actual = $user->isCenterDirectorOfOrganization(null);
        $this->assertEquals(false, $actual);
    }

    /**
     * @throws Exception
     */
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
        $user = self::getUser(null, 'test', 'a', 'user');
        $user->enumCenterDirectorSites();
    }

    /**
     * @dataProvider provideEnumCenterDirectorsSites
     * @param string $userName
     * @param bool $expectedFileName
     * @throws Exception
     */
    public function testEnumCenterDirectorSites($userName, $expectedFileName)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->enumCenterDirectorSites();
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile(
                'acls',
                $expectedFileName
            )
        );
        $this->assertEquals($expected, $actual);

    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideEnumCenterDirectorsSites()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile(
                'acls',
                'enum_center_director_sites',
                'input'
            )
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling enumCenterStaffSites()
     */
    public function testEnumCenterStaffSitesWithUnsavedUserFails()
    {
        $user = self::getUser(null, 'test', 'a', 'User', array('cs', 'usr'), 'cs');
        $user->enumCenterStaffSites();
    }

    /**
     * @dataProvider provideEnumCenterStaffSites
     * @param string $userName
     * @param array $expectedFileName
     * @throws Exception
     */
    public function testEnumCenterStaffSites($userName, $expectedFileName)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->enumCenterStaffSites();
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('acls', $expectedFileName)
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideEnumCenterStaffSites()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'enum_center_staff_sites', 'input')
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling getPrimaryOrganization()
     */
    public function testGetPrimaryOrganizationForUnsavedUserFails()
    {
        $user = self::getUser(null, 'test', 'a', 'user');
        $user->getPrimaryOrganization();
    }

    /**
     * @dataProvider provideGetPrimaryOrganization
     * @param $userName
     * @param $expectedFileName
     * @throws Exception
     */
    public function testGetPrimaryOrganization($userName, $expectedFileName)
    {
        $user = XDUser::getUserByUserName($userName);
        $actual = $user->getPrimaryOrganization();
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('acls', $expectedFileName)
        );
        $this->assertArrayHasKey('value', $expected);

        $this->assertEquals($expected['value'], $actual);
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideGetPrimaryOrganization()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('acls', 'get_primary_organization', 'input')
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This user must be saved prior to calling getOrganizationCollection()
     */
    public function testGetOrganizationCollectionWithUnsavedUserFails()
    {
        $user = self::getUser(null, 'test', 'a', 'user');
        $user->getOrganizationCollection();
    }

    /**
     * @dataProvider provideGetOrganizationCollection
     * @param string $userName
     * @param $expectedFileName
     * @throws Exception
     */
    public function testGetOrganizationCollection($userName, $expectedFileName)
    {
        $expectedData = JSON::loadFile(
            $this->getTestFiles()->getFile('acls', $expectedFileName)
        );
        foreach ($expectedData as $centerStaffOrDirector => $expected) {
            $user = XDUser::getUserByUserName($userName);
            if ($centerStaffOrDirector === 'null') {
                $centerStaffOrDirector = null;
            }
            $actual = $user->getOrganizationCollection($centerStaffOrDirector);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideGetOrganizationCollection()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('acls', 'get_organization_collection', 'input')
        );
    }

    /**
     * @dataProvider provideGetRoleIDFromIdentifierInvalidFails
     * @param string $roleName
     * @throws Exception
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
     * @throws Exception
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

        foreach ($results as $roleName => $expected) {
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

        $user = self::getUser(null, 'test', 'a', 'user');
        $promoter = $user->getPromoter(self::CENTER_DIRECTOR_ACL_NAME, 1);
        $this->assertEquals(-1, $promoter);
    }

    /**
     * @dataProvider provideGetPromoter
     * @param $userName
     * @param $aclData
     * @throws Exception
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
        $user = self::getUser(null, 'test', 'a', 'user');

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

    public function testGetFormalRoleNameNull()
    {
        $expected = 'Public';
        $user = self::getUser(null, 'test', 'a', 'user');
        $actual = $user->_getFormalRoleName(null);
        $this->assertEquals($expected, $actual);
    }

    public function testGetFormalRoleNameEmptyString()
    {
        $expected = 'Public';
        $user = self::getUser(null, 'test', 'a', 'user');
        $actual = $user->_getFormalRoleName('');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider  provideRoleGetParameters
     * @param array $roleSet
     * @param array $centerSet
     * @throws Exception
     */
    public function testRoleGetParameters($roleSet, $centerSet)
    {
        if (!empty($roleSet) && !empty($centerSet)) {
            $user = self::getUser(null, 'test', 'a', 'user', $roleSet);
            $user->setUserType(self::DEFAULT_USER_TYPE);
            $user->saveUser();

            foreach ($roleSet as $role) {
                $user->setOrganizations($centerSet, $role);
            }

            $mostPrivilegedRole = $user->getMostPrivilegedRole();

            $expected = array_keys($centerSet);
            $roles = implode('_', $roleSet);
            $centers = implode('_', array_values($expected));
            $fileName = "$roles-$centers.json";
            $testFilePath = $this->getTestFile($fileName);
            $testFileExists = file_exists($testFilePath) && is_readable($testFilePath);
            if ($testFileExists) {
                $expected = json_decode(file_get_contents($testFilePath));
            }

            $parameters = $mostPrivilegedRole->getParameters();
            $actual = array_values($parameters);

            if (!$testFileExists) {
                file_put_contents($testFilePath, json_encode($actual));
            }

            foreach ($actual as $idx => $item) {
                $this->assertTrue(in_array($item, $expected), "Expected [". json_encode($expected) . "] Received: [" . json_encode($actual) . "]");
            }
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function provideRoleGetParameters()
    {
        $input = JSON::loadFile(
            $this->getTestFile('role_get_parameters.json', self::DEFAULT_PROJECT, 'input')
        );

        $this->assertArrayHasKey('centers', $input);
        $this->assertArrayHasKey('acls', $input);

        $centerPermutations = $this->allCombinations($input['centers']);
        $rolePermutations = $this->allCombinations($input['acls']);

        $results = array();
        foreach($rolePermutations as $rolePermutation) {
            foreach($centerPermutations as $centerPermutation) {
                $centers = array();
                if (!empty($centerPermutation)) {
                    $total = 0;
                    foreach($centerPermutation as $center) {
                        switch($total) {
                            case 0:
                                $centers[$center] = array('primary' => 1, 'active' => 1);
                                break;
                            default:
                                $centers[$center] = array('primary' => 0, 'active'=> 1);
                                break;
                        }
                        $total += 1;
                    }
                }
                $results []= array($rolePermutation, $centers);
            }
        }
        return $results;
    }


    public static function tearDownAfterClass()
    {
        foreach (self::$users as $userName => $user) {
            try {
                $user->removeUser();
            } catch (Exception $e) {
                echo "\nUnable to remove User: $userName\n";
                echo "{$e->getCode()}: {$e->getMessage()}\n{$e->getTraceAsString()}\n";
            }
        }
    }

    /**
     * Retrieve and log a reference to an XDUser instance created with the
     * provided arguments.
     *
     * @param string $username
     * @param string $password
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @param array|null $acls
     * @param string|null $primaryRole
     * @param string|null $email
     * @return XDUser
     */
    private static function getUser($password, $firstName, $middleName, $lastName, array $acls = null, $primaryRole = null, $email = null, $username = null)
    {
        $newUserName = isset($username) ? $username : self::getUserName(self::DEFAULT_TEST_USER_NAME);
        $emailAddress = isset($email) ? $email : "$newUserName" . self::DEFAULT_EMAIL_ADDRESS_SUFFIX;

        if (!isset($acls) && !isset($primaryRole)) {
            $user = new XDUser($newUserName, $password, $firstName, $middleName, $lastName);
        } else {
            $user = new XDUser($newUserName, $password, $emailAddress, $firstName, $middleName, $lastName, $acls, $primaryRole);
        }

        self::$users[$newUserName] = $user;
        return $user;
    }

    private static function getUserName($username)
    {
        while (array_key_exists($username, self::$users)) {
            $suffix = rand(self::MIN_USERS, self::MAX_USERS);
            $username = "$username$suffix";
        }
        return $username;
    }
}
