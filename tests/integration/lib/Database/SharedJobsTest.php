<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use \PHPUnit\Framework\TestCase;
use IntegrationTests\BaseTest;
use Configuration\XdmodConfiguration;

/**
 * Test the "shared_jobs" values in the database.
 */
class SharedJobsTest extends BaseDatabaseTest
{

    public function testResourcesSharedJobsValues()
    {
        $actualSQLQuery = 'SELECT code, shared_jobs FROM modw.resourcefact ORDER BY code';
        $expectedFileName = 'shared_jobs';
        $expectedSchemaFileName = "$expectedFileName.spec";
        $skippedMessage = "Generated Expected Output for testResourcesSharedJobsValues testGetMenus: %s\n";

        $this->validateDatabaseValues(
            $actualSQLQuery,
            $expectedSchemaFileName,
            $expectedFileName,
            $skippedMessage
        );
    }
}
