<?php namespace ComponentTests;

use \XDUser;
use Models\Acl;
use Models\Services\Acls;
use \Exception;

/**
 * modify the isDeveloper function.
 * @group skip
 **/
class XDUserTest extends \PHPUnit_Framework_TestCase
{
    const PUBLIC_USER_NAME = 'Public User';
    const PUBLIC_ACL_NAME = 'pub';

    const CENTER_DIRECTOR_USER_NAME = 'centerdirector';
    const CENTER_DIRECTOR_ACL_NAME  = 'cd';

    const PRINCIPAL_INVESTIGTOR_ACL_NAME = 'pi';

    public function testGetPublicUser()
    {
        $user = XDUser::getPublicUser();

        $this->assertTrue($user !== null);
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
        foreach(array_values($originalOrganizations) as $organizationId) {
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
        foreach(array_values($originalOrganizations) as $organizationId) {
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
        foreach($roles as $roleDisplay => $roleAbbrev) {
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
        $self = $this;
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
        $newAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGTOR_ACL_NAME);
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
        $existingAcl = Acls::getAclByName(self::PRINCIPAL_INVESTIGTOR_ACL_NAME);
        $user = XDUser::getUserByUserName(self::CENTER_DIRECTOR_USER_NAME);

        $hasAcl = $user->hasAcl($existingAcl);
        $this->assertFalse($hasAcl);
    }

    public function testHasAclsExists()
    {
        $acls = array();
        $acls []= Acls::getAclByName(self::CENTER_DIRECTOR_ACL_NAME);
        $acls []= Acls::getAclByName('usr');

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

    public function testGetUserByUserNameInvalid()
    {
        $user = XDUser::getUserByUserName("bilbo");
        $this->assertNull($user);
    }

    public function testGetUserByUserNameEmptyString()
    {
        $user = XDUser::getUserByUserName("");
        $this->assertNull($user);
    }

    /**
     * @expectedException Exception
     */
    public function testGetUserByUserNameNull()
    {
        $user = XDUser::getUserByUserName(null);
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

        $this->assertNotNull($primaryRole);
    }

    /**
     * @expectedException Exception
     **/
    public function testGetPrimaryRoleWithNewUser()
    {
        $user = new XDUser('test', null, 'test@ccr.xdmod.org', 'test', 'a', 'user');
        $primaryRole = $user->getPrimaryRole();
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
        $activeRole = $user->getActiveRole();
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
        $anoterUser->setUserType(XSEDE_USER_TYPE);
        $anotherUser->saveUser();
    }

    /**
     * @expectedException Exception
     **/
    public function testCreateUserWithExistingEmailShouldFail()
    {
        $anotherUser = new XDUser('test2', null, 'public@ccr.xdmod.org', 'public', 'a', 'user');
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

    public function testRemoveUser()
    {
        $user = XDUser::getUserByUserName('test');

        $this->assertNotNull($user);

        $user->removeUser();
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
}
