<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use Configuration\XdmodConfiguration;
use Exception;
use IntegrationTests\BaseTest;

abstract class BaseDatabaseTest extends BaseTest
{
    protected $db;

    /**
     * @throws Exception
     */
    public function setup(): void
    {
        $this->db = DB::factory('datawarehouse');
    }

    /**
     * This function provides a generic way of validating the results of a SQL Query are what is expected.
     * Specifically, it executes `$actualSQLQuery` and validates the results against the schema contained in
     * `$actualSchemaFilename`. Then, for each realm found in `self::$XDMOD_REALMS` it retrieves what the
     * expected output is based on `$expectedFileName`. If the file does not exist the function will generate
     * the expected output and save it in `$expectedFilename`. Finally, it ensures that each expected record
     * is found within the results of `$actualSQLQuery`.
     *
     * @param string $actualSQLQuery         A sql query that will return the current results to be tested.
     * @param string $actualSchemaFileName   A json schema file that describes what form the results from
     *                                       `$actualSQLQuery` should be in.
     * @param string $expectedFileName       The name of the file ( minus extension ) that contains the expected
     *                                       results.
     * @param string $skippedMessage         The message that will be displayed if the test is skipped. This string will
     *                                       formatted with `sprintf` be supplied with a value for
     *                                       `$expectedOutputFile`.
     * @param string $schemaTestGroup        [Optional] Which test group the `$actualSchemaFileName` is located in.
     * @param string $expectedTestGroup      [Optional] Which test group the expected test result files are located in.
     *
     * @throws Exception if unable to load the expected schema file.
     */
    protected function validateDatabaseValues(
        $actualSQLQuery,
        $actualSchemaFileName,
        $expectedFileName,
        $skippedMessage,
        $schemaTestGroup = 'schema/integration',
        $expectedTestGroup = 'integration/database'
    ) {
        $actual = $this->db->query($actualSQLQuery);

        $this->validateJsonAgainstFile(
            $actual,
            $schemaTestGroup,
            $actualSchemaFileName
        );

        # Check expected file
        foreach(self::$XDMOD_REALMS as $realm) {
            $expectedOutputFile = parent::getTestFiles()->getFile($expectedTestGroup, $expectedFileName, "output/" . strtolower($realm));

            # Create missing files/directories
            if(!is_file($expectedOutputFile)) {
                $resourceConversions = $this->db->query('SELECT modw.resourcefact.code, modw.resourcetype.abbrev FROM modw.resourcefact
                INNER JOIN modw.resourcetype ON modw.resourcefact.resourcetype_id=modw.resourcetype.id;');
                $resourceTypes = XdmodConfiguration::assocArrayFactory('resource_types.json', CONFIG_DIR);

                $usedTypes = array();
                foreach($resourceTypes['resource_types'] as $id => $items) {
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
                $this->markTestSkipped(sprintf($skippedMessage, $expectedOutputFile));
            }

            $expectedResults = Json::loadFile($expectedOutputFile);
            foreach($expectedResults as $expected) {
                $this->assertContains($expected, $actual);
            }
        }
    }
}
