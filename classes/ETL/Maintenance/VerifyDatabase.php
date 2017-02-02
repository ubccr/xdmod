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

use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\DbEntity\Query;
use \PDOException;
use ETL\Utilities;
use \Log;

use PHPSQLParser\PHPSQLParser;

// PHPSQLParser has "use Exception" instead of "use \Exception". In order to catch general
// exceptions we must do the same and reference the global \Exception when we throw one.
use Exception;

class VerifyDatabase extends aAction
implements iAction
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

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        if ( ! $options instanceof MaintenanceOptions ) {
            $msg = __CLASS__ . ": Options is not an instance of MaintenanceOptions";
            $this->logAndThrowException($msg);
        }

        parent::__construct($options, $etlConfig, $logger);

        // Since Maintenance actions are versatile, we can't globally define these requirements in
        // the options file.

        $requiredKeys = array("source", "definition_file");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $this->sourceEndpoint = $etlConfig->getDataEndpoint($this->options->source);
        if ( ! $this->sourceEndpoint instanceof iRdbmsEndpoint ) {
            $this->sourceEndpoint = null;
            $msg = "Source endpoint is not an instance of ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }
        $this->sourceHandle = $this->sourceEndpoint->getHandle();
        $this->logger->debug("Source endpoint: " . $this->sourceEndpoint);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify(EtlOverseerOptions $etlOptions = null)
    {
        if ( $this->isVerified() ) {
            return;
        }

        $this->verified = false;
        if ( null !== $etlOptions ) {
            $this->setEtlOverseerOptions($etlOptions);
        }

        $this->initialize();

        // Verify that the response block and destination email are set

        if (  ! isset($this->parsedDefinitionFile->verify_database) ) {
            $msg = "Required key verify_database not found in definition file";
            $this->logAndThrowException($msg);
        }

        $verifyConfig = $this->parsedDefinitionFile->verify_database;

        if (  ! isset($verifyConfig->response) ) {
            $msg = "Required key verify_database.response not found in definition file";
            $this->logAndThrowException($msg);
        }

        if (  ! isset($verifyConfig->response->line) ) {
            $msg = "Required key verify_database.response.line not found in definition file";
            $this->logAndThrowException($msg);
        }

        // Verify that any fields referenced in the line response are valid column names

        if ( preg_match_all('/\${(.+)}/U', $verifyConfig->response->line, $matches) > 0 ) {
            array_shift($matches);
            $missing = array_diff($matches[0], $this->queryColumnNames);
            if ( 0 != count($missing) ) {
                $msg = "The following column names were referenced in the line template but are "
                    . "not present in the query: " . implode(", " , $missing);
                $this->logAndThrowException($msg);
            }
        }

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
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        $utilityEndpoint = $this->etlConfig->getDataEndpoint($this->options->utility);
        $sourceEndpoint = $this->etlConfig->getDataEndpoint($this->options->source);
        $destinationEndpoint = $this->etlConfig->getDataEndpoint($this->options->destination);

        // These additional variables are available to the sql statement

        $localVariableMap = array(
            'UTILITY_SCHEMA' => $utilityEndpoint->getSchema(),
            'SOURCE_SCHEMA' => $sourceEndpoint->getSchema(),
            'START_DATE' =>  $sourceEndpoint->quote($this->etlOverseerOptions->getStartDate()),
            'END_DATE' => $sourceEndpoint->quote($this->etlOverseerOptions->getEndDate())
            );

        if ( false !== $destinationEndpoint ) {
            $localVariableMap['DESTINATION_SCHEMA'] = $destinationEndpoint->getSchema();
        }

        if ( $this->etlOverseerOptions->getNumberOfDays() ) {
            $localVariableMap['NUMBER_OF_DAYS'] = $this->etlOverseerOptions->getNumberOfDays();
        }

        $this->variableMap = array_merge($this->variableMap, $localVariableMap);

        // Our source query can be either a query specified directly in the definition file, or a
        // more complex query defined in a separate file.

        $substitutedVars = array();
        $unsubstitutedVars = array();

        if ( ! isset($this->parsedDefinitionFile->source_query) ) {
            $msg = "Required source_query key not found";
            $this->logAndThrowException($msg);
        } else if ( isset($this->parsedDefinitionFile->source_query->sql_file) ) {

            $sqlFile = $this->parsedDefinitionFile->source_query->sql_file;
            $sqlFile = $this->options->applyBasePath("paths->sql_dir", $sqlFile);

            $this->logger->debug("Using SQL file: '$sqlFile'");

            if ( ! file_exists($sqlFile) ) {
                $msg = "SQL file does not exist '$sqlFile'";
                $this->logAndThrowException($msg);
            }

            $this->sqlQueryString = file_get_contents($sqlFile);
            $this->sqlQueryString = Utilities::substituteVariables($this->sqlQueryString, $this->variableMap, $substitutedVars, $unsubstitutedVars);

            if ( 0 != count($unsubstitutedVars) ) {
                $msg = $this . " Unsubstituted variables found in source query: " . implode(",", $unsubstitutedVars);
                $this->logger->warning($msg);
            }

            $parser = new PHPSQLParser($this->sqlQueryString);
            $parsedSql = $parser->parsed;

            if ( ! array_key_exists("SELECT", $parsedSql) ) {
                $msg = "Select block not found in parsed SQL";
                $this->logAndThrowException($msg);
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
            $sourceQuery = new Query($this->parsedDefinitionFile->source_query,
                                     $this->sourceEndpoint->getSystemQuoteChar(),
                                     $this->logger);
            $this->queryColumnNames = array_keys($sourceQuery->getRecords());
            $this->setOverseerRestrictionOverrides();
            $this->etlOverseerOptions->applyOverseerRestrictions($sourceQuery, $this->sourceEndpoint, $this->overseerRestrictionOverrides);
            $this->sqlQueryString = $sourceQuery->getSelectSql();
            $this->sqlQueryString = Utilities::substituteVariables($this->sqlQueryString, $this->variableMap, $substitutedVars, $unsubstitutedVars);

            if ( 0 != count($unsubstitutedVars) ) {
                $msg = $this . " Unsubstituted variables found in source query: " . implode(",", $unsubstitutedVars);
                $this->logger->warning($msg);
            }

        } // else if ( ! isset($this->parsedDefinitionFile->source_query) )

        // Set up the email optional configuration and apply any variables

        $verifyConfig = $this->parsedDefinitionFile->verify_database;

        // Unlike the other email config options the subject defaults to the action name rather than NULL.
        $this->emailConfiguration['subject'] =  $this->options->name;

        foreach ( array('subject', 'header', 'footer', 'destination_email') as $option ) {
            if ( ! isset($verifyConfig->response->$option) ) {
                continue;
            }

            $substitutedVars = array();
            $unsubstitutedVars = array();

            $this->emailConfiguration[$option] =  Utilities::substituteVariables(
                $verifyConfig->response->$option,
                $this->variableMap,
                $substitutedVars,
                $unsubstitutedVars);

            if ( 0 != count($unsubstitutedVars) ) {
                $msg = $this . " Unsubstituted variables found in response $option: " . implode(",", $unsubstitutedVars);
                $this->logger->warning($msg);
            }
        }  // if ( isset($verifyConfig->response->header) )

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $this->setEtlOverseerOptions($etlOverseerOptions);

        $this->verify();

        $time_start = microtime(true);

        $this->logger->debug("Executing SQL " . $this->sourceEndpoint . ": " . $this->sqlQueryString);
        $verifyConfig = $this->parsedDefinitionFile->verify_database;
        $lineTemplate = $verifyConfig->response->line;
        $lines = array();

        try {
            if ( ! $this->etlOverseerOptions->isDryrun() ) {
                $result = $this->sourceEndpoint->getHandle()->query($this->sqlQueryString);
                $this->logger->info(count($result) . " maches found");
                if ( 0 != count($result) ) {
                    foreach ( $result as $row ) {
                        $line = Utilities::substituteVariables($lineTemplate, $row);
                        $this->logger->warning($line);
                        $lines[] = $line;
                    }
                    // If there are result rows, generate an email replacing any row macros in the line template
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
