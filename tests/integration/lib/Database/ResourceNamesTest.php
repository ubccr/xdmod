<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;
use Models\Services\Realms;
use JsonSchema\Validator;

/**
 * Test the resource names and codes in the database.
 */
class ResourceNamesTest extends PHPUnit_Framework_TestCase
{
    private $db;

    private $testFiles;

    public function setUp()
    {
        $this->db = DB::factory('datawarehouse');
        $this->testFiles = new TestFiles(__DIR__ . '/../../../');
        $xdmod_realms = array();
        $rawRealms = Realms::getRealms();
        foreach($rawRealms as $item) {
            array_push($xdmod_realms,$item->name);
        }
        $this->xdmod_realms = $xdmod_realms;
    }

    public function testResourcesNamesValues()
    {
        $actual = $this->db->query('SELECT code, name FROM modw.resourcefact ORDER BY code');

        # Check spec file
        $schemaObject = Json::loadFile(
            $this->testFiles->getFile('schema/integration', 'resource_names.spec', ''),
            false
        );
        $validator = new Validator();
        $validator->validate(json_decode(json_encode($actual)), $schemaObject);
        $errors = array();
        foreach ($validator->getErrors() as $err) {
            $errors[] = sprintf("[%s] %s\n", $err['property'], $err['message']);
        }
        $this->assertEmpty($errors, implode("\n", $errors) . "\n" . json_encode($actual, JSON_PRETTY_PRINT));

        # Check expected file
        foreach($this->xdmod_realms as $realm) {
            $expectedOutputFile = $this->testFiles->getFile('integration/database', 'resource_names', "output/$realm");

            # Create missing files/directories
            if(!is_file($expectedOutputFile)) {
                $resourceConversions = $this->db->query('SELECT modw.resourcefact.code, modw.resourcetype.abbrev FROM modw.resourcefact
                INNER JOIN modw.resourcetype ON modw.resourcefact.resourcetype_id=modw.resourcetype.id;');
                $resource_types = Json::loadFile(__DIR__ . "/../../../../configuration/resource_types.json", false);

                $usedTypes = array();
                foreach($resource_types->resource_types as $id => $items) {
                    foreach($items->realms as $rlm) {
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
                echo "Generated Expected Output for testResourcesNamesValues: $expectedOutputFile\n";
            }

            $resources = Json::loadFile($expectedOutputFile);
            foreach($resources as $item) {
                $this->assertContains($item, $actual);
            }
        }
    }
}
