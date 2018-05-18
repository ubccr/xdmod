<?php

namespace ComponentTests;

ini_set('memory_limit', '2048M');

use CCR\Json;
use DataWarehouse\Access\MetricExplorer;
use Models\Services\Acls;
use XDUser;

class AclsTest extends BaseTest
{
    const GET_QUERY_DESCRIPTERS = 'getQueryDescripters';
    const HAS_DATA_ACCESS = 'hasDataAccess';
    const DEBUG_MSG = <<<TXT

-----------------------------------------
AclsTest - %s
User:            %s
    Query Group: %s
    Realm:       %s
    Group By:    %s
    Statistic:   %s
    Role:        %s

    aRole::%s: %s
*****************************************    
    Acls::%s:  %s

TXT;
    const CHECK_DATA_ACCESS_DEBUG_MSG = <<<TXT

-------------------------------------
From:      %s
Role:      %s
Realm:     %s
GroupBy:   %s
Statistic: %s

TXT;

    /**
     * Tests that the Acls::hasDataAccess function operates the same as aRole::hasDataAccess. Also
     * checks that MetricExplorer::checkDataAccess returns what we would expect.
     *
     * @dataProvider provideHasDataAccess
     * @param array $options
     * @throws \Exception
     */
    public function testHasDataAccess(array $options)
    {
        $userName = $options['username'];

        $realm = !empty($options['realm']) ? $options['realm'] : null;
        $groupBy = !empty($options['group_by']) ? $options['group_by'] : null;
        $statistic = !empty($options['statistic']) ? $options['statistic'] : null;
        $queryGroup = 'tg_usage';

        $user = XDUser::getUserByUserName($userName);

        $allRoles = $user->getAllRoles();

        foreach ($allRoles as $role) {
            $roleName = $role->getIdentifier();

            $expected = $role->hasDataAccess(
                $queryGroup,
                ucfirst($realm),
                $groupBy,
                $statistic
            );

            $actual = Acls::hasDataAccess(
                $user,
                $realm,
                $groupBy,
                $statistic,
                $roleName
            );

            // We also check the MetricExplorer::checkDataAccess function as it's
            // based on the previous function(s).
            try {
                $authorizedRoles = MetricExplorer::checkDataAccess(
                    $user,
                    $queryGroup,
                    ucfirst($realm),
                    $groupBy,
                    $statistic
                );

                if ($expected) {
                    $this->assertNotEmpty(
                        $authorizedRoles,
                        "Expected MetricExplorer::checkDataAccess to not be empty."
                    );
                } else {
                    $this->assertEmpty(
                        $authorizedRoles,
                        "Expected MetricExplorer::checkDataAccess to be empty."
                    );
                }
            } catch (\Exception $e) {
                // then the user isn't authorized *shrug*.
            }

            // Just some pre-assertion debugging info.
            if ($expected !== $actual) {
                echo sprintf(
                    self::DEBUG_MSG,
                    self::HAS_DATA_ACCESS,
                    $user->getUsername(),
                    $queryGroup,
                    $realm,
                    $groupBy,
                    $statistic,
                    $roleName,
                    self::HAS_DATA_ACCESS,
                    json_encode($expected),
                    self::HAS_DATA_ACCESS,
                    json_encode($actual)
                );
            }

            $this->assertEquals(
                $expected,
                $actual,
                sprintf(
                    "[%s] Expected does not match Actual: %s => %s",
                    $roleName,
                    $expected ? 'true' : "false",
                    $actual ? 'true' : 'false'
                )
            );
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideHasDataAccess()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile(
                'acls',
                'has_data_access',
                'input'
            )
        );
    }

    /**
     * Tests that Acls::getQueryDescripters === aRole->getQueryDescripters.
     *
     * @dataProvider provideGetQueryDescripters
     *
     * @param array $options
     * @throws \Exception if there is a problem retrieving the user.
     */
    public function testGetQueryDescripters(array $options)
    {
        $username = $options['username'];
        $realm = $options['realm'];
        $groupBy = $options['group_by'];
        $statistic = $options['statistic'];

        $user = XDUser::getUserByUserName($username);

        $queryGroupName = 'tg_usage';
        $roles = $user->getAllRoles();
        foreach($roles as $role) {
            if (isset($realm) && isset($groupBy) && isset($statistic)) {
                $expected = $role->getQueryDescripters(
                    $queryGroupName,
                    ucfirst($realm),
                    $groupBy,
                    $statistic
                );
                $actual = Acls::getQueryDescripters(
                    $user,
                    $realm,
                    $groupBy,
                    $statistic
                );
            } elseif(isset($realm) && isset($groupBy)) {
                $expected = $role->getQueryDescripters(
                    $queryGroupName,
                    ucfirst($realm),
                    $groupBy
                );
                $actual = Acls::getQueryDescripters(
                    $user,
                    $realm,
                    $groupBy
                );
            } elseif(isset($realm)) {
                $expected = $role->getQueryDescripters(
                    $queryGroupName,
                    ucfirst($realm)
                );
                $actual = Acls::getQueryDescripters(
                    $user,
                    $realm
                );
            } else {
                $expected = $role->getQueryDescripters(
                    $queryGroupName
                );
                $actual = Acls::getQueryDescripters(
                    $user
                );
            }

            $this->assertEquals(
                $expected,
                $actual,
                sprintf(
                    self::DEBUG_MSG,
                    self::GET_QUERY_DESCRIPTERS,
                    $user->getUsername(),
                    $queryGroupName,
                    $realm,
                    $groupBy,
                    $statistic,
                    $role->getIdentifier(),
                    self::GET_QUERY_DESCRIPTERS,
                    print_r($expected, true),
                    self::GET_QUERY_DESCRIPTERS,
                    print_r($actual, true)
                )
            );
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetQueryDescripters()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('acls', 'get_query_descripters', 'input')
        );
    }
}
