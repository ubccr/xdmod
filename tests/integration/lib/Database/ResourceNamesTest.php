<?php

namespace IntegrationTests\Database;

use IntegrationTests\BaseTest;
use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;
use JsonSchema\Validator;
use Configuration\XdmodConfiguration;

/**
 * Test the resource names and codes in the database.
 */
class ResourceNamesTest extends BaseDatabaseTest
{

    public function testResourcesNamesValues()
    {
        $actualSQLQuery = 'SELECT code, name FROM modw.resourcefact ORDER BY code';
        $expectedFilename = 'resource_names';
        $expectedSchemaFileName = "$expectedFilename.spec";
        $skippedMessage = "Generated Expected Output for testResourcesNamesValues: %s\n";

        $this->validateDatabaseValues(
            $actualSQLQuery,
            $expectedSchemaFileName,
            $expectedFilename,
            $skippedMessage
        );
    }
}
