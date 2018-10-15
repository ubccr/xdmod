<?php

namespace ComponentTests;

use CCR\DB;
use CCR\Json;
use Models\Acl;
use Models\Services\Users;
use ReflectionClass;
use TestHarness\UserHelper;
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
        $keyList = array(
            '_username',
            '_email',
            '_firstName',
            '_middleName',
            '_lastName',
            '_roles',
            '_acls',
            'name',
            'display',
            'enabled'
        );

        $actual = $this->arrayFilterKeysRecursive($keyList, $actual);
        $expected = $this->arrayFilterKeysRecursive($keyList, $expected);

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

        $user->setUserType(SSO_USER_TYPE);

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

        $user->setUserType(SSO_USER_TYPE);

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
        $anotherUser->setUserType(SSO_USER_TYPE);
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
     * @throws Exception if there was a problem instantiating the XDUser object.
     */
    private static function getUser($password, $firstName, $middleName, $lastName, array $acls = null, $primaryRole = null, $email = null, $username = null)
    {
        $newUserName = isset($username) ? $username : self::getUserName(self::DEFAULT_TEST_USER_NAME);

        $user = UserHelper::getUser($newUserName, $password, $firstName, $middleName, $lastName, $acls, $primaryRole, $email, 1);

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
