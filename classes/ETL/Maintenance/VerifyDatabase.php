<?php
/* ==========================================================================================
 * Verify data in the database by executing one or more SQL scripts and
 * reporting on the results.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-10-27
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Maintenance;

use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\DbModel\Query;
use PDOException;
use ETL\VariableStore;
use Log;

use PHPSQLParser\PHPSQLParser;

// PHPSQLParser has "use Exception" instead of "use \Exception". In order to catch general
// exceptions we must do the same and reference the global \Exception when we throw one.
use Exception;

class VerifyDatabase extends aAction implements iAction
{
    // Column names extracted from the query, used to verify macro expansions used in the line
    // messages are valid.
    protected $queryColumnNames = array();

    // Configuration for the optional notification email to be sent
    protected $emailConfiguration = array(
        'destination_email' => null,
        'subject' => null,
        'header'  => null,
        'footer'  => null
    );

    // SQL to execute to perform the verification
    protected $sqlQueryString = null;

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        if ( ! $options instanceof MaintenanceOptions ) {
            $this->logAndThrowException(__CLASS__ . ": Options is not an instance of MaintenanceOptions");
        }

        parent::__construct($options, $etlConfig, $logger);

        // Since Maintenance actions are versatile, we can't globally define these requirements in
        // the options file.

        $requiredKeys = array("source", "definition_file");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            $this->logger->debug("ALREADY INITIALIZED!");
            return;
        }

        $this->initialized = false;

        // These additional variables are available to the sql statement

        parent::initialize($etlOverseerOptions);

        $varsToQuote = array(
            'START_DATE',
            'END_DATE',
            'LAST_MODIFIED',
            'LAST_MODIFIED_START_DATE',
            'LAST_MODIFIED_END_DATE'
        );

        $localVariableMap = \ETL\Utilities::quoteVariables($varsToQuote, $this->variableStore, $this->sourceEndpoint);
        $this->variableStore->add($localVariableMap, true);

        // Our source query can be either a query specified directly in the definition file, or a
        // more complex query defined in a separate file.

        if ( ! isset($this->parsedDefinitionFile->source_query) ) {
            $this->logAndThrowException("Required source_query key not found");
        } elseif ( isset($this->parsedDefinitionFile->source_query->sql_file) ) {

            $sqlFile = $this->parsedDefinitionFile->source_query->sql_file;

            if ( isset($this->options->paths->sql_dir) ) {
                $sqlFile = \xd_utilities\qualify_path($sqlFile, $this->options->paths->sql_dir);
            }

            $this->logger->debug(sprintf("Using SQL file: '%s'", $sqlFile));

            if ( ! file_exists($sqlFile) ) {
                $this->logAndThrowException("SQL file does not exist '$sqlFile'");
            }

            $this->sqlQueryString = file_get_contents($sqlFile);
            $this->sqlQueryString = $this->variableStore->substitute(
                $this->sqlQueryString,
                "Undefined macros found in source query"
            );

            $parser = new PHPSQLParser($this->sqlQueryString);
            $parsedSql = $parser->parsed;

            if ( ! array_key_exists("SELECT", $parsedSql) ) {
                $this->logAndThrowException("Select block not found in parsed SQL");
            }

            foreach ( $parsedSql['SELECT'] as $item ) {
                if ( array_key_exists('alias', $item)
                     && $item['alias']['as']
                     && array_key_exists('name', $item['alias']) )
                {
                    $this->queryColumnNames[] = $item['alias']['name'];
                } else {
                    $pos = strrpos($item['base_expr'], ".");
                    $this->queryColumnNames[] = ( false === $pos ? $item['base_expr'] : substr($item['base_expr'], $pos + 1) );
                }
            }  // foreach ( $parsedSql['SELECT'] as $item )

        } else {
            $this->logger->debug("Create ETL source query object");
            $sourceQuery = new Query(
                $this->parsedDefinitionFile->source_query,
                $this->sourceEndpoint->getSystemQuoteChar(),
                $this->logger
            );
            $this->queryColumnNames = array_keys($sourceQuery->records);
            $this->setOverseerRestrictionOverrides();
            $this->getEtlOverseerOptions()->applyOverseerRestrictions($sourceQuery, $this->sourceEndpoint, $this);
            $this->sqlQueryString = $sourceQuery->getSql();
            $this->sqlQueryString = $this->variableStore->substitute(
                $this->sqlQueryString,
                "Undefined macros found in source query"
            );

        } // else if ( ! isset($this->parsedDefinitionFile->source_query) )

        // Set up the email optional configuration and apply any variables

        $verifyConfig = $this->parsedDefinitionFile->verify_database;

        // Unlike the other email config options the subject defaults to the action name rather than NULL.
        $this->emailConfiguration['subject'] =  $this->options->name;

        foreach ( array('subject', 'header', 'footer', 'destination_email') as $option ) {
            if ( ! isset($verifyConfig->response->$option) ) {
                continue;
            }

            $this->emailConfiguration[$option] = $this->variableStore->substitute(
                $verifyConfig->response->$option,
                "Undefined macros found in response $option"
            );

        }  // if ( isset($verifyConfig->response->header) )

        if ( ! $this->sourceEndpoint instanceof iRdbmsEndpoint ) {
            $this->logAndThrowException(
                "Source endpoint is not an instance of ETL\\DataEndpoint\\iRdbmsEndpoint"
            );
        }

        // Verify that the response block and destination email are set

        if ( ! isset($this->parsedDefinitionFile->verify_database) ) {
            $this->logAndThrowException(
                "Required key verify_database not found in definition file"
            );
        }

        $verifyConfig = $this->parsedDefinitionFile->verify_database;

        if ( ! isset($verifyConfig->response) ) {
            $this->logAndThrowException(
                "Required key verify_database.response not found in definition file"
            );
        }

        if ( ! isset($verifyConfig->response->line) ) {
            $this->logAndThrowException(
                "Required key verify_database.response.line not found in definition file"
            );
        }

        // Verify that any fields referenced in the line response are valid column names

        if ( preg_match_all('/\${(.+)}/U', $verifyConfig->response->line, $matches) > 0 ) {
            array_shift($matches);
            $missing = array_diff($matches[0], $this->queryColumnNames);
            if ( 0 != count($missing) ) {
                $this->logAndThrowException(
                    "The following column names were referenced in the line template but are "
                    . "not present in the query: " . implode(", ", $missing)
                );
            }
        }

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

        $this->logger->debug("Executing SQL " . $this->sourceEndpoint . ":\n" . $this->sqlQueryString);
        $verifyConfig = $this->parsedDefinitionFile->verify_database;
        $lineTemplate = $verifyConfig->response->line;
        $lines = array();

        try {
            if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                $result = $this->sourceHandle->query($this->sqlQueryString);
                $this->logger->info(count($result) . " matches found");
                if ( 0 != count($result) ) {
                    foreach ( $result as $row ) {
                        $vs = new VariableStore($row, $this->logger);
                        $line = $vs->substitute($lineTemplate);
                        $this->logger->warning($line);
                        $lines[] = $line;
                    }
                }
            }
        } catch ( PDOException $e ) {
            $this->logAndThrowException(
                "Error executing SQL",
                array('exception' => $e, 'sql' => $this->sqlQueryString, 'endpoint' => $this->sourceEndpoint)
            );
        }

        // How do we substitute variables in the header and footer?

        if ( count($lines) > 0 && null !== $this->emailConfiguration['destination_email'] ) {
            $this->logger->notice("Sending notification email to " . $this->emailConfiguration['destination_email']);
            $body = ( null !== $this->emailConfiguration['header'] ? $this->emailConfiguration['header'] . "\n\n" : "" )
                . implode("\n", $lines)
                . ( null !== $this->emailConfiguration['footer'] ? "\n\n" . $this->emailConfiguration['footer'] : "" );
            mail($this->emailConfiguration['destination_email'], $this->emailConfiguration['subject'], $body);
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $this->logger->notice(array('action'       => (string) $this,
                                    'start_time'   => $time_start,
                                    'end_time'     => $time_end,
                                    'elapsed_time' => round($time, 5)
                                  ));
    }  // execute()
}  // class ExecuteSql
