<?php
/**
 * Component tests for the Greenlion SQL parser.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2018-01-24
 */

namespace UnitTesting\ETL\SqlParser;

use ETL\Configuration\EtlConfiguration;

class SqlParserTest extends \PHPUnit_Framework_TestCase
{
    // Re-use existing input files
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";
    const TMPDIR = '/tmp/xdmod-etl-sqlparser-test';

    /**
     * Test that the Greenlion SQL parser is working properly. See
     * https://github.com/greenlion/PHP-SQL-Parser
     *
     * @return nothing
     */

    public function testSqlParser()
    {
        // Use existing test files from the EtlConfigurationTest

        @mkdir(self::TMPDIR . '/etl_8.0.0.d', 0755, true);
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_etl_config_8.0.0.json', self::TMPDIR . '/xdmod_etl_config_8.0.0.json');
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/etl_8.0.0.d/maintenance.json', self::TMPDIR . '/etl_8.0.0.d/maintenance.json');

        // Create a fake table definition file so the ETL verification steps don't fail

        @mkdir(self::TMPDIR . '/etl_tables_8.0.0.d', 0755, true);
        file_put_contents(self::TMPDIR . '/etl_tables_8.0.0.d/jobfactstatus.json', '{ "table_definition": {} }');

        // Rather than call the SQL parser directly, use the same methods that an ETL action would use.
        // This requires that we instantiate a class that extends aRdbmsDestinationAction.

        $etlConfig = new EtlConfiguration(self::TMPDIR . '/xdmod_etl_config_8.0.0.json', self::TMPDIR);
        $etlConfig->initialize();
        // The "TableManagement" action is defined in etl.d/maintenance.json
        $options = $etlConfig->getActionOptions('TableManagement');
        $action = forward_static_call(array($options->factory, "factory"), $options, $etlConfig);

        $sql = <<<SQL
SELECT
DISTINCT o.organization_id,
COALESCE(o.amie_name, o.organization_abbrev, o.organization_name) AS short_name,
CASE
  WHEN COALESCE(o.amie_name, o.organization_abbrev) IS NULL THEN o.organization_name
  ELSE COALESCE(o.amie_name, o.organization_abbrev) || ' - ' || o.organization_name
END AS long_name
FROM acct.organizations o, acct.resources r
WHERE o.organization_id = r.organization_id   
AND r.resource_type_id IS NOT NULL
AND r.resource_type_id NOT IN (4, 11)
ORDER BY long_name
SQL;
        $generatedColumnNames = $action->getSqlColumnNames($sql);
        $expectedColumnNames = array(
            'organization_id',
            'short_name',
            'long_name'
        );

        // Cleanup

        unlink(self::TMPDIR . '/xdmod_etl_config_8.0.0.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/maintenance.json');
        rmdir(self::TMPDIR . '/etl_8.0.0.d');
        unlink(self::TMPDIR . '/etl_tables_8.0.0.d/jobfactstatus.json');
        rmdir(self::TMPDIR . '/etl_tables_8.0.0.d');
        rmdir(self::TMPDIR);

        $this->assertEquals($generatedColumnNames, $expectedColumnNames);
    }
} // class SqlParserTest
