<?php
/* ==========================================================================================
 * Abstract helper class to encapsulare functionality common to all actions (e.g., Aggregators and
 * Ingestors).  Actions may extend this class for simplicity, but they must all implement iAction.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL;

use Log;
use ETL\EtlOverseerOptions;
use ETL\DataEndpoint\iDataEndpoint;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\Configuration\Configuration;
use ETL\Configuration\EtlConfiguration;

abstract class aAction extends aEtlObject
{
    // aOptions object with configuration information for this action
    protected $options = null;

    // EtlConfiguration object. This is the parsed configuration.
    protected $etlConfig = null;

    // EtlOverseerOptions object. Contains the options for this ingestion run and is
    // private so we can enforce updating the variable map when it is set.
    private $etlOverseerOptions = null;

    // Individual actions may override ETL overseer restrictions. Store the overrides as an
    // associative array where the key is the restriction name and the value is the overriden
    // restriction.
    protected $overseerRestrictionOverrides = array();

    // A list of key/value pairs mapping a variable name to a value. This is used to substitute
    // variables in queries or other strings. Note that keys do not include ${}, only the name of the
    // variable.
    protected $variableMap = array();

    // Path to the JSON configuration file containing ETL table and source query configurations, among
    // other things.
    protected $definitionFile = null;

    // The stdClass representing a parsed definition file
    protected $parsedDefinitionFile = null;

    // Does this action support chunking of date ranges? Ingestors may support this to
    // mitigate timeouts on long-running queries but other actions may not. Defaults to
    // FALSE.
    protected $supportDateRangeChunking = false;

    // The current start date that this action is working with. Note that not all actions
    // utilize a start/end date.
    protected $currentStartDate = null;

    // The current end date that this action is working with. Note that not all actions
    // utilize a start/end date.
    protected $currentEndDate = null;

    // --------------------------------------------------------------------------------
    // NOTE: If we want to support additional endpoint names, these should be implemented as an array
    // of endpoints. -smg

    // Handle to the utility database, must implement iDataEndpoint and be a DataEndpoint\Mysql object
    protected $utilityHandle = null;
    protected $utilityEndpoint = null;

    // Handle to the source, must implement iDataEndpoint
    protected $sourceHandle = null;
    protected $sourceEndpoint = null;

    // Handle to the destination, must implement iDataEndpoint. This is typically a database.
    protected $destinationHandle = null;
    protected $destinationEndpoint = null;

    /* ------------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        // Set up the default logger
        parent::__construct($logger);

        $requiredKeys = array("name");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $this->setName($options->name);
        $this->options = $options;
        $this->etlConfig = $etlConfig;
        $this->logger->info("Create action " . $this);

        if ( null !== $this->options->definition_file ) {

            // Set up the path to the definition file for this action

            if ( isset($this->options->paths->action_definition_dir) ) {
                $this->definitionFile = \xd_utilities\qualify_path(
                    $this->options->definition_file,
                    $this->options->paths->action_definition_dir
                );
            }
            $this->definitionFile = \xd_utilities\resolve_path($this->definitionFile);

            // Parse the action definition so it is available before initialize() is called. If it
            // has already been set by a child constructor leave it alone.

            $options = array();
            foreach ( $this->options->paths as $name => $value ) {
                $options['variables'][$name] = $value;
            }

            if ( null === $this->parsedDefinitionFile ) {
                $this->parsedDefinitionFile = new Configuration(
                    $this->definitionFile,
                    $this->options->paths->base_dir,
                    $logger,
                    $options
                );
                $this->parsedDefinitionFile->initialize();
                $this->parsedDefinitionFile->cleanup();
            }
        }  // if ( null !== $this->options->definition_file )

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( null === $etlOverseerOptions ) {
            $this->logAndThrowException("ETL Overseer options not set");
        }

        parent::initialize();

        if ( null !== $etlOverseerOptions ) {
            $this->setEtlOverseerOptions($etlOverseerOptions);
        }

        $this->initializeVariableMap();
        $this->initializeUtilityEndpoint()->initializeSourceEndpoint()->initializeDestinationEndpoint();

        // Set up the start and end dates, which may be null if not provided. Actions that
        // support chunking of the date period can override these.

        list($startDate, $endDate) = $this->etlOverseerOptions->getDatePeriod();
        $this->currentStartDate = $startDate;
        $this->currentEndDate = $endDate;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getClass()
     * ------------------------------------------------------------------------------------------
     */

    public function getClass()
    {
        return get_class($this);
    }  // getClass()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getOptions()
     * ------------------------------------------------------------------------------------------
     */

    public function getOptions()
    {
        return $this->options;
    }  // getOptions()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getCurrentStartDate()
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentStartDate()
    {
        return $this->currentStartDate;
    }  // getCurrentStartDate()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getCurrentEndDate()
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentEndDate()
    {
        return $this->currentEndDate;
    }  // getCurrentEndDate()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::supportsDateRangeChunking()
     * ------------------------------------------------------------------------------------------
     */

    public function supportsDateRangeChunking()
    {
        return $this->supportDateRangeChunking;
    }  // supportsDateRangeChunking()

    /* ------------------------------------------------------------------------------------------
     * @return The ETL overseer options
     * ------------------------------------------------------------------------------------------
     */

    public function getEtlOverseerOptions()
    {
        return $this->etlOverseerOptions;
    }  // getEtlOverseerOptions()

    /* ------------------------------------------------------------------------------------------
     * Set the current EtlOverseerOptions object
     *
     * @return This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function setEtlOverseerOptions(EtlOverseerOptions $overseerOptions)
    {
        $this->etlOverseerOptions = $overseerOptions;

        return $this;
    }  // setEtlOverseerOptions()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getOverseerRestrictionOverrides()
     * ------------------------------------------------------------------------------------------
     */

    public function getOverseerRestrictionOverrides()
    {
        return $this->overseerRestrictionOverrides;
    }  // getOverseerRestrictionOverrides()

    /* ----------------------------------------------------------------------------------------------------
     * The ETL overseer provides the ability to specify parameters that are interpreted as
     * restrictions on actions such as the ETL start/end dates and resources to include or
     * exclude from the ETL process.  However, in some cases these options may be
     * overriden by the configuration of an individual action such as resources to include
     * or exclude for that action. Keep track of the restrictions here.
     * ----------------------------------------------------------------------------------------------------
     */

    protected function setOverseerRestrictionOverrides()
    {
        $this->overseerRestrictionOverrides = array();

        if ( isset($this->options->include_only_resource_codes) && is_array($this->options->include_only_resource_codes) ) {
            $this->overseerRestrictionOverrides[EtlOverseerOptions::RESTRICT_INCLUDE_ONLY_RESOURCES] = $this->options->include_only_resource_codes;

        }

        if ( isset($this->options->exclude_resource_codes) && is_array($this->options->exclude_resource_codes) ) {
            $this->overseerRestrictionOverrides[EtlOverseerOptions::RESTRICT_EXCLUDE_RESOURCES] = $this->options->exclude_resource_codes;
        }

    }  // setOverseerRestrictionOverrides()

    /* ------------------------------------------------------------------------------------------
     * @return An array containing the current variable to value mapping
     * ------------------------------------------------------------------------------------------
     */

    public function getVariableMap()
    {
        return $this->variableMap;
    }  // getVariableMap()

    /* ------------------------------------------------------------------------------------------
     * @return A string representation of the variable map suitable for debugging output.
     * ------------------------------------------------------------------------------------------
     */

    protected function getVariableMapDebugString()
    {
        $map = $this->variableMap;
        ksort($map);

        return implode(
            ', ',
            array_map(
                function ($k, $v) {
                    return "$k='$v'";
                },
                array_keys($map),
                $map
            )
        );
    }  // getVariableMapDebugString()

    /* ------------------------------------------------------------------------------------------
     * Set the variable to value map to be used when substituting variables in strings.
     *
     * @param $map An array containing the updated variable map
     *
     * @return $this for object chaining
     * ------------------------------------------------------------------------------------------
     */

    public function setVariableMap(array $map)
    {
        $this->variableMap = $map;
        return $this;
    }  // setVariableMap()

    /* ------------------------------------------------------------------------------------------
     * Initialized the variable map based on ETL settings in the overseer options
     * ------------------------------------------------------------------------------------------
     */

    private function initializeVariableMap()
    {
        if ( null === $this->etlOverseerOptions ) {
            return;
        }

        $this->variableMap = array();

        // Set up any variables associated with the Overseer that should be available for
        // substitution in actions such as start and end dates, number of days, etc.

        if ( null !== ( $value = $this->etlOverseerOptions->getStartDate() ) ) {
            $this->variableMap['START_DATE'] = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getEndDate() ) ) {
            $this->variableMap['END_DATE'] = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getNumberOfDays() ) ) {
            $this->variableMap['NUMBER_OF_DAYS'] = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getLastModifiedStartDate() ) ) {
            $this->variableMap['LAST_MODIFIED_START_DATE'] = $value;
            $this->variableMap['LAST_MODIFIED'] = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getLastModifiedEndDate() ) ) {
            $this->variableMap['LAST_MODIFIED_END_DATE'] = $value;
        }

        // If resource codes have been passed into the overseer, make the first resource id
        // available as a macro. Useful for ingesting log data for a specific resource.

        if (
            null !== ( $value = $this->etlOverseerOptions->getIncludeOnlyResourceCodes() )
            && is_array($value)
            && 0 != count($value)
        ) {
            $resourceCode = current($value);
            $resourceId = $this->etlOverseerOptions->getResourceIdFromCode($resourceCode);
            if ( count($value) > 1 ) {
                $this->logger->info(
                    sprintf(
                        "%d resources specified for inclusion, using first for RESOURCE macro: %s (id=%d)",
                        count($value),
                        $resourceCode,
                        $resourceId
                    )
                );
            }
            $this->variableMap['RESOURCE'] = $resourceCode;
            $this->variableMap['RESOURCE_ID'] = $resourceId;
        }

        // Set the default time zone and make it available as a variable
        $this->variableMap['TIMEZONE'] = date_default_timezone_get();

        // Make the ETL log email available to actions as a macro. If it is not available use
        // the the debug email instead.

        try {
            $section = \xd_utilities\getConfigurationSection("general");
            if ( array_key_exists('dw_etl_log_recipient', $section) && ! empty($section['dw_etl_log_recipient']) ) {
                $this->variableMap['DW_ETL_LOG_RECIPIENT'] = $section['dw_etl_log_recipient'];
            } elseif ( array_key_exists('debug_recipient', $section) && ! empty($section['debug_recipient']) ) {
                $this->variableMap['DW_ETL_LOG_RECIPIENT'] = $section['debug_recipient'];
            } else {
                $this->logger->warning(
                    "Cannot set ETL macro DW_ETL_LOG_RECIPIENT - XDMoD configuration option general.debug_recipient is not set or is empty."
                );
            }
        } catch (\Exception $e) {
            $msg = "'general' section not defined in XDMoD configuration";
            $this->logAndThrowException($msg);
        }

        return $this;
    }  // initializeVariableMap()


    /* ------------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeUtilityEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->utility)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableMap['UTILITY_SCHEMA'] = $endpoint->getSchema();
            }
            $this->logger->debug("Utility endpoint: " . $endpoint);
            $this->utilityEndpoint = $endpoint;
            $this->utilityHandle = $endpoint->getHandle();
        }
        return $this;
    }

    /* ------------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeSourceEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->source)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableMap['SOURCE_SCHEMA'] = $endpoint->getSchema();
            }
            $this->logger->debug("Source endpoint: " . $endpoint);
            $this->sourceEndpoint = $endpoint;
            $this->sourceHandle = $endpoint->getHandle();
        }
        return $this;
    }

    /* ------------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeDestinationEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->destination)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableMap['DESTINATION_SCHEMA'] = $endpoint->getSchema();
            }
            $this->logger->debug("Destination endpoint: " . $endpoint);
            $this->destinationEndpoint = $endpoint;
            $this->destinationHandle = $endpoint->getHandle();
        }
        return $this;
    }

    /** -----------------------------------------------------------------------------------------
     * Perform any pre-execution tasks. For example, disabling table keys on MyISAM
     * tables, or other setup tasks.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPreExecuteTasks();

    /** -----------------------------------------------------------------------------------------
     * Perform any post-execution tasks. For example, enabling table keys on MyISAM
     * tables, or tracking table history.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @param integer|null $numRecordsProcessed The number of records processed during
     *   execution, or NULL if it is not used by this action.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPostExecuteTasks($numRecordsProcessed = null);
}  // abstract class aAction
