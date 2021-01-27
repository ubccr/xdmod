<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;
use JsonSchema\Validator;
use IntegrationTests\BaseTest;
use Configuration\XdmodConfiguration;

/**
 * Test the "shared_jobs" values in the database.
 */
class SharedJobsTest extends BaseTest
{
    private $db;

    private $testFiles;

    public function setUp()
    {
        $this->db = DB::factory('datawarehouse');
        $this->testFiles = new TestFiles(__DIR__ . '/../../../');
    }

    public function testResourcesSharedJobsValues()
    {
        $actual = $this->db->query('SELECT code, shared_jobs FROM modw.resourcefact ORDER BY code');

        # Check spec file
        $schemaObject = Json::loadFile(
            $this->testFiles->getFile('schema/integration', 'shared_jobs.spec', ''),
            false
        );

        $this->validateJson($actual, $schemaObject);

        # Check expected file
        foreach(self::$XDMOD_REALMS as $realm) {
            $expectedOutputFile = $this->testFiles->getFile('integration/database', 'shared_jobs', "output/" . strtolower($realm));

            # Create missing files/directories
            if(!is_file($expectedOutputFile)) {
                $resourceConversions = $this->db->query('SELECT modw.resourcefact.code, modw.resourcetype.abbrev FROM modw.resourcefact
                INNER JOIN modw.resourcetype ON modw.resourcefact.resourcetype_id=modw.resourcetype.id;');
                $resource_types = XdmodConfiguration::assocArrayFactory('resource_types.json', CONFIG_DIR);

                $usedTypes = array();
                foreach($resource_types['resource_types'] as $id => $items) {
                    foreach($items['realms'] as $rlm) {
                        if ($rlm == ucfirst($realm)) {
                            array_push($usedTypes, $id);
                        }
                    }
                }

                $usedCodes = array();
                foreach($usedTypes as $type) {
                    foreach($resourceConversions as $resource) {
                        if ($resource['abbrev'] == $type) {
                            array_push($usedCodes, $resource['code']);
                        }
                    }
                }

                $newFile = array();
                foreach($usedCodes as $code) {
                    foreach($actual as $item) {
                        if ($code == $item['code']){
                            array_push($newFile, $item);
                        }
                    }
                }

                $filePath = dirname($expectedOutputFile);
                if (!is_dir($filePath)){
                    mkdir($filePath);
                }
                file_put_contents($expectedOutputFile, json_encode($newFile, JSON_PRETTY_PRINT) . "\n");
                $this->markTestSkipped("Generated Expected Output for testResourcesSharedJobsValues testGetMenus: $expectedOutputFile\n");
            }

            $shared_jobs = Json::loadFile($expectedOutputFile);
            foreach($shared_jobs as $item) {
                $this->assertContains($item, $actual);
            }
        }
    }
}
