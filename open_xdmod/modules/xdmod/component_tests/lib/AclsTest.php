<?php

namespace ComponentTests;

ini_set('memory_limit', '2048M');

use CCR\Json;
use DataWarehouse\Access\MetricExplorer;
use Models\Services\Acls;
use User\Elements\QueryDescripter;
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
     * The list of QueryDescripter property names that will be tested.
     *
     * @var String[]
     */
    private static $PROPERTY_NAMES = array(
        '_realm_name',
        '_group_by_name',
        '_default_statisticname',
        '_order_id',
        '_show_menu',
        '_disable_menu'
    );

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

        $expected = (bool)$options['enabled'];

        $queryGroup = 'tg_usage';

        $user = XDUser::getUserByUserName($userName);

        $acls = $user->getAcls(true);

        foreach ($acls as $acl) {
            $actual = Acls::hasDataAccess(
                $user,
                $realm,
                $groupBy,
                $statistic,
                $acl
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
                    $acl,
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
                    $acl,
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

        $fileId = $options['file_id'];

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('acls', "get_query_descripters-$fileId")
        );

        $user = XDUser::getUserByUserName($username);

        $roles = $user->getAllRoles();
        foreach ($roles as $role) {
            if (isset($realm) && isset($groupBy) && isset($statistic)) {
                $actual = $this->extractDataFrom(
                    Acls::getQueryDescripters(
                        $user,
                        $realm,
                        $groupBy,
                        $statistic
                    )
                );
            } elseif (isset($realm) && isset($groupBy)) {
                $actual = $this->extractDataFrom(
                    Acls::getQueryDescripters(
                        $user,
                        $realm,
                        $groupBy
                    )
                );
            } elseif (isset($realm)) {
                $actual = $this->extractDataFrom(
                    Acls::getQueryDescripters(
                        $user,
                        $realm
                    )
                );
            } else {
                $actual = $this->extractDataFrom(
                    Acls::getQueryDescripters(
                        $user
                    )
                );
            }

            $this->assertEquals(
                $expected,
                $actual,
                sprintf(
                    self::DEBUG_MSG,
                    self::GET_QUERY_DESCRIPTERS,
                    $user->getUsername(),
                    'tg_usage',
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
     * Extracts the data that is pertinent for testing the output of `getQueryDescripters`
     *
     * @param mixed $queryDescripters
     * @return array
     * @throws \ReflectionException
     */
    private function extractDataFrom($queryDescripters)
    {
        $results = array();

        if (is_array($queryDescripters)) {
            foreach ($queryDescripters as $queryDescripter) {
                if (is_array($queryDescripter)) {
                    $results[] = $this->extractDataFrom($queryDescripter);
                } else {
                    $results[] = $this->extractFromQueryDescripter($queryDescripter);
                }
            }
        } else {
            $results = $this->extractFromQueryDescripter($queryDescripters);
        }

        return $results;
    }

    /**
     * Extracts the pertinent testing information from a single QueryDescripter object.
     *
     * @param QueryDescripter $queryDescripter
     * @return array
     * @throws \ReflectionException
     */
    private function extractFromQueryDescripter(QueryDescripter $queryDescripter)
    {
        $results = array();
        $ref = new \ReflectionClass($queryDescripter);
        $properties = $ref->getProperties();
        foreach ($properties as $property) {
            $name = $property->getName();
            if (in_array($name, self::$PROPERTY_NAMES)) {
                $property->setAccessible(true);
                $results[$name] = $property->getValue($queryDescripter);
            }
        }
        return $results;
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
