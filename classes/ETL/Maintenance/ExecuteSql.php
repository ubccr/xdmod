<?php
/* ==========================================================================================
 * Execute one or more SQL scripts.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-01-12
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Maintenance;

use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\DataEndpoint\iRdbmsEndpoint;
use \PDOException;
use ETL\Utilities;
use \Log;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\exceptions\UnsupportedFeatureException;

// PHPSQLParser has "use Exception" instead of "use \Exception". In order to catch general
// exceptions we must do the same and reference the global \Exception when we throw one.
use Exception;

class ExecuteSql extends aAction implements iAction
{
    // Delimiter for SQL scripts that contain multiple statements
    const DEFAULT_MULTI_STATEMENT_DELIMITER = '//';

    const SQL_COMMENT_STRING = '--';

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        if (! $options instanceof MaintenanceOptions) {
            $msg = __CLASS__ . ": Options is not an instance of MaintenanceOptions";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("sql_file_list");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        parent::__construct($options, $etlConfig, $logger);

        // The SQL file list must be provided and be an array

        if (! is_string($options->sql_file_list) && ! is_array($options->sql_file_list)) {
            $msg = __CLASS__ . ": file_list must be a string or an array";
            $this->logAndThrowException($msg);
        }

        // Undocumented behavior: Normalize a single file string/object list to an array

        if (is_string($options->sql_file_list) || is_object($options->sql_file_list)) {
            $options->sql_file_list = array($options->sql_file_list);
        }
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify(EtlOverseerOptions $etlOptions = null)
    {
        if ($this->isVerified()) {
            return;
        }

        $this->verified = false;
        if (null !== $etlOptions) {
            $this->etlOverseerOptions = $etlOptions;
        }

        $this->initialize();

        // Verify that each sql file exists and is readable

        foreach ($this->options->sql_file_list as $sqlFile) {
            $filename = $sqlFile;

            if (is_object($sqlFile)) {
                if (! isset($sqlFile->sql_file)) {
                    $msg =  "sql_file_list object does not have sql_file property set";
                    $this->logAndThrowException($msg);
                } else {
                    $filename = $sqlFile->sql_file;
                }
            }  // if ( is_object($sqlFile) )

            if (! file_exists($filename)) {
                $msg = "SQL file does not exist '$filename'";
                $this->logAndThrowException($msg);
            } elseif (! is_readable($filename)) {
                $msg = "SQL file is not readable '$filename'";
                $this->logAndThrowException($msg);
            }
        }  // foreach ( $this->sqlScriptFiles as $sqlFile )

        $this->verified = true;

        return true;
    }  // verify()

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
        if ($this->isInitialized()) {
            return;
        }

        $this->initialized = false;

        // Apply a base path to each SQL file, if needed

        // aOptions uses __get() to access object properties so we can't use indirect
        // modification. Fetching the value and then replacing it after modificaiton solves this issue.

        $sqlFileList  = $this->options->sql_file_list;

        foreach ($sqlFileList as &$sqlFile) {
            if (is_object($sqlFile) && isset($sqlFile->sql_file)) {
                $sqlFile->sql_file = $this->options->applyBasePath("paths->sql_dir", $sqlFile->sql_file);
            } else {
                $sqlFile = $this->options->applyBasePath("paths->sql_dir", $sqlFile);
            }
        }

        $this->options->sql_file_list = $sqlFileList;

        $this->initialized = true;

        return true;
    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $this->etlOverseerOptions = $etlOverseerOptions;

        $this->verify();

        $time_start = microtime(true);

        $utilityEndpoint = $this->etlConfig->getDataEndpoint($this->options->utility);
        $sourceEndpoint = $this->etlConfig->getDataEndpoint($this->options->source);
        $destinationEndpoint = $this->etlConfig->getDataEndpoint($this->options->destination);

        if (! $destinationEndpoint instanceof iRdbmsEndpoint) {
            $msg = "Destination endpoint does not implement ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }
        $this->logger->debug("Destination endpoint: " . $destinationEndpoint);

        // These variables are available to the sql statement

        $localVariableMap = array(
            'UTILITY_SCHEMA' => $utilityEndpoint->getSchema(),
            'SOURCE_SCHEMA' => $sourceEndpoint->getSchema(),
            'DESTINATION_SCHEMA' => $destinationEndpoint->getSchema(),
            'START_DATE' =>  $destinationEndpoint->quote($etlOverseerOptions->getStartDate()),
            'END_DATE' => $destinationEndpoint->quote($etlOverseerOptions->getEndDate())
            );
        $this->variableMap = array_merge($this->variableMap, $localVariableMap);

        foreach ($this->options->sql_file_list as $sqlFile) {
            $delimiter = self::DEFAULT_MULTI_STATEMENT_DELIMITER;

            // An object can be used to override the default delimiter.

            $filename = $sqlFile;

            if (is_object($sqlFile)) {
                $filename = $sqlFile->sql_file;
                if (isset($sqlFile->delimiter)) {
                    $delimiter = $sqlFile->delimiter;
                }
            }  // if ( is_object($sqlFile) )

            $this->logger->info("Processing SQL file '$filename' with delimiter '$delimiter'");

            $sqlFileContents = file_get_contents($filename);
            $sqlFileContents = Utilities::substituteVariables($sqlFileContents, $this->variableMap);

            // Split the file on the delimiter and execute each statement. PDO without mysqlnd drivers
            // does not support multiple SQL statements in a single query.

            $sqlStatementList = explode($delimiter, $sqlFileContents);
            $numStatementsProcessed = 0;

            foreach ($sqlStatementList as $sql) {
                // Skip empty queries

                $sql = trim($sql);
                if ("" == $sql) {
                    continue;
                }

                // Remove comments from the SQL before executing for clarity.

                $commentPatterns = array(
                    // Inline or multi-line C-style comments. The U (ungreedy) is needed!
                    '/\/\*(.|[\r\n])*\*\//U',
                    // Hash-style comments
                    '/#.*[\r\n]+/',
                    // Standard SQL comments.
                    '/-- ?.*[\r\n]+/'
                    );
                $sql = preg_replace($commentPatterns, "", $sql);

                // Skip delimiter and use statements

                if (preg_match('/^\s*(use|delimiter)/', $sql)) {
                    continue;
                }

                $this->logger->debug("Executing SQL: $sql");
                $sqlStartTime = microtime(true);

                try {
                    if (! $this->etlOverseerOptions->isDryrun()) {
                        $destinationEndpoint->getHandle()->execute($sql);
                    }
                } catch (PDOException $e) {
                    $msg = "Error executing sql: " . $e->getMessage();
                    $this->logAndThrowSqlException($sql, $e);
                }

                $time = microtime(true) - $sqlStartTime;
                $this->logger->debug("Elapsed time: " . round($time, 5));

                $numStatementsProcessed++;
            }  // foreach ($sqlStatementList as $sql)

            $this->logger->info("Processed $numStatementsProcessed SQL statements");
        }  // foreach ( $this->options->sql_file_list as $sqlFile )

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $this->logger->notice(array('action'       => (string) $this,
                                    'start_time'   => $time_start,
                                    'end_time'     => $time_end,
                                    'elapsed_time' => round($time, 5)
                                  ));
    }  // execute()
}  // class ExecuteSql
