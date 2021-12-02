<?php
/* ==========================================================================================
 * The ManageAggregateTables action does not ingest any data, but manages the aggregate database
 * tables. Much of the aRdbmsDestinationAction functionality is not used, only manageTable().
 *
 * Note: ManageAggregateTables only uses the table_definition from the configuration file.
 *
 * @author Greg Dean <gmdean@buffalo.edu>
 * @date 2021-12-02
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Maintenance;

use Configuration\Configuration;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aOptions;
use ETL\iAction;
use ETL\aRdbmsDestinationAction;
use Exception;
use Psr\Log\LoggerInterface;
use ETL\DbModel\AggregationTable;

class ManageAggregateTables extends aRdbmsDestinationAction implements iAction
{
    // List of ETL Table objects generated from the definition files
    private $etlTableDefinitions = array();

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        // Set the logger manually since we are not calling the parent constructor chain until
        // later.

        $this->setLogger($logger);

        $requiredKeys = array("definition_file_list");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        if ( ! $options instanceof MaintenanceOptions ) {
            $msg = "Options is not an instance of MaintenanceOptions";
            $this->logAndThrowException($msg);
        }

        // The table definition list must be provided and be an array

        if ( ! is_array($options->definition_file_list) ) {
            $msg = "definition_file_list must be an array";
            $this->logAndThrowException($msg);
        } elseif ( 0 == count($options->definition_file_list) ) {
            $msg = "definition_file_list must contain at least one element";
            $this->logAndThrowException($msg);
        }

        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        parent::initialize($etlOverseerOptions);

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Override aRdbmsDestinationAction::createDestinationTableObjects() because there are
     * multiple definition files referenced by this action and we will be generating a
     * table for each file.
     *
     * @see aRdbmsDestinationAction::createDestinationTableObjects()
     * ------------------------------------------------------------------------------------------
     */

     protected function createDestinationTableObjects()
     {
       foreach ( $this->options->definition_file_list as $defFile ) {
           if ( isset($this->options->paths->table_definition_dir) ) {
               $defFile = \xd_utilities\qualify_path($defFile, $this->options->paths->table_definition_dir);
           }
           $this->logger->notice(sprintf("Parse table definition: '%s'", $defFile));

           $tableConfig = Configuration::factory(
               $defFile,
               $this->options->paths->base_dir,
               $this->logger
           );

           // If the etlDestinationTable is set, it will not be generated in aRdbmsDestinationAction
           if ( ! isset($tableConfig->table_definition) ) {
               $this->logAndThrowException("Definition file does not contain a 'table_definition' key");
           }

           // This action only supports 1 destination table so use the first one and log a warning if
           // there are multiple.

           if ( is_array($tableConfig->table_definition) ) {
               if ( count($tableConfig->table_definition) > 1 ) {
                   $this->logger->warning(sprintf(
                       "%s does not support multiple ETL destination tables, using first table",
                       $this
                   ));
               }
               $tableDefinition = $tableConfig->table_definition;
               $tableConfig->table_definition = array_shift($tableDefinition);
           }

           if ( ! is_object($tableConfig->table_definition) ) {
               $this->logAndThrowException("Table definition must be an object.");
           }

           $this->logger->debug("Create ETL destination aggregation table object");
           $this->etlDestinationTable = new AggregationTable(
               $tableConfig->table_definition,
               $this->destinationEndpoint->getSystemQuoteChar(),
               $this->logger
           );

           $this->etlDestinationTable->schema = $this->destinationEndpoint->getSchema();

           if ( isset($this->options->table_prefix) &&
                $this->options->table_prefix != $this->etlDestinationTable->table_prefix )
           {
               $msg =
                   "Overriding table prefix from " .
                   $this->etlDestinationTable->table_prefix
                   . " to " .
                   $this->options->table_prefix;
               $this->logger->debug($msg);
               $this->etlDestinationTable->table_prefix = $this->options->table_prefix;
           }

           // Aggregation does not support multiple destination tables but we must still populate
           // the table list since it is used by methods upstream.
           $this->etlDestinationTableList[$tableConfig->table_definition->name] = $this->etlDestinationTable;
         }

     }  // createDestinationTableObjects()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $time_start = microtime(true);
        $this->initialize($etlOverseerOptions);

        foreach ( $this->options->aggregation_units as $aggregationUnit ) {
            foreach ( $this->etlDestinationTableList as $etlTable ) {

                try {
                    // The aggregation unit must be set for the AggregationTable
                    $etlTable->aggregation_unit = $aggregationUnit;
                    $this->variableStore->overwrite('AGGREGATION_UNIT', $aggregationUnit);

                    // Optionally truncate the destination table
                    $this->truncateDestination();

                    $qualifiedDestTableName = $etlTable->getFullName();
                    $substitutedEtlAggregationTable = $etlTable->copyAndApplyVariables($this->variableStore);

                    $this->manageTable($substitutedEtlAggregationTable, $this->destinationEndpoint);

                } catch ( Exception $e ) {
                    $msg = "Error managing ETL table " . $etlTable->getFullName() . ": " . $e->getMessage();
                    $this->logAndThrowException($msg);
                }
            }  // foreach ( $this->etlDestinationTables as $etlTable )
        } // foreach ( $this->options->aggregation_units as $aggregationUnit )

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->logger->notice(array('action'       => (string) $this,
                                    'start_time'   => $time_start,
                                    'end_time'     => $time_end,
                                    'elapsed_time' => round($time, 5)
                                  ));
    }  // execute()
}  // class ManageTables
