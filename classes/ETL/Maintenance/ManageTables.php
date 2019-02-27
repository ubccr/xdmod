<?php
/* ==========================================================================================
 * The ManageTables action does not ingest any data, but manages database tables. This is used to
 * create ancillary tables in the database such as the jobfactstatus table.  Much of the
 * aRdbmsDestinationAction functionality is not used, only manageTable().
 *
 * Note: ManageTables only uses the table_definition from the configuration file.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-12-11
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Maintenance;

use Configuration\Configuration;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\DbModel\Table;
use ETL\aOptions;
use ETL\iAction;
use ETL\aRdbmsDestinationAction;
use Log;
use Exception;

class ManageTables extends aRdbmsDestinationAction implements iAction
{
    // List of ETL Table objects generated from the definition files
    private $etlTableDefinitions = array();

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
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
        // Parse the each table config and set the schema to be our destination schema

        foreach ( $this->options->definition_file_list as $defFile ) {
            if ( isset($this->options->paths->table_definition_dir) ) {
                $defFile = \xd_utilities\qualify_path($defFile, $this->options->paths->table_definition_dir);
            }
            $this->logger->info(sprintf("Parse table definition: '%s'", $defFile));
            $tableConfig = new Configuration(
                $defFile,
                $this->options->paths->base_dir,
                $this->logger
            );
            $tableConfig->initialize();
            $etlTable = new Table(
                $tableConfig->getTransformedConfig(),
                $this->destinationEndpoint->getSystemQuoteChar(),
                $this->logger
            );
            $etlTable->schema = $this->destinationEndpoint->getSchema();
            if ( array_key_exists($etlTable->name, $this->etlDestinationTableList) ) {
                $this->logger->warning(
                    sprintf(
                        "Table definition for '%s' already exists, overriding with file %s",
                        $etlTable->name,
                        $defFile
                    )
                );
            }
            $this->etlDestinationTableList[$etlTable->name] = $etlTable;
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

        foreach ( $this->etlDestinationTableList as $etlTable ) {

            // The methods in aRdbmsDestinationAction were designed to work with a single table at a time
            // and assume that etlDestinationTable is set. Initialize it here in case we want to use any
            // of its methods.

            try {

                // Optionally truncate the destination table
                $this->truncateDestination();

                // Bring the destination table in line with the configuration if necessary.
                $this->manageTable($etlTable, $this->destinationEndpoint);

            } catch ( Exception $e ) {
                $msg = "Error managing ETL table " . $etlTable->getFullName() . ": " . $e->getMessage();
                $this->logAndThrowException($msg);
            }
        }  // foreach ( $this->etlDestinationTables as $etlTable )

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->logger->notice(array('action'       => (string) $this,
                                    'start_time'   => $time_start,
                                    'end_time'     => $time_end,
                                    'elapsed_time' => round($time, 5)
                                  ));
    }  // execute()
}  // class ManageTables
