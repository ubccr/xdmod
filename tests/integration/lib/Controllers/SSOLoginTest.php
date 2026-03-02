<?php

namespace IntegrationTests\Controllers;

use CCR\DB;
use IntegrationTests\TestHarness\PeopleHelper;
use IntegrationTests\TestHarness\XdmodTestHelper;

class SSOLoginTest extends BaseUserAdminTest
{
    /**
     * key to use when providing / retrieving the value that controls whether or not to include the
     * default SSO parameters when conducting an SSO login. By default these keys / values will be
     * included which may cause problems if you're attempting to test the absence of said keys /
     * values
     *
     * @var string
     */
    const INCLUDE_DEFAULT_SSO = 'include_default_sso';

    /**
     * Key to use when providing / retrieving the value that indicates whether or not a user is
     * expected to be created and therefor should be removed after the test is run.
     *
     * @var string
     */
    const REMOVE_USER = 'remove_user';

    /**
     * Key to use when retrieving the username ( 'itname' ) value from SSO properties.
     *
     * @var string
     */
    const SSO_USERNAME = 'itname';

    /**
     * Key to use when providing / retrieving the value that indicates whether or not a user should
     * have the 'sticky' bit set
     *
     * @var string
     */
    const STICKY = 'sticky';

    /**
     * Key to use when providing / retrieving the value that must match the Users organization_id.
     *
     * @var string
     */
    const EXPECTED_ORG = 'expected_org';

    /**
     * Key to use when providing / retrieving the set of acls a user is to be updated with.
     * @var string
     */
    const SET_ACLS = 'acls';

    /**
     * Key to use when providing / retrieving the set of acls a user is expected to have after
     * they have logged in.
     * @var string
     */
    const EXPECTED_ACLS = 'expected_acls';

    /**
     * Key to use when providing / retrieving the info to use when creating a new person.
     * @var string
     */
    const CREATE_PERSON = 'create_person';

    /**
     * Key to use when providing / retrieving the info to be used when creating a new system_account
     * record.
     * @var string
     */
    const CREATE_SYSTEM_ACCOUNT = 'create_system_account';

    /**
     * Key to use when providing / retrieving the information to be used in removing a person.
     * aka. their 'long_name' value.
     *
     * @var string
     */
    const REMOVE_PERSON = 'remove_person';

    /**
     * Key to use when providing / retrieving information to be used in removing a system_account.
     * aka. their 'username' value.
     *
     * @var string
     */
    const REMOVE_SYSTEM_ACCOUNT = 'remove_system_account';

    /**
     * Key to use when providing / retrieving information to be used when setting the current users
     * organization.
     *
     * @var string
     */
    const SET_ORGANIZATION = 'set_organization';

    /**
     * used to test that SSO logins work correctly and the correct user information
     * is reported
     *
     * @dataProvider loginsProvider
     */
    public function testLogin($ssoSettings, $expected, $testOptions = array())
    {
        $helper = new XdmodTestHelper();
        $peopleHelper = new PeopleHelper();

        $includeDefault = \xd_utilities\array_get($testOptions, self::INCLUDE_DEFAULT_SSO, true);
        $removeUser  = \xd_utilities\array_get($testOptions, self::REMOVE_USER, false);
        $sticky = \xd_utilities\array_get($testOptions, self::STICKY, false);
        $expectedOrg = \xd_utilities\array_get($testOptions, self::EXPECTED_ORG, null);
        $setAcls = \xd_utilities\array_get($testOptions, self::SET_ACLS, null);
        $setOrganization = \xd_utilities\array_get($testOptions, self::SET_ORGANIZATION, null);
        $expectedAcls = \xd_utilities\array_get($testOptions, self::EXPECTED_ACLS, null);

        $createPerson = \xd_utilities\array_get($testOptions, self::CREATE_PERSON, null);
        $createSystemAccount = \xd_utilities\array_get($testOptions, self::CREATE_SYSTEM_ACCOUNT, null);

        $removePerson = \xd_utilities\array_get($testOptions, self::REMOVE_PERSON, null);
        $removeSystemAccount = \xd_utilities\array_get($testOptions, self::REMOVE_SYSTEM_ACCOUNT, null);

        $username = \xd_utilities\array_get($ssoSettings, self::SSO_USERNAME, '');

        // If we need to create a person then do it now. Person needs to be created before a system_account
        // so that it can be associated with it.
        if ($createPerson) {
            $peopleHelper->createPerson(
                $createPerson['organization_id'],
                $createPerson['nsfstatuscode_id'],
                $createPerson['first_name'],
                $createPerson['last_name'],
                $createPerson['long_name'],
                $createPerson['short_name']
            );
        }

        // If we need to create a system account then go ahead and do it now.
        if ($createSystemAccount) {
            $this->createSystemAccount(
                $createSystemAccount['person_long_name'],
                $createSystemAccount['resource_id'],
                $createSystemAccount['username']
            );
        }

        // Take care of setting the sticky bit for a user. NOTE: Can only be set for users that exist.
        if ($sticky) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $properties = $this->retrieveUserProperties(
                $userId,
                array(
                    'email_address',
                    'assigned_user_id',
                    'institution'
                )
            );

            $this->updateUser(
                $userId,
                $properties['email_address'],
                array('usr' => array()),
                $properties['assigned_user_id'],
                $properties['institution'],
                SSO_USER_TYPE,
                'true'
            );
        }

        // Perform the SSO login
        $helper->authenticateSSO($ssoSettings, $includeDefault);

        $response = $helper->get('index.php');
        $this->assertEquals(200, $response[1]['http_code']);

        $matches = array();
        preg_match_all('/^(CCR\.xdmod.[a-zA-Z_\.]*) = ([^=;]*);?$/m', $response[0], $matches);
        $jsvariables = array_combine($matches[1], $matches[2]);

        // Ensure that everything is copacetic
        foreach ($expected as $varname => $varvalue) {
            $this->assertEquals($varvalue, $jsvariables[$varname]);
        }

        // Log the SSO User out
        $helper->logout();

        // If specified, update the users set of acls
        if ($setAcls) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $properties = $this->retrieveUserProperties(
                $userId,
                array(
                    'email_address',
                    'assigned_user_id',
                    'institution'
                )
            );
            $this->updateUser(
                $userId,
                $properties['email_address'],
                $setAcls,
                $properties['assigned_user_id'],
                $properties['institution'],
                SSO_USER_TYPE
            );
        }

        // If specified, update this users organization
        if ($setOrganization) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $properties = $this->retrieveUserProperties(
                $userId,
                array(
                    'email_address',
                    'assigned_user_id',
                    'acls'
                )
            );

            $this->updateUser(
                $userId,
                $properties['email_address'],
                $properties['acls'],
                $properties['assigned_user_id'],
                $setOrganization,
                SSO_USER_TYPE,
                false
            );
        }

        // If specified, ensure that this user's organization is as expected.
        if ($expectedOrg) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $actualOrganization = $this->retrieveUserProperties(
                $userId,
                array(
                    'institution'
                )
            );
            $this->assertEquals($expectedOrg, $actualOrganization, "Expected $expectedOrg == $actualOrganization.");
        }

        // If specified, ensure that this users acls are as expected.
        if ($expectedAcls) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $actualAcls = $this->retrieveUserProperties(
                $userId,
                array(
                    'acls'
                )
            );
            $this->assertEquals(array_keys($expectedAcls), array_keys($actualAcls), "Expected Acls !== Actual Acls");
        }

        if ($removeSystemAccount) {
            $this->removeSystemAccount($removeSystemAccount);
        }

        if ($removePerson) {
            $peopleHelper->removePerson($removePerson);
        }

        if ($removeUser) {
            $userId = $this->retrieveUserId($username, SSO_USER_TYPE);
            $this->removeUser($userId, $username);
        }
    }

    public function loginsProvider()
    {
        return array(
            // 0 NU1: New User Test 1
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 1 NU2: New User Test 2
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::REMOVE_USER => true
                )
            ),
            // 2 NU3: New User Test 3
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Alpine',
                    'lastName' => 'Swift',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 3 EU1: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1
                )
            ),

            // 4 EU1
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::STICKY => true,
                    self::EXPECTED_ORG => -1,
                    self::REMOVE_USER => true
                )
            ),

            // 5 E02: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1
                )
            ),

            // 6 E02
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::REMOVE_USER => true
                )
            ),

            // 7 E03: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::SET_ACLS => array('usr' => array(), 'cd' => array()),
                    self::EXPECTED_ORG => -1
                )
            ),

            // 8 E03
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 9 E04: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1
                )
            ),

            // 10 E04
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 11 E05: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1
                )
            ),

            // 12 E05
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 13 E06: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 14 E06
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => -1,
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 15 E07: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::SET_ACLS => array('usr' => array(), 'cd' => array()),

                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 16 E07
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 17 E08: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Unknown, Unknown"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 18 E08
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 19 E09: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 20 E09
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'UB CCR',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 21 E10: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'testy',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => -1,
                    self::SET_ACLS => array('usr' => array(), 'cd' => array()),
                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 22 E10
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 23 E11: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'teper',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctesterson@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::CREATE_PERSON => array(
                        'organization_id' => -1,
                        'nsfstatuscode_id' => 0,
                        'first_name' => 'Testy',
                        'last_name' => 'Person',
                        'long_name' => 'Person, Testy',
                        'short_name' => 'Person, T',
                    ),
                    self::CREATE_SYSTEM_ACCOUNT => array(
                        'person_long_name' => 'Person, Testy',
                        'resource_id' => 5,
                        'username' => 'teper'
                    )
                )
            ),

            // 24 E11
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'testy',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Testy McTesterson"',
                    'CCR.xdmod.ui.mappedPName' => '"Person, Testy"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_PERSON => 'Person, Testy',
                    self::REMOVE_SYSTEM_ACCOUNT => 'teper',
                    self::REMOVE_USER => true
                )
            ),

            // 25 E12: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Alpine',
                    'lastName' => 'Swift',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1
                )
            ),

            // 26 E12
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),

            // 27 E13: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Alpine',
                    'lastName' => 'Swift',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::SET_ORGANIZATION => 2,
                    self::SET_ACLS => array('usr' => array(), 'cd' => array()),
                    self::EXPECTED_ORG => 2
                )
            ),

            // 28 E13
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::EXPECTED_ACLS => array('usr' => array()),
                    self::REMOVE_USER => true
                )
            ),

            // 29 E14: User Creation
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Alpine',
                    'lastName' => 'Swift',
                    'email' => 'alpsw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::SET_ORGANIZATION => 2,
                    self::EXPECTED_ORG => 2
                )
            ),

            // 30 E14
            array(
                array(
                    'itname' => 'testy',
                    'system_username' => 'alpsw',
                    'orgId' => 'Screwdriver',
                    'firstName' => 'Testy',
                    'lastName' => 'McTesterson',
                    'email' => 'tmctestersonw@example.com'
                ),
                array(
                    'CCR.xdmod.ui.username' => "'testy'",
                    'CCR.xdmod.ui.fullName' => '"Alpine Swift"',
                    'CCR.xdmod.ui.mappedPName' => '"Swift, Alpine"',
                    'CCR.xdmod.org_name' => '"Screwdriver"'
                ),
                array(
                    self::EXPECTED_ORG => 1,
                    self::REMOVE_USER => true
                )
            ),
        );
    }

    public function createSystemAccount($personLongName, $resourceId, $username)
    {
        $query = <<<SQL
INSERT INTO modw.systemaccount(person_id, resource_id, username, ts)
SELECT
    p.id ,
    :resource_id as resource_id,
    :username as username,
    NOW() as ts
FROM modw.person p WHERE p.long_name = :person_long_name
SQL;
        $params = array(
            ':person_long_name' => $personLongName,
            ':resource_id'=> $resourceId,
            ':username' => $username
        );

        $db = DB::factory('database');
        $db->execute($query, $params);
    }

    public function removeSystemAccount($username)
    {
        $query = "DELETE FROM modw.systemaccount WHERE username = :username";
        $params = array(
            ':username' => $username
        );
        $db = DB::factory('database');
        $db->execute($query, $params);
    }
}
