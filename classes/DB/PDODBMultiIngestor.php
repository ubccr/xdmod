<?php
/**
 * @author Amin Ghadersohi 7/1/2010
 */

use CCR\DB\MySQLHelper;

class PDODBMultiIngestor implements Ingestor
{
    const MAX_QUERY_ATTEMPTS = 3;

    protected $_destination_db = null;
    protected $_source_db = null;
    protected $_source_query = null;
    protected $_insert_table = null;
    protected $_insert_fields = null;
    protected $_pre_ingest_update_statements;
    protected $_post_ingest_update_statements;
    protected $_delete_statement = null;
    protected $_count_statement = null;
    protected $_logger = null;
    protected $_trackchanges = false;

    /**
     * Helper instance for destination database.
     *
     * @var MySQLHelper
     */
    protected $_dest_helper = null;

    function __construct(
        $dest_db,
        $source_db,
        $pre_ingest_update_statements = array(),
        $source_query,
        $insert_table,
        $insert_fields = array(),
        $post_ingest_update_statements = array(),
        $delete_statement = null,
        $count_statement = null
    ) {
        $this->_destination_db = $dest_db;
        $this->_source_db = $source_db;
        $this->_source_query = $source_query;
        $this->_insert_fields = $insert_fields;
        $this->_insert_table = $insert_table;
        $this->_pre_ingest_update_statements = $pre_ingest_update_statements;
        $this->_post_ingest_update_statements = $post_ingest_update_statements;
        $this->_delete_statement = $delete_statement;
        $this->_count_statement = $count_statement;
        $this->_logger = Log::singleton('null');

        if ($this->_destination_db->_db_engine == 'mysql') {
            $this->_dest_helper = MySQLHelper::factory($this->_destination_db);
        }
    }

    public function enableChangeTracking()
    {
        $this->_trackchanges = true;
    }

    public function ingest()
    {
        $this->_logger->info(
            'Started ingestion for class: ' . get_class($this)
        );
        $time_start = microtime(true);
        $sourceRows = 0;
        $countRowsAffected = 0;

        foreach ($this->_pre_ingest_update_statements as $updateStatement) {
            try {
                $this->_logger->debug(
                    "Pre ingest update statement: $updateStatement"
                );
                $this->_source_db->handle()->prepare(
                    $updateStatement
                )->execute();
            }
            catch (PDOException $e) {
                $this->_logger->info(array(
                    'message'    => $e->getMessage(),
                    'sql'        => $updateStatement,
                    'stacktrace' => $e->getTraceAsString()
                ));
            }
        }

        // The count query must be before the source query for
        // unbuffered queries.
        if ($this->_count_statement != null) {
            $this->_logger->debug('Count query: ' . $this->_count_statement);
            $results   = $this->_source_db->query($this->_count_statement);
            $rowsTotal = $results[0]['row_count'];
        }

        $this->_logger->debug('Source query: ' . $this->_source_query);

        $this->_logger->info(get_class($this) . ': Querying...');

        $query_success = false;
        $n_attempts = 0;

        while ($query_success == false) {
            $n_attempts += 1;

            try {
                $srcStatement = $this->_source_db->handle()->prepare(
                    $this->_source_query
                );
                $srcStatement->execute();
                $query_success = true;
            }
            catch (PDOException $e) {
                if (
                    !isset($srcStatement)
                    || $srcStatement === false
                    || $srcStatement->errorCode() != "40001"
                    || $n_attempts > self::MAX_QUERY_ATTEMPTS
                ) {
                    throw $e;
                }

                $this->_logger->info(
                    get_class($this)
                    . ': Query was cancelled by server with error '
                    . $srcStatement->errorCode() . '. Retrying ' . $n_attempts
                );
            }
        }

        if ($this->_count_statement == null) {
            $rowsTotal = $srcStatement->rowCount();
        }

        $this->_logger->debug("Row count: $rowsTotal");

        $field_sep  = chr(30);
        $line_sep   = chr(29);
        $string_enc = chr(31);

        $this->_destination_db->handle()->prepare(
            'SET FOREIGN_KEY_CHECKS = 0'
        )->execute();

        if ($this->_delete_statement == null) {

            $this->_logger->debug("Truncating table {$this->_insert_table}");
            $this->_destination_db->handle()->prepare(
                "TRUNCATE TABLE {$this->_insert_table}"
            )->execute();
        }
        elseif ($this->_delete_statement !== 'nodelete') {
            $this->_logger->debug(
                'Delete statement: ' . $this->_delete_statement
            );
            $this->_destination_db->handle()->prepare(
                $this->_delete_statement
            )->execute();
        }

        $infile_name = tempnam(
            sys_get_temp_dir(),
            sprintf(
                '%s.data.%s.',
                $this->_insert_table,
                $this->_destination_db->_db_port
            )
        );

        $f = fopen($infile_name, 'w');

        if ($f === FALSE) {
            $this->_logger->debug("Failed to open '$infile_name'");

            $infile_name = sys_get_temp_dir() . "/{$this->_insert_table}.data"
                . $this->_destination_db->_db_port . rand();
            $f = fopen($infile_name, 'w');

            if ($f === FALSE) {
                throw new Exception(
                    get_class($this)
                    . ': tmp file error: could not open file: ' . $infile_name
                );
            }
        }

        $this->_logger->debug("Using temporary file '$infile_name'");

        $exec_output = array();

        while (
            $srcRow = $srcStatement->fetch(
                PDO::FETCH_ASSOC,
                PDO::FETCH_ORI_NEXT
            )
        ) {
            $tmp_values = array();

            foreach ($this->_insert_fields as $insert_field) {
                $tmp_values[$insert_field]
                    = $insert_field == 'order_id'
                    ? $sourceRows
                    : (
                        !isset($srcRow[$insert_field])
                        ? '\N'
                        : (
                            empty($srcRow[$insert_field])
                            ? $string_enc . '' . $string_enc
                            : str_replace('\\', '\\\\', $srcRow[$insert_field])
                        )
                    );
            }

            fwrite($f, implode($field_sep, $tmp_values) . $line_sep);
            $sourceRows++;

            if ($sourceRows !== 0  && $sourceRows % 100000 == 0) {
                $message = sprintf(
                    '%s: Rows Written to File: %d of %d',
                    get_class($this),
                    $sourceRows,
                    $rowsTotal
                );
                $this->_logger->info($message);
            }

            if (
                   $sourceRows !== 0
                && $sourceRows % 250000 == 0
                || $rowsTotal == $sourceRows
            ) {
                // From https://dev.mysql.com/doc/refman/5.5/en/load-data.html
                //
                // The server uses the character set indicated by the
                // character_set_database system variable to interpret the information in
                // the file. **SET NAMES and the setting of character_set_client do not
                // affect interpretation of input**. If the contents of the input file use
                // a character set that differs from the default, it is usually preferable
                // to specify the character set of the file by using the CHARACTER SET
                // clause.

                $load_statement = 'load data local infile \'' . $infile_name
                    . '\' replace into table ' . $this->_insert_table
                    // Only set the character set for XRAS ingestors to minimize potential impact
                    . ( 0 === strpos(get_class($this), 'XRAS') ? ' character set \'utf8mb4\'' : '' )
                    . ' fields terminated by 0x1e optionally enclosed by 0x1f'
                    . ' lines terminated by 0x1d ('
                    . implode(',', $this->_insert_fields) . ')';

                try {
                    $output = array();

                    if ($this->_destination_db->_db_engine !== 'mysql') {
                        throw new Exception(
                            get_class($this)
                            .  ': Unsupported operation: currently only mysql'
                            . 'is supported as destination db. '
                            . $this->_destination_db->_db_engine
                            . ' was passed.'
                        );
                    }

                    $this->_dest_helper->executeStatement($load_statement);

                    fclose($f);
                    $f = fopen($infile_name, 'w');
                }
                catch (Exception $e) {
                    $this->_logger->err(array(
                        'message'    => $e->getMessage(),
                        'stacktrace' => $e->getTraceAsString(),
                        'statement'  => $load_statement,
                    ));
                    return;
                }
            }
        }

        fclose($f);
        unlink($infile_name);

        foreach ($this->_post_ingest_update_statements as $updateStatement) {
            try {
                $this->_logger->debug(
                    "Post ingest update statement: $updateStatement"
                );
                $this->_destination_db->handle()->prepare(
                    $updateStatement
                )->execute();
            }
            catch (PDOException $e) {
                $this->_logger->err(array(
                    'message'    => $e->getMessage(),
                    'sql'        => $updateStatement,
                    'stacktrace' => $e->getTraceAsString(),
                ));
                return;
            }
        }

        $this->_destination_db->handle()->prepare(
            "SET FOREIGN_KEY_CHECKS = 1"
        )->execute();

        if ($rowsTotal > 0) {
            $this->_logger->debug('Analyzing table');
            $this->_destination_db->handle()->prepare(
                "ANALYZE TABLE {$this->_insert_table}"
            )->execute();
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $message = sprintf(
            '%s: Rows Processed: %d of %d (Time Taken: %01.2f s)',
            get_class($this),
            $sourceRows,
            $rowsTotal,
            $time
        );
        $this->_logger->info($message);

        // NOTE: This is needed for the log summary.
        $this->_logger->notice(array(
            'message'          => 'Finished ingestion',
            'class'            => get_class($this),
            'start_time'       => $time_start,
            'end_time'         => $time_end,
            'records_examined' => $rowsTotal,
            'records_loaded'   => $sourceRows,
        ));

    }

    public function setLogger(Log $logger)
    {
        $this->_logger = $logger;

        if ($this->_dest_helper !== null) {
            $this->_dest_helper->setLogger($logger);
        }
    }

    public function getSchemaAndTableNames()
    {
        $tableinfo = explode(".", $this->_insert_table);
        if( 1 == count($tableinfo) ) {
            // Using unqualified table name
            return array("schema" => $this->_destination_db->_db_name, "tablename" => $this->_insert_table);
        } else {
            return array("schema" => $tableinfo[0], "tablename" => $tableinfo[1] );
        }
    }

    public function checkForChanges()
    {
        $stmt = $this->_destination_db->handle()->prepare(
            "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA` = :schema) AND (`TABLE_NAME` = :tablename) AND (`COLUMN_KEY` = 'PRI')");

        $stmt->execute( $this->getSchemaAndTableNames() );

        $primarykeys = $stmt->fetchAll();

        $constraints = array();
        foreach($primarykeys as $keydata)
        {
            $constraints[] = sprintf(" b.%s = c.%s ", $keydata['COLUMN_NAME'], $keydata['COLUMN_NAME']);
        }

        if(0 == count($constraints))
        {
            $this->_logger->info("no primary keys defined for {$this->_insert_table}. Change check not run.");
            return;
        }

        $primarykey = $primarykeys[0]['COLUMN_NAME'];
        $statement = "SELECT b.* FROM {$this->_insert_table}_backup b LEFT OUTER JOIN {$this->_insert_table} c ON (" . join("AND", $constraints) . ") WHERE c.{$primarykey} IS NULL";

        $stmt = $this->_destination_db->handle()->prepare($statement);
        $stmt->execute();

        $sadness = false;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC) )
        {
            $this->_logger->crit(array(
                'message'          => 'Missing row',
                'rowdata'          => print_r($row, true),
                'class'            => get_class($this)
            ));
            $sadness = true;
        }

        if(!$sadness) {
            $this->_logger->info("data consistency check passed for {$this->_insert_table}");
        }
    }

    /**
     * Loads a pre- or post-ingest config file and generates SQL statements.
     *
     * This function can be used when an ingestor does not have any custom
     * pre- or post-ingest processing steps. If it does have custom operations,
     * then it is recommended that loadProcessingConfig and/or
     * addCommonProcessingStepToStatements be used directly.
     *
     * @param  string $configFilePath The path to the config file.
     * @return array                  A list of SQL statements to execute.
     * @throws Exception              The file could not be loaded
     *                                or is invalid.
     * @throws Exception              The file contains a step that specifies
     *                                a custom or invalid operation.
     */
    protected function getCommonProcessingStatements($configFilePath)
    {
        $processingSteps = $this->loadProcessingConfig($configFilePath);
        $processingStatements = array();
        foreach ($processingSteps as $step) {
            $this->addCommonProcessingStepToStatements(
                $step,
                $processingStatements
            );
        }
        return $processingStatements;
    }

    /**
     * Loads a config file for pre- or post-ingest processing of data.
     *
     * @param  string $configFilePath The path to the config file.
     * @return array                  A list of processing steps to perform.
     * @throws Exception              The file could not be loaded
     *                                or is invalid.
     */
    protected function loadProcessingConfig($configFilePath)
    {
        if (! is_file($configFilePath)) {
            throw new Exception("'$configPath' is missing. If no processing is needed, use an empty array.");
        }

        $configFileContents = @file_get_contents($configFilePath);
        if ($configFileContents === false) {
            $error = error_get_last();
            throw new Exception("Error opening file '$configFilePath': " . $error['message']);
        }

        $config = @json_decode($configFileContents);
        if ($config === null) {
            throw new Exception("Error decoding file '$configFilePath'.");
        }

        if (! is_array($config)) {
            throw new Exception("'$configFilePath' must be an array of processing steps.");
        }

        return $config;
    }

    /**
     * Adds the given step that uses a common operation to the given statements.
     *
     * This should be called after doing any custom processing of steps. If
     * the step's operation cannot be resolved, an exception will be thrown.
     *
     * @param  stdClass $step       The steps to examine.
     * @param  array    $statements The list of statements to append to.
     * @throws Exception            The step's operation could not be handled.
     */
    protected function addCommonProcessingStepToStatements(
        stdClass $step,
        array &$statements
    ) {
        $operation = $step->operation;
        if ($operation === 'execute_sql') {
            $statements[] = $step->sql;
        } else {
            throw new Exception("Unknown operation: $operation");
        }
    }
}
