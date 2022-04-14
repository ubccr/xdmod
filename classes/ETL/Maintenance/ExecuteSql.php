<?php
/* ==========================================================================================
 * Execute one or more SQL scripts.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-01-12
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Maintenance;

use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\DataEndpoint\iRdbmsEndpoint;
use PDOException;
use ETL\Utilities;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\exceptions\UnsupportedFeatureException;

// PHPSQLParser has "use Exception" instead of "use \Exception". In order to catch general
// exceptions we must do the same and reference the global \Exception when we throw one.
use Exception;

use Psr\Log\LoggerInterface;

class ExecuteSql extends aAction implements iAction
{
    // Delimiter for SQL scripts that contain multiple statements
    const DEFAULT_MULTI_STATEMENT_DELIMITER = '//';

    const SQL_COMMENT_STRING = '--';

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        if ( ! $options instanceof MaintenanceOptions ) {
            $msg = __CLASS__ . ": Options is not an instance of MaintenanceOptions";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("sql_file_list");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        parent::__construct($options, $etlConfig, $logger);

        // The SQL file list must be provided and be an array

        if ( ! is_string($options->sql_file_list) && ! is_array($options->sql_file_list) ) {
            $msg = __CLASS__ . ": file_list must be a string or an array";
            $this->logAndThrowException($msg);
        }

        // Undocumented behavior: Normalize a single file string/object list to an array

        if ( is_string($options->sql_file_list) || is_object($options->sql_file_list) ) {
            $options->sql_file_list = array($options->sql_file_list);
        }

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

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // Apply a base path to each SQL file, if needed

        // aOptions uses __get() to access object properties so we can't use indirect
        // modification. Fetching the value and then replacing it after modificaiton solves this issue.

        $sqlFileList  = $this->options->sql_file_list;

        foreach ( $sqlFileList as &$sqlFile ) {
            if ( isset($this->options->paths->sql_dir) ) {
                if ( is_object($sqlFile) && isset($sqlFile->sql_file) ) {
                    $sqlFile->sql_file = \xd_utilities\qualify_path($sqlFile->sql_file, $this->options->paths->sql_dir);
                } else {
                    $sqlFile = \xd_utilities\qualify_path($sqlFile, $this->options->paths->sql_dir);
                }
            }
        }
        unset($sqlFile); // Sever the reference with the last element

        $this->options->sql_file_list = $sqlFileList;

        if ( ! $this->destinationEndpoint instanceof iRdbmsEndpoint ) {
            $msg = "Destination endpoint does not implement ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }

        // Verify that each sql file exists and is readable

        foreach ( $this->options->sql_file_list as $sqlFile ) {

            $filename = $sqlFile;

            if ( is_object($sqlFile) ) {
                if ( ! isset($sqlFile->sql_file) ) {
                    $msg =  "sql_file_list object does not have sql_file property set";
                    $this->logAndThrowException($msg);
                } else {
                    $filename = $sqlFile->sql_file;
                }
            }  // if ( is_object($sqlFile) )

            if ( ! file_exists($filename) ) {
                $msg = "SQL file does not exist '$filename'";
                $this->logAndThrowException($msg);
            } elseif ( ! is_readable($filename) ) {
                $msg = "SQL file is not readable '$filename'";
                $this->logAndThrowException($msg);
            }
        }  // foreach ( $this->sqlScriptFiles as $sqlFile )

        // The SQL statements expect these variables to be quoted if they exist

        $varsToQuote = array(
            'START_DATE',
            'END_DATE',
            'LAST_MODIFIED',
            'LAST_MODIFIED_START_DATE',
            'LAST_MODIFIED_END_DATE'
        );

        $localVariableMap = Utilities::quoteVariables($varsToQuote, $this->variableStore, $this->destinationEndpoint);
        $this->variableStore->add($localVariableMap, true);

        $this->initialized = true;

        return true;

    }  // initialize()

    /** -----------------------------------------------------------------------------------------
     * @see aAction::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {
        return true;
    } // performPreExecuteTasks()

    /** -----------------------------------------------------------------------------------------
     * @see aAction::performPostExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostExecuteTasks($numRecordsProcessed = null)
    {
        return true;
    }  // performPostExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $time_start = microtime(true);
        $this->initialize($etlOverseerOptions);

        foreach ( $this->options->sql_file_list as $sqlFile ) {
            // close the connection so that a branch new connection will be created
            // for each file. This mitigates problems with long
            // running queries causing database connection timeouts.
            $this->destinationEndpoint->disconnect();

            $delimiter = self::DEFAULT_MULTI_STATEMENT_DELIMITER;

            // An object can be used to override the default delimiter.

            $filename = $sqlFile;

            if ( is_object($sqlFile) ) {
                $filename = $sqlFile->sql_file;
                if ( isset($sqlFile->delimiter) ) {
                    $delimiter = $sqlFile->delimiter;
                }
            }  // if ( is_object($sqlFile) )


            $sqlFileContents = file_get_contents($filename);
            $sqlFileContents = $this->variableStore->substitute(
                $sqlFileContents,
                "Undefined macros found in SQL file " . $filename
            );

            // Split the file on the delimiter and execute each statement. PDO without mysqlnd drivers
            // does not support multiple SQL statements in a single query.

            $sqlStatementList = explode($delimiter, $sqlFileContents);

            // Trim each SQL statement and filter empty items (such as newlines)

            $sqlStatementList = array_filter(array_map('trim', $sqlStatementList));
            $numSqlStatements = count($sqlStatementList);
            $numStatementsProcessed = 0;
            $this->logger->notice("Processing SQL file '$filename' using delimiter '$delimiter' containing $numSqlStatements statements");
            foreach ($sqlStatementList as $sql) {

                // Remove comments from the SQL before executing for clarity.

                $commentPatterns = array(
                    // Hash-style comments
                    '/^\s*#.*[\r\n]+/m',
                    // Standard SQL comments.
                    '/^\s*-- ?.*[\r\n]+/m'
                    );
                $sql = preg_replace($commentPatterns, "", $sql);

                // Skip delimiter and use statements

                if ( preg_match('/^\s*(use|delimiter)/', $sql) ) {
                    continue;
                }

                $sqlStartTime = microtime(true);
                $numRowsAffected = 0;
                $statementPosition = ($numStatementsProcessed + 1);
                $statementPositionDisplay = "( $statementPosition / $numSqlStatements)";
                try {
                    $this->logger->info(array(
                        "message" => "Executing statement " . $statementPositionDisplay,
                        "action" => (string) $this . '-sql-' . $statementPosition,
                        "endpoint" => $this->destinationEndpoint,
                        "sql" => $sql
                    ));
                    if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                        $numRowsAffected = $this->destinationEndpoint->getHandle()->execute($sql);
                    }
                } catch ( PDOException $e ) {
                    $this->logAndThrowException(
                        "Error executing SQL",
                        array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->sourceEndpoint)
                    );
                }

                $endTime = microtime(true);
                $this->logger->info(array(
                    "message" => "Finished executing statement " . $statementPositionDisplay,
                    "action" => (string) $this . '-sql-' . $statementPosition,
                    "rows" =>  $numRowsAffected,
                    "start_time" => $sqlStartTime,
                    "end_time" => $endTime,
                    "elapsed_time" => $endTime - $sqlStartTime
                ));

                $numStatementsProcessed++;

            }  // foreach ($sqlStatementList as $sql)

            $this->logger->notice("Finished Processing $numStatementsProcessed SQL statements");


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
