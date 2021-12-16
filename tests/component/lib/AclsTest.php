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
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        $userName = $options['username'];

        $realm = !empty($options['realm']) ? $options['realm'] : null;
        $groupBy = !empty($options['group_by']) ? $options['group_by'] : null;
        $statistic = !empty($options['statistic']) ? $options['statistic'] : null;

        $expected = (bool)$options['enabled'];

        $user = XDUser::getUserByUserName($userName);

        $allRoles = $user->getAllRoles();

        foreach ($allRoles as $role) {
            $actual = Acls::hasDataAccess(
                $user,
                $realm,
                $groupBy,
                $statistic,
                $role
            );

            // We also check the MetricExplorer::checkDataAccess function as it's
            // based on the previous function(s).
            try {
                $authorizedRoles = MetricExplorer::checkDataAccess(
                    $user,
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
                    $realm,
                    $groupBy,
                    $statistic,
                    $role,
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
                    $role,
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
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $username = $options['username'];
        $realm = $options['realm'];
        $groupBy = $options['group_by'];
        $statistic = $options['statistic'];

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
                $options['expected'],
                $actual,
                sprintf(
                    self::DEBUG_MSG,
                    self::GET_QUERY_DESCRIPTERS,
                    $user->getUsername(),
                    $realm,
                    $groupBy,
                    $statistic,
                    $role,
                    self::GET_QUERY_DESCRIPTERS,
                    print_r($options['expected'], true),
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
        $groups = array(
            'fieldofscience',
            'gpucount',
            'jobsize',
            'jobwalltime',
            'nodecount',
            'none',
            'nsfdirectorate',
            'parentscience',
            'person',
            'pi',
            'qos',
            'queue',
            'resource',
            'resource_type',
            'username',
        );

        $stats = array(
            null,
            'active_person_count',
            'active_pi_count',
            'active_resource_count',
            'avg_cpu_hours',
            'avg_gpu_hours',
            'avg_job_size_weighted_by_cpu_hours',
            'avg_job_size_weighted_by_gpu_hours',
            'avg_node_hours',
            'avg_processors',
            'avg_waitduration_hours',
            'avg_wallduration_hours',
            'expansion_factor',
            'job_count',
            'max_processors',
            'min_processors',
            'normalized_avg_processors',
            'running_job_count',
            'started_job_count',
            'submitted_job_count',
            'total_cpu_hours',
            'total_gpu_hours',
            'total_node_hours',
            'total_waitduration_hours',
            'total_wallduration_hours',
            'utilization'
        );

        $base = array(
            'username' => array('principal'),
            'realm' => array('jobs'),
            'group_by' => $groups,
            'statistic' => $stats,
        );

        $testdata = array(
            array(
                array(
                    "username" => "principal",
                    "realm" => "jobs",
                    "group_by" => null,
                    "statistic" => null,
                    "expected" => JSON::loadFile($this->getTestFiles()->getFile('acls', 'get_query_descripters-jobs'))
                )
            )
        );

        foreach(\TestHarness\Utilities::getCombinations($base) as $settings)
        {
            $settings['expected'] = array(
                "_realm_name" => 'Jobs',
                "_group_by_name" => $settings['group_by'],
                "_default_statisticname" => isset($settings['statistic']) ? $settings['statistic'] : 'all',
                "_order_id" => 0,
                "_show_menu" => true,
                "_disable_menu" => false
            );
            $testdata[] = array($settings);
        }

        return $testdata;
    }

    /**
     * This test ensures that `Acls::getDisabledMenus` is working as expected. The data from this
     * function informs the front end code on which query descriptors should be grayed out for the
     * current user. Ex. query descriptors are used to populate the Usage Explorer Tree.
     *
     * @dataProvider provideTestAclsGetDisabledMenus
     *
     * @param array $options
     * @throws \Exception
     */
    public function testAclsGetDisabledMenus(array $options)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        $username = $options['username'];
        $realm = $options['realm'];

        $user = XDUser::getUserByUserName($username);
        $actual = Acls::getDisabledMenus($user, array($realm));

        $fileName = "get_disabled_menus-" . (str_replace(' ', '_', strtolower($username)));
        $expectedFile = $this->getTestFiles()->getFile('acls', $fileName);

        if (!is_file($expectedFile)) {
            file_put_contents($expectedFile, json_encode($actual, JSON_PRETTY_PRINT) . "\n");
            echo "Generated: $expectedFile\n";
            $this->assertTrue(true);
        }

        $expected = JSON::loadFile($expectedFile);

        $this->assertEquals($expected, json_decode(json_encode($actual), true));
    }

    /**
     * @return array|object the contents of `get_disabled_menus.json`
     * @throws \Exception if unable to read / parse the test input file.
     */
    public function provideTestAclsGetDisabledMenus()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('acls', 'get_disabled_menus', 'input')
        );
    }
}
