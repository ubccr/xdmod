<?php

namespace IntegrationTests\Database;

use IntegrationTests\BaseTest;
use CCR\DB;
use CCR\Json;
use \PHPUnit\Framework\TestCase;
use Configuration\XdmodConfiguration;

/**
 * Test the resource names and codes in the database.
 */
class ResourceNamesTest extends BaseTest
{
    private $db;

    public function setup(): void
    {
        $this->db = DB::factory('datawarehouse');
    }

    public function testResourcesNamesValues()
    {
        $actual = $this->db->query('SELECT code, name FROM modw.resourcefact ORDER BY code');

        $this->validateJsonAgainstFile(
            $actual,
            'schema/integration',
            'resource_names.spec'
        );

        # Check expected file
        foreach(self::$XDMOD_REALMS as $realm) {
            $expectedOutputFile = parent::getTestFiles()->getFile('integration/database', 'resource_names', "output/" . strtolower($realm));

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
                $this->markTestSkipped("Generated Expected Output for testResourcesNamesValues: $expectedOutputFile\n");
            }

            $resources = Json::loadFile($expectedOutputFile);
            foreach($resources as $item) {
                $this->assertContains($item, $actual);
            }
        }
    }
}
