<?php

namespace ComponentTests;

use CCR\DB;
use CCR\Json;
use Models\Acl;
use Models\Services\Users;
use ReflectionClass;
use IntegrationTests\TestHarness\UserHelper;
use \XDUser;
use Models\Services\Acls;
use \Exception;
use IntegrationTests\TestHarness\TestFiles;

/**
 * modify the isDeveloper function.
 * @group skip
 **/
class XDUserTest extends BaseTest
{
    private static $users = [];

    /**
     * @dataProvider provideGetUserByUserName
     * @param string $userName the name of the user to be requested.
     * @param string $expectedFile the name of the file that holds the expected
     *                             results.
     * @throws Exception
     */
    public function testGetUserByUserName($userName, $expectedFile): void
    {
        $user = XDUser::getUserByUserName($userName);
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('acls', $expectedFile)
        );
        $actual = json_decode(json_encode($user), true);

        if ($expected['_password'] !== null) {
            // The test user accounts intentionally have the password identical
            // to the username.

            $verified = password_verify($userName, $actual['_password']);
            // If we were not able to verify the password via the new method, fall back to md5.
            if (!$verified) {
                $verified = md5($userName) === $actual['_password'];
            }
            $this->assertTrue($verified, "Unable to verify the password for: $userName");
        }

        // Compare only keys that we care about, remove all others.
        $keyList = ['_username', '_email', '_firstName', '_middleName', '_lastName', '_roles', '_acls', 'name', 'display', 'enabled'];

        $actual = $this->arrayFilterKeysRecursive($keyList, $actual);
        $expected = $this->arrayFilterKeysRecursive($keyList, $expected);

        $this->assertEquals($expected, $actual);
    }

    public function provideGetUserByUserName()
    {
        return [[self::PUBLIC_USER_NAME, 'public_user'], [self::CENTER_STAFF_USER_NAME, 'center_staff'], [self::CENTER_DIRECTOR_USER_NAME, 'center_director-update_enumAllAvailableRoles'], [self::PRINCIPAL_INVESTIGATOR_USER_NAME, 'principal-update_enumAllAvailableRoles'], [self::NORMAL_USER_USER_NAME, 'normal_user']];
    }

    public function testGetPublicUser(): void
    {
        $user = XDUser::getPublicUser();
        $this->assertNotNull($user);
    }

    public function testPublicUserIsPublicUser(): void
    {
        $user = XDUser::getPublicUser();
        $this->assertTrue($user->isPublicUser());
    }

    /**
     * @throws Exception
     */
    public function testNonPublicUserIsNotPublicUser(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $this->assertFalse($user->isPublicUser());
    }

    /**
     * @throws Exception
     */
    public function testIsDeveloperInvalid(): void
    {
        $user = XDUser::getUserByUserName(self::PUBLIC_USER_NAME);

        $this->assertFalse($user->isDeveloper());
    }

    /**
     * @throws Exception
     */
    public function testIsManagerInvalid(): void
    {
        $user = XDUser::getUserByUserName(self::PUBLIC_USER_NAME);

        $this->assertFalse($user->isManager());
    }

    public function testGetTokenAsPublic(): void
    {
        $user = XDUser::getPublicUser();

        $token = $user->getToken();
        $this->assertEquals('', $token);
    }

    /**
     * @throws Exception
     */
    public function testGetTokenAsNonPublic(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $token = $user->getToken();
        $this->assertNotEquals('', $token);
    }

    public function testGetTokenExpirationAsPublic(): void
    {
        $user = XDUser::getPublicUser();

        $expiration = $user->getTokenExpiration();
        $this->assertEquals('', $expiration);
    }

    /**
     * @throws Exception
     */
    public function testGetTokenExpirationAsNonPublic(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $expiration = $user->getTokenExpiration();
        $this->assertNotEquals('', $expiration);
    }

    public function testGetRolesPublic(): void
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
    public function testGetRolesInformalEqualsGetAclNames(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $roles = $user->getRoles();
        $acls = $user->getAcls(true);

        $this->assertEquals($roles, $acls);
    }

    /**
     * @throws Exception
     */
    public function testGetRolesFormal(): void
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
    public function testGetRolesCasual(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $roles = $user->getRoles('casual');
        $this->assertNull($roles);
    }

    public function testSetRolesEmpty(): void
    {
        $this->expectExceptionMessage("A user must have at least one role.");
        $this->expectException(Exception::class);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalRoles = $user->getRoles();

        $this->assertTrue(count($originalRoles) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $originalRoles));

        $user->setRoles([]);
        $user->saveUser();
    }

    /**
     * @throws Exception
     */
    public function testGetAcls(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $acls = $user->getAcls();

        $this->assertTrue(count($acls) > 0);

        foreach ($acls as $acl) {
            $class = $acl::class;
            $isAcl = $class === \Models\Acl::class;
            $this->assertTrue($isAcl);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetAclNames(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $acls = $user->getAcls(true);
        $this->assertTrue(count($acls) > 0);
        $this->assertTrue(in_array(self::CENTER_DIRECTOR_ACL_NAME, $acls));
    }

    public function testSetAclsEmpty(): void
    {
        $this->expectExceptionMessage("A user must have at least one acl.");
        $this->expectException(Exception::class);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $originalAcls = $user->getAcls();
        $this->assertTrue(count($originalAcls) > 0);

        $user->setAcls([]);
        $user->saveUser();
    }

    /**
     * @throws Exception
     */
    public function testAddNewAcl(): void
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
    public function testAddExistingAclNoOverwrite(): void
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
    public function testAddExistingAclOverwrite(): void
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
    public function testHasAclExists(): void
    {
        $existingAcl = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertTrue($hasAcl);
    }

    /**
     * @throws Exception
     */
    public function testHasAclNotExists(): void
    {
        $existingAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGATOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertFalse($hasAcl);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsExists(): void
    {
        $acls = [];
        $acls [] = Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $acls [] = Acls::getAclByName('usr');

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertTrue($hasAcls);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsNotExists(): void
    {
        $acls = ['dev', 'mgr'];

        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    /**
     * @throws Exception
     */
    public function testHasAclsPartialExists(): void
    {
        $acls = ['dev', 'cd'];
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcls = $user->hasAcls($acls);
        $this->assertFalse($hasAcls);
    }

    /**
     * @throws Exception
     */
    public function testGetUserByUserNameValid(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $this->assertNotNull($user);
        $this->assertNotNull($user->getUserID());
    }

    public function testGetUserByUserNameInvalid(): void
    {
        $this->expectExceptionMessage("User \"bilbo\" not found");
        $this->expectException(Exception::class);
        XDUser::getUserByUserName("bilbo");
    }

    public function testGetUserByUserNameEmptyString(): void
    {
        $this->expectExceptionMessage("User \"\" not found");
        $this->expectException(Exception::class);
        XDUser::getUserByUserName("");
    }

    public function testGetUserByUserNameNull(): void
    {
        $this->expectExceptionMessage("No username provided");
        $this->expectException(Exception::class);
        XDUser::getUserByUserName(null);
    }

    public function testHasAclWithNonAclTypeShouldThrowException(): void
    {
        $this->expectException(Exception::class);
        $acl = new \StdClass;
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $user->hasAcl($acl);
    }

    /**
     * @throws Exception
     */
    public function testUserIsManager(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $isManager = $user->isManager();
        $this->assertFalse($isManager);
    }

    /**
     * Expect that it should complain about not having a valid user type.
     *
     *
     **/
    public function testCreateUserWithoutUserTypeShouldFail(): void
    {
        $this->expectException(Exception::class);
        $user = self::getUser(null, 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    /**
     * @throws Exception
     */
    public function testCreateUser(): void
    {
        $user = self::getUser(null, 'test', 'a', 'user');

        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(SSO_USER_TYPE);

        $user->saveUser();

        $this->assertNotNull($user->getUserID());
    }

    public function testCreateUserWithNoRoles(): void
    {
        $this->expectExceptionMessage("At least one role must be associated with this user");
        $this->expectException(Exception::class);
        $user = self::getUser(null, 'test', 'a', 'user', []);
        $this->assertEquals('0', $user->getUserID());

        $user->setUserType(SSO_USER_TYPE);

        $user->saveUser();
        $this->assertNotNull($user->getUserID());
    }

    /**
     * Expect that it should complain about there already being a test user.
     *
     *
     **/
    public function testCreateUserWithExistingUserNameShouldFail(): void
    {
        $this->expectException(Exception::class);
        $username = array_keys(self::$users)[count(self::$users) - 1];
        $anotherUser = self::getUser(null, 'test', 'a', 'user', [ROLE_ID_USER], ROLE_ID_USER, null, $username);
        $anotherUser->setUserType(SSO_USER_TYPE);
        $anotherUser->saveUser();
    }

    public function testSavePublicUserShouldFail(): void
    {
        $this->expectException(Exception::class);
        $user = XDUser::getPublicUser();
        $user->saveUser();
    }

    public function testSaveUserWithDefaultUserType(): void
    {
        $this->expectException(Exception::class);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $user->setUserType(0);
        $user->saveUser();
    }

    public function testRemoveUser(): void
    {
        $this->expectExceptionMessageMatches("/User \"([\w\d.]+)\" not found/");
        $this->expectException(Exception::class);
        $user = self::getUser(null, 'Test', 'A', 'User', ['usr']);
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
     *
     **/
    public function testRemovePublicUserShouldFail(): void
    {
        $this->expectException(Exception::class);
        $user = XDUser::getPublicUser();

        $user->removeUser();
    }


    public function testGetUserByIDInvalidUID(): void
    {
        $user = XDUser::getUserByID(self::INVALID_ID);
        $this->assertNull($user);
    }

    public function testGetuserByIDNull(): void
    {
        $user = XDUser::getUserByID(null);
        $this->assertNull($user);
    }

    /**
     * @throws Exception
     */
    public function testSaveUserUpdatePassword(): void
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

    private function allCombinations(array $data)
    {
        $results = [[]];
        foreach ($data as $element) {
            foreach ($results as $combination) {
                array_push($results, array_merge([$element], $combination));
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
    public function testEnumAllAvailableRoles($userName, $expectedFile): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $expected = JSON::loadFile($this->getTestFiles()->getFile('acls', $expectedFile));
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
        $results = [[self::CENTER_DIRECTOR_USER_NAME, 'center_director_all_available_roles'], [self::CENTER_STAFF_USER_NAME, 'center_staff_all_available_roles'], [self::PRINCIPAL_INVESTIGATOR_USER_NAME, 'principal_user_all_available_roles'], [self::NORMAL_USER_USER_NAME, 'normal_user_all_available_roles']];

        // Retrieve all acls except for 'pub' and convert them to an array of
        // acl names.
        $acls = array_map(
            fn(Acl $acl) => $acl->getName(),
            array_filter(
                Acls::getAcls(),
                fn(Acl $acl) => $acl->getName() !== self::PUBLIC_ACL_NAME
            )
        );

        // retrieve all possible combinations of the acls that were retrieved.
        $allAclCombinations = $this->allCombinations($acls);

        // Here we setup a user per acl combination
        foreach ($allAclCombinations as $aclCombination) {
            // replace the hardcoded array on rhs of || with a call to
            // Acls::getAclsForAclType when it get's merged in.
            if (empty($aclCombination) || count(array_diff($aclCombination, ['mgr', 'dev'])) < 1 ) {
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
                $user->setOrganizations([self::DEFAULT_CENTER => ['active' => 1, 'primary' => 1]], self::CENTER_STAFF_ACL_NAME);
            }

            if ($hasCenterDirector){
                $user->setOrganizations([self::DEFAULT_CENTER => ['active' => 1, 'primary' => 1]], self::CENTER_DIRECTOR_ACL_NAME);
            }

            $userName = $user->getUsername();
            $fileName = implode('_', $aclCombination) . "_acls";
            $results []= [$userName, $fileName];
        }
        return $results;
    }

    /**
     * @dataProvider provideGetMostPrivilegedRole
     * @param string $userName the username of the user to request
     * @param string $expected the expected result
     * @throws Exception
     */
    public function testGetMostPrivilegedRole($userName, $expected): void
    {
        $user = XDUser::getUserByUserName($userName);
        $mostPrivilegedRole = $user->getMostPrivilegedRole();
        $this->assertNotNull($mostPrivilegedRole);
        $this->assertEquals($mostPrivilegedRole->getName(), $expected);

    }

    public function provideGetMostPrivilegedRole()
    {
        return [[self::CENTER_DIRECTOR_USER_NAME, self::CENTER_DIRECTOR_ACL_NAME], [self::CENTER_STAFF_USER_NAME, self::CENTER_STAFF_ACL_NAME], [self::PRINCIPAL_INVESTIGATOR_USER_NAME, self::PRINCIPAL_INVESTIGATOR_ACL_NAME], [self::NORMAL_USER_USER_NAME, self::NORMAL_USER_ACL], [self::PUBLIC_USER_NAME, self::PUBLIC_ACL_NAME]];
    }

    /**
     * @dataProvider provideGetAllRoles
     * @param string $userName
     * @param $output
     * @throws Exception
     */
    public function testGetAllRoles($userName, $output): void
    {

        $user = XDUser::getUserByUserName($userName);
        $actual = $user->getAllRoles();
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
     * @param string $userName The name of the user to test
     * @param int $organizationId The organization id to test
     * @param bool $expected Expected value
     * @throws Exception
     */
    public function testIsCenterDirectorOfOrganizationValidCenter($userName, $organizationId, $expected): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $user = XDUser::getUserByUserName($userName);
        $actual = $user->isCenterDirectorOfOrganization($organizationId);
        $this->assertEquals($expected, $actual);
    }

    public function provideIsCenterDirectorOfOrganizationValidCenter()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('acls', 'is_center_director_of_organization', 'input')
        );
    }

    /**
     * @dataProvider provideIsCenterDirectorOfOrganizationInvalidCenter
     * @param string $userName
     * @param bool $expected
     * @throws Exception
     */
    public function testIsCenterDirectorOfOrganizationInvalidCenter($userName, $expected): void
    {
        $invalidOrganizationId = -999;

        $user = XDUser::getUserByUserName($userName);
        $actual = $user->isCenterDirectorOfOrganization($invalidOrganizationId);
        $this->assertEquals($expected, $actual);
    }

    public function provideIsCenterDirectorOfOrganizationInvalidCenter()
    {
        return [[self::CENTER_DIRECTOR_USER_NAME, false], [self::CENTER_STAFF_USER_NAME, false], [self::PRINCIPAL_INVESTIGATOR_USER_NAME, false], [self::NORMAL_USER_USER_NAME, false], [self::PUBLIC_USER_NAME, false]];
    }

    /**
     * @throws Exception
     */
    public function testIsCenterDirectorOfOrganizationNull(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $actual = $user->isCenterDirectorOfOrganization(null);
        $this->assertEquals(false, $actual);
    }

    /**
     * @throws Exception
     */
    public function testIsCenterDirectorOfOrganizationEmptyString(): void
    {
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);
        $actual = $user->isCenterDirectorOfOrganization("");
        $this->assertEquals(false, $actual);
    }

    public function provideGetRoleIDFromIdentifierInvalidFails()
    {
        return [[self::INVALID_ACL_NAME], [''], [null]];
    }

    public function provideGetRoleIDFromIdentifier()
    {
        return [[self::CENTER_DIRECTOR_ACL_NAME], [self::CENTER_STAFF_ACL_NAME], [self::PRINCIPAL_INVESTIGATOR_ACL_NAME], [self::NORMAL_USER_ACL], [self::PUBLIC_ACL_NAME]];
    }

    /**
     * @dataProvider provideGetFormalRoleName
     * @param string $roleName
     * @param string $expected
     */
    public function testGetFormalRoleName($roleName, $expected): void
    {
        $user = self::getUser(null, 'test', 'a', 'user');

        $actual = $user->_getFormalRoleName($roleName);
        $this->assertEquals($expected, $actual);
    }

    public function provideGetFormalRoleName()
    {
        return [[self::CENTER_DIRECTOR_ACL_NAME, 'Center Director'], [self::CENTER_STAFF_ACL_NAME, 'Center Staff'], [self::PRINCIPAL_INVESTIGATOR_ACL_NAME, 'Principal Investigator'], [self::NORMAL_USER_ACL, 'User'], [self::PUBLIC_ACL_NAME, 'Public'], [self::INVALID_ACL_NAME, 'Public'], [null, 'Public'], ['', 'Public']];
    }

    public function testGetFormalRoleNameNull(): void
    {
        $expected = 'Public';
        $user = self::getUser(null, 'test', 'a', 'user');
        $actual = $user->_getFormalRoleName(null);
        $this->assertEquals($expected, $actual);
    }

    public function testGetFormalRoleNameEmptyString(): void
    {
        $expected = 'Public';
        $user = self::getUser(null, 'test', 'a', 'user');
        $actual = $user->_getFormalRoleName('');
        $this->assertEquals($expected, $actual);
    }

    public static function tearDownAfterClass(): void
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
     * @throws Exception if there was a problem instantiating the XDUser object.
     */
    private static function getUser($password, $firstName, $middleName, $lastName, array $acls = null, $primaryRole = null, $email = null, $username = null)
    {
        $newUserName = $username ?? self::getUserName(self::DEFAULT_TEST_USER_NAME);

        $user = UserHelper::getUser($newUserName, $password, $firstName, $middleName, $lastName, $acls, $primaryRole, $email);

        self::$users[$newUserName] = $user;
        return $user;
    }

    private static function getUserName($username)
    {
        return sprintf("%s%s", $username, uniqid("", true));
    }
}
