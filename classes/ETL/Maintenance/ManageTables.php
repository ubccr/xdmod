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

use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\DbEntity\Table;
use ETL\aOptions;
use ETL\iAction;
use ETL\aRdbmsDestinationAction;
use \Log;

class ManageTables extends aRdbmsDestinationAction
implements iAction
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
        } else if ( 0 == count($options->definition_file_list) ) {
            $msg = "definition_file_list must contain at least one element";
            $this->logAndThrowException($msg);
        }

        // aRdbmsDestinationAction::__construct() expects the definition file to be set, but we are
        // handling that manually during initialization.

        $list = $options->definition_file_list;
        $options->definition_file = current($list);

        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize data required to perform the action.  Since this is an action of a target database
     * we must parse the definition of the target table.
     *
     * @throws Exception if any query data was not
     * int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize()
    {
        if ( $this->isInitialized() ) {
            return;
        }

        // We are not calling parent::initialize() because aRdbmsDestinationAction will attempt to
        // instantiate a Table object if $this->etlDestinationTable is not set.

        // Parse the each table config and set the schema to be our destination schema

        foreach ( $this->options->definition_file_list as $defFile ) {
            $defFile = $this->options->applyBasePath("paths->definition_file_dir", $defFile);
            $this->logger->info("Parse table config: '" . $defFile . "'");
            $etlTable = new Table($defFile,
                                  $this->destinationEndpoint->getSystemQuoteChar(),
                                  $this->logger);
            $etlTable->setSchema($this->destinationEndpoint->getSchema());
            $this->etlDestinationTableList[$etlTable->getName()] = $etlTable;
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOptions)
    {
        $this->etlOverseerOptions = $etlOptions;

        $time_start = microtime(true);

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
