<?php

namespace IntegrationTests\Controllers;

use CCR\DB;
use CCR\Json;

class UserOrganizationTest extends BaseUserAdminTest
{

    private $config;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->config = json_decode(file_get_contents(__DIR__ . '/../../../ci/testing.json'), true);
    }


    /**
     * Test that the organization update on login is functioning properly.
     *
     * @dataProvider provideOrganizationUpdateOnLogin
     *
     * @param array $options the options that will control how the test is executed.
     * @throws \Exception if there is a problem authenticating w/ XDMoD
     */
    public function testOrganizationUpdateOnLogin(array $options)
    {
        $centerRelatedAcls = array('cd', 'cs');

        $user = $options['user'];
        $newOrganization = $options['organization'];

        $this->createTestOrganization($newOrganization);

        if (is_string($user)) {
            $userName = $this->config['role'][$user]['username'];
            $userGroup = 1;
            $userCenterRoles = array_intersect($centerRelatedAcls, array($user));

            $this->helper->authenticate($user);
        } else {
            $this->createUser($user);
            $userName = $user['username'];
            $userGroup = $user['user_type'];
            $userAcls = isset($user['acls']) ? $user['acls'] : array();
            $userCenterRoles = array_intersect($centerRelatedAcls, array_keys($userAcls));

            $this->helper->authenticateDirect($userName, $userName);
        }

        $userId = $this->retrieveUserId($userName, $userGroup);

        $requestedProperties = array(
            'institution',
            'assigned_user_id',
            'acls',
            'email_address',
            'user_type'
        );

        $currentUserInfo = $this->retrieveUserProperties($userId, $requestedProperties);

        $this->assertCount(
            count($requestedProperties),
            $currentUserInfo,
            sprintf(
                "Unable to retrieve properties for %s\n\tRequested: %s\n\tFound:           %s",
                $userName,
                json_encode($requestedProperties),
                json_encode($currentUserInfo)
            )
        );

        $originalOrganization = $currentUserInfo['institution'];
        $currentPerson = $currentUserInfo['assigned_user_id'];
        $originalAcls = array_keys($currentUserInfo['acls']);
        $emailAddress = $currentUserInfo['email_address'];
        $userType = $currentUserInfo['user_type'];

        $this->updatePersonOrganization($currentPerson, $newOrganization);

        $this->helper->logout();

        // Log back in to trigger the organization update.
        if (is_string($user)) {
            $this->helper->authenticate($user);
        } else {
            $this->helper->authenticateDirect($userName, $userName);
        }

        $updatedOrganization = $this->retrieveUserProperties($userId, array('institution'));

        // Ensure that the organization update was successful.
        $this->assertEquals(
            $newOrganization,
            $updatedOrganization,
            sprintf(
                "Unable to update organization for %s\n\tExpected: %s\n\tFound:     %s",
                $userName,
                $newOrganization,
                $updatedOrganization
            )
        );

        // Now go ahead and reset the Person's organization back the original value.
        $this->updatePersonOrganization($currentPerson, $originalOrganization);

        $this->helper->logout();

        if (is_string($user)) {
            $this->helper->authenticate($user);
        } else {
            $this->helper->authenticateDirect($userName, $userName);
        }

        $finalOrganization = $this->retrieveUserProperties($userId, array('institution'));

        $this->assertEquals(
            $originalOrganization,
            $finalOrganization,
            sprintf(
                "Unable to reset Organization for %s\n\tRequested: %s\n\tFound:     %s",
                $userName,
                $originalOrganization,
                $finalOrganization
            )
        );

        // If the user had center related roles we have a few more things to check.
        if (count($userCenterRoles) > 0) {
            $currentUserAcls = array_keys($this->retrieveUserProperties($userId, array('acls')));
            $missingAcls = array_diff($originalAcls, $currentUserAcls);

            // They should have had their center related role revoked, so they should have at least
            // one acl missing.
            $this->assertTrue(count($missingAcls) > 0);
            $this->assertTrue(count($missingAcls) === count($userCenterRoles));

            $userAcls = array_reduce(
                $originalAcls,
                function ($carry, $item) {
                    $carry[$item] = array();
                    return $carry;
                },
                array()
            );

            $this->updateUser(
                $userId,
                $emailAddress,
                $userAcls,
                $currentPerson,
                $originalOrganization,
                $userType
            );
        }

        $this->deleteTestOrganization($newOrganization);
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideOrganizationUpdateOnLogin()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_organization', 'user_organization_update_on_login', 'input')
        );
    }

    /**
     * Update the person identified by `$personId` so that they are associated with the organization
     * identified by `$organizationId`. This is done via direct manipulation of the database.
     *
     * NOTE: This updating would normally occur during ingestion but to keep the tests as simplistic
     * as possible this helper function has been added.
     *
     * @param int $personId       the person who is to have their organization association updated.
     * @param int $organizationId the id of the organization with whom the person is to be
     *                            associated with.
     *
     * @return bool
     *
     * @throws \Exception if there is a problem retrieving a db connection.
     * @throws \Exception if there is a problem executing a sql statement.
     */
    private function updatePersonOrganization($personId, $organizationId)
    {
        $query = "UPDATE modw.person SET organization_id = :organization_id WHERE id = :person_id";
        $params = array(
            ':person_id' => $personId,
            ':organization_id' => $organizationId
        );

        $db = DB::factory('database');

        $rows = $db->execute($query, $params);

        return count($rows) > 0;
    }

    /**
     *
     * @param int $organizationId
     * @return bool
     * @throws \Exception
     */
    public function createTestOrganization($organizationId)
    {
        $query = "INSERT INTO modw.organization (id, abbrev, name) VALUES (:organization_id, :organization_abbrev, :organization_name)";
        $params = array(
            ':organization_id' => $organizationId,
            ':organization_abbrev' => "ORG_$organizationId",
            ':organization_name' => "Organization $organizationId"
        );

        $db = DB::factory('database');

        $rows = $db->execute($query, $params);

        return count($rows) > 0;
    }

    /**
     * @param int $organizationId
     * @return bool
     * @throws \Exception
     */
    public function deleteTestOrganization($organizationId)
    {
        $query = "DELETE FROM modw.organization WHERE id = :organization_id";
        $params = array(
            ':organization_id' => $organizationId
        );

        $db = DB::factory('database');

        $rows = $db->execute($query, $params);

        return count($rows) > 0;
    }
}
