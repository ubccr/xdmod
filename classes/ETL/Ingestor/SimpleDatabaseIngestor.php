<?php
/* ==========================================================================================
 * Simple ingestor for bringing over all columns (or a subset) from a single
 * destination table to the target table with no modification.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-18
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Ingestor;

use ETL\EtlOverseerOptions;
use ETL\iAction;

class SimpleDatabaseIngestor extends pdoIngestor implements iAction
{

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        if ( ! isset($this->parsedDefinitionFile->source_table) ) {
            $this->logAndThrowException("source_table not found in definition file");
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see pdoIngestor::getSourceQueryString()
     * ------------------------------------------------------------------------------------------
     */

    public function getSourceQueryString()
    {
        $tableName = $this->parsedDefinitionFile->source_table;

        $sourceTable = $this->sourceEndpoint->getSchema(true) . "." . $this->sourceEndpoint->quoteSystemIdentifier($tableName);
        $sourceColumnNames = $this->sourceEndpoint->getTableColumnNames($tableName);

        // If subset of the columns was requested (the "columns" key is an array) or if a mapping is
        // being provided (the "columns" key is an object), ensure that all requested columns are present
        // in the source table.

        $optionalColumnNames = null;

        if ( isset($this->parsedDefinitionFile->source_columns) ) {
            $optionalColumnNames = ( is_array($this->parsedDefinitionFile->source_columns)
                                     ? $this->parsedDefinitionFile->source_columns
                                     : array_values((array) $this->parsedDefinitionFile->source_columns) );
            $missing = array_diff($optionalColumnNames, $sourceColumnNames);
            if ( 0 != count($missing) ) {
                $msg = "Requested colums missing from table $sourceTable: " . implode(",", $missing);
                $this->logAndThrowException($msg);
            }

            // If the optional columns value is an object it is a mapping between the source table columns
            // and the destination table columns. Construct the columns using "AS".

            if ( is_object($this->parsedDefinitionFile->source_columns) ) {
                $optionalColumnNames = array();
                foreach ($this->parsedDefinitionFile->source_columns as $dest => $src) {
                    $optionalColumnNames[] = "$src AS $dest";
                }
            }

        }  // if ( isset($this->options->columns) )

        // Copy all columns or a subset, as requested

        $columns = ( isset($this->parsedDefinitionFile->source_columns) ? $optionalColumnNames : $sourceColumnNames );

        $destColumnNames = array();

        $sourceQuery = "SELECT " . implode(", ", $columns) . " FROM $sourceTable";

        return $sourceQuery;

    }  // getSourceQuery()
}  // class SimpleDatabaseIngestor
