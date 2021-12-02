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

class ManageAggregateTables extends ManageTables
{
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
        foreach ($this->options->definition_file_list as $defFile) {
            if (isset($this->options->paths->table_definition_dir)) {
                $defFile = \xd_utilities\qualify_path($defFile, $this->options->paths->table_definition_dir);
            }

            $this->logger->notice(sprintf("Parse table definition: '%s'", $defFile));

            $tableConfig = Configuration::factory(
                $defFile,
                $this->options->paths->base_dir,
                $this->logger
            );

            // If the etlDestinationTable is set, it will not be generated in aRdbmsDestinationAction
            if (! isset($tableConfig->table_definition)) {
                $this->logAndThrowException("Definition file does not contain a 'table_definition' key");
            }

            // This action only supports 1 destination table so use the first one and log a warning if
            // there are multiple.

            if (is_array($tableConfig->table_definition)) {
                if (count($tableConfig->table_definition) > 1) {
                    $this->logger->warning(sprintf(
                        "%s does not support multiple ETL destination tables, using first table",
                        $this
                    ));
                }
                $tableDefinition = $tableConfig->table_definition;
                $tableConfig->table_definition = array_shift($tableDefinition);
            }

            if (! is_object($tableConfig->table_definition)) {
                $this->logAndThrowException("Table definition must be an object.");
            }

            $this->logger->debug("Create ETL destination aggregation table object");
            $this->etlDestinationTable = new AggregationTable(
                $tableConfig->table_definition,
                $this->destinationEndpoint->getSystemQuoteChar(),
                $this->logger
            );

            $this->etlDestinationTable->schema = $this->destinationEndpoint->getSchema();

            if (isset($this->options->table_prefix) &&
                $this->options->table_prefix != $this->etlDestinationTable->table_prefix) {
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
    }

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $time_start = microtime(true);
        $this->initialize($etlOverseerOptions);

        foreach ($this->options->aggregation_units as $aggregationUnit) {
            foreach ($this->etlDestinationTableList as $etlTable) {
                try {
                    // The aggregation unit must be set for the AggregationTable
                    $etlTable->aggregation_unit = $aggregationUnit;
                    $this->variableStore->overwrite('AGGREGATION_UNIT', $aggregationUnit);

                    // Optionally truncate the destination table
                    $this->truncateDestination();

                    $substitutedEtlAggregationTable = $etlTable->copyAndApplyVariables($this->variableStore);
                    $this->manageTable($substitutedEtlAggregationTable, $this->destinationEndpoint);

                } catch (Exception $e) {
                    $msg = "Error managing ETL table " . $etlTable->getFullName() . ": " . $e->getMessage();
                    $this->logAndThrowException($msg);
                }
            }
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->logger->notice(array('action'       => (string) $this,
                                    'start_time'   => $time_start,
                                    'end_time'     => $time_end,
                                    'elapsed_time' => round($time, 5)
                                  ));
    }
}
