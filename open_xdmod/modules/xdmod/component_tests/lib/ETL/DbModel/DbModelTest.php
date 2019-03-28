<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace ComponentTests\ETL\DbModel;

// Create a base ETL test class that allows us to run ETL actions and can be reused.

use CCR\Log;
use ETL\DbModel\Table;
use ETL\EtlOverseerOptions;
use ETL\Configuration\EtlConfiguration;

class DbModelTest extends \ComponentTests\ETL\BaseEtlTest
{
    const TMPDIR_PREFIX = 'xdmod-dbmodel-test';
    private static $tmpDir = null;
    private static $etlConfig = null;
    private static $etlOverseerOptions = null;
    private static $endpoint = null;
    private static $testArtifactInputPath = null;
    private static $testArtifactOutputPath = null;

    /**
     * Set up machinery that we will need for these tests.
     *
     * @return Nothing
     */

    public static function setupBeforeClass()
    {
        self::$testArtifactInputPath = realpath(BASE_DIR . '/tests/artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input/');
        self::$testArtifactOutputPath = realpath(BASE_DIR . '/tests/artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output/');

        // The modify_table.json file defines actions to create and modify the table as well was the test schema.
        $configFile = self::$testArtifactInputPath . "/xdmod_etl_config_8.0.0.json";
        self::$etlConfig = new EtlConfiguration($configFile, self::$testArtifactInputPath);
        self::$etlConfig->initialize();
        self::$endpoint = self::$etlConfig->getGlobalEndpoint('utility');

        self::$etlOverseerOptions = new EtlOverseerOptions(array());
    }

    /**
     * Clean up after our tests.
     *
     * @return Nothing
     */

    public static function tearDownAfterClass()
    {
        self::$endpoint->getHandle()->execute('DROP TABLE IF EXISTS `test`.`modify_table_test`');
    }

    /**
     * Create a new table based on a JSON configuration.
     */

    public function testTableCreation()
    {
        // Execute a ManageTables action to create a new table. We are expecting the table does not
        // exist.
        $this->executeEtlAction('db-model-test.create-table', self::$etlConfig, self::$etlOverseerOptions);

        // We can use the global endpoint to discover a table in any schema as log as its name is
        // fully qualified. The action does not currently expose its endpoints.
        $existingTable = new Table(null, self::$endpoint->getSystemQuoteChar());
        $existingTable->discover('test.modify_table_test', self::$endpoint);
        $actual = $existingTable->toStdClass();
        $expected = json_decode(file_get_contents(self::$testArtifactOutputPath . "/create_table.json"));
        $this->assertEquals($expected, $actual, "Create table");

    }

    /**
     * Test the following use cases:
     * 1. Add a new column at the start of the list
     * 2. Add a new column in the middle of the list
     * 3. Delete a column
     * 4. Add a new column after the deleted column
     * 5. Rename a column
     * 6. Add a new column after the renamed column
     * 7. Swap the order of awarded and recommended
     */

    public function testTableModification()
    {
        // Execute a ManageTables action to modify the table and then discover the table to comapre
        // the result to the expected value.
        $this->executeEtlAction('db-model-test.modify-table', self::$etlConfig, self::$etlOverseerOptions);

        // We can use the global endpoint to discover a table in any schema as log as its name is
        // fully qualified. The action does not currently expose its endpoints.
        $modifiedTable = new Table(null, self::$endpoint->getSystemQuoteChar());
        $modifiedTable->discover('test.modify_table_test', self::$endpoint);
        $actual = $modifiedTable->toStdClass();
        $expected = json_decode(file_get_contents(self::$testArtifactOutputPath . "/modify_table.json"));
        $this->assertEquals($expected, $actual, "Modify table");
    }

    /**
     * Test the following use cases:
     * 1. Reorder the first column in the table
     * 2. Reorder the last 2 columns in the table
     * 3. Reorder columns in the middle of the table
     * 4. Alter the definition of one of the re-ordered columns
     */

    public function testReorderTableColumns()
    {
        // Execute a ManageTables action to reorder columns and then discover the table to comapre
        // the result to the expected value.
        $this->executeEtlAction('db-model-test.reorder-table-columns', self::$etlConfig, self::$etlOverseerOptions);

        // We can use the global endpoint to discover a table in any schema as log as its name is
        // fully qualified. The action does not currently expose its endpoints.
        $modifiedTable = new Table(null, self::$endpoint->getSystemQuoteChar());
        $modifiedTable->discover('test.modify_table_test', self::$endpoint);
        $actual = $modifiedTable->toStdClass();
        $expected = json_decode(file_get_contents(self::$testArtifactOutputPath . "/reorder_table_columns.json"));
        $this->assertEquals($expected, $actual, "Reorder table columns");
    }

    /**
     * Test the following use cases:
     * 1. Rename and reorder a column at the same time
     */

    public function testSimultaneousRenameAndReorderColumns()
    {
        // Execute a ManageTables action to reorder columns and then discover the table to comapre
        // the result to the expected value.
        $this->executeEtlAction('db-model-test.rename-and-reorder-table-column', self::$etlConfig, self::$etlOverseerOptions);

        // We can use the global endpoint to discover a table in any schema as log as its name is
        // fully qualified. The action does not currently expose its endpoints.
        $modifiedTable = new Table(null, self::$endpoint->getSystemQuoteChar());
        $modifiedTable->discover('test.modify_table_test', self::$endpoint);
        $actual = $modifiedTable->toStdClass();
        $expected = json_decode(file_get_contents(self::$testArtifactOutputPath . "/rename_and_reorder_table_column.json"));
        $this->assertEquals($expected, $actual, "Rename and reorder table columns");
    }

    /**
     * Test the following use cases:
     * 1. Modify a table where the definition has variations in case that are typically normalized
     *    by MySQL.
     */

    public function testNormalizationOfTableDefinition()
    {
        // Create a baseline table and then test normalization of DbModel values (e.g., nothing
        // should be changed).
        $this->executeEtlAction('db-model-test.create-baseline-normalized-table-definition', self::$etlConfig, self::$etlOverseerOptions);
        $this->executeEtlAction('db-model-test.test-normalized-table-definition', self::$etlConfig, self::$etlOverseerOptions);
        // We can use the global endpoint to discover a table in any schema as log as its name is
        // fully qualified. The action does not currently expose its endpoints.
        $modifiedTable = new Table(null, self::$endpoint->getSystemQuoteChar());
        $modifiedTable->discover('test.normalize_table_test', self::$endpoint);
        $actual = $modifiedTable->toStdClass();
        $expected = json_decode(file_get_contents(self::$testArtifactOutputPath . "/normalized_table_definition.json"));
        $this->assertEquals($expected, $actual, "Table definition normalization");
    }
}
