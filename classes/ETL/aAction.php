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

use Configuration\Configuration;
use Log;
use ETL\DataEndpoint\iDataEndpoint;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\Configuration\EtlConfiguration;

abstract class aAction extends aEtlObject
{
    /**
     * @var aOptions object with configuration information for this action
     */

    protected $options = null;

    /**
     * @var EtlConfiguration Object containing a representation of the parsed configuration file.
     */

    protected $etlConfig = null;

    /**
     * @var EtlOverseerOptions Object containing the options for this ingestion run. This is private
     *   so that we can enforce updating of the variable map.
     */

    private $etlOverseerOptions = null;

    /**
     * @var array An associative array where the key is the restriction name and the value is the overriden
     *   restriction. Individual actions may override ETL overseer restrictions.
     */

    protected $overseerRestrictionOverrides = array();

    /**
     * @var VariableStore An object storing a list of key/value pairs mapping a variable name to a
     *   value. This is used to substitute variables in queries or other strings. Note that keys do
     *   not include ${}, only the name of the variable.
     */

    protected $variableStore = null;

    /**
     * @var string Path to the JSON configuration file containing the action definition.
     */

    protected $definitionFile = null;

    /**
     * @var stdClass A class representing the parsed definition file.
     */

    protected $parsedDefinitionFile = null;

    /**
     * @var boolean True if this action supports chunking of date ranges. Ingestors may support this
     *   to mitigate timeouts on long-running queries but other actions may not. Defaults to FALSE.
     */

    protected $supportDateRangeChunking = false;

    /**
     * @var string The current start date that this action is working with. Note that not all
     *   actions utilize a start/end date.
     */

    protected $currentStartDate = null;

    /**
     * @var string The current end date that this action is working with. Note that not all actions
     *   utilize a start/end date.
     */

    protected $currentEndDate = null;

    /*
     * NOTE: If we want to support additional endpoint names, these should be implemented as an array of endpoints. -smg
     */

    /**
     * @var mixed An object or resource representing the connection to the underlying utility
     *   endopint. For example, a database handle or PDO object.
     */

    protected $utilityHandle = null;

    /**
     * @var iDataEndpoint The utility data endpoint, must implement iDataEndpoint.
     */

    protected $utilityEndpoint = null;

    /**
     * @var iDataEndpoint The utility data endpoint, must implement iDataEndpoint.
     */

    protected $sourceEndpoint = null;

    /**
     * @var mixed An object or resource representing the connection to the underlying utility
     *   endopint. For example, a database handle or PDO object.
     */

    protected $sourceHandle = null;

    /**
     * @var iDataEndpoint The utility data endpoint, must implement iDataEndpoint.
     */

    protected $destinationEndpoint = null;

    /**
     * @var mixed An object or resource representing the connection to the underlying utility
     *   endopint. For example, a database handle or PDO object.
     */

    protected $destinationHandle = null;

    /** -----------------------------------------------------------------------------------------
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

        $variableInitializer = ( isset($this->options->variables) ? $this->options->variables : null );
        $this->variableStore = new VariableStore($variableInitializer, $logger);

        if ( isset($this->options->definition_file) ) {

            // Set up the path to the definition file for this action

            $this->definitionFile = $this->options->definition_file;

            if ( isset($this->options->paths->action_definition_dir) ) {
                $this->definitionFile = \xd_utilities\qualify_path(
                    $this->options->definition_file,
                    $this->options->paths->action_definition_dir
                );
            }
            $this->definitionFile = \xd_utilities\resolve_path($this->definitionFile);

            // Parse the action definition so it is available before initialize() is called. If it
            // has already been set by a child constructor leave it alone.

            if ( null === $this->parsedDefinitionFile ) {
                $options = array(
                    'variable_store' => $this->variableStore
                );
                $this->parsedDefinitionFile = new Configuration(
                    $this->definitionFile,
                    ( isset($this->options->paths->base_dir) ? $this->options->paths->base_dir : null ),
                    $logger,
                    $options
                );
                $this->parsedDefinitionFile->initialize();
                $this->parsedDefinitionFile->cleanup();
            }
        }  // if ( null !== $this->options->definition_file )

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * Helper function to instantiate an action based on the provided EtlConfiguration object.
     *
     * @param EtlConfiguration $etlConfig Object containing the parsed ETL configuration.
     * @param string $actionName The name of the action to instantiate.
     * @param Log $logger A PEAR Log object or null to use the null logger
     *
     * @return iAction An object implementing the iAction interface
     * ------------------------------------------------------------------------------------------
     */

    public static function factory(EtlConfiguration $etlConfig, $actionName, Log $logger = null)
    {
        $etlConfig->initialize();
        $options = $etlConfig->getActionOptions($actionName);
        // We are using the action's own factory method
        return forward_static_call(array($options->factory, 'factory'), $options, $etlConfig, $logger);
    }  // factory()

    /** -----------------------------------------------------------------------------------------
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

        $this->initializeVariableStore();
        $this->initializeUtilityEndpoint()->initializeSourceEndpoint()->initializeDestinationEndpoint();

        // Set up the start and end dates, which may be null if not provided. Actions that
        // support chunking of the date period can override these.

        list($startDate, $endDate) = $this->etlOverseerOptions->getDatePeriod();
        $this->currentStartDate = $startDate;
        $this->currentEndDate = $endDate;

        return true;

    }  // initialize()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::getClass()
     * ------------------------------------------------------------------------------------------
     */

    public function getClass()
    {
        return get_class($this);
    }  // getClass()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::getOptions()
     * ------------------------------------------------------------------------------------------
     */

    public function getOptions()
    {
        return $this->options;
    }  // getOptions()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::getCurrentStartDate()
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentStartDate()
    {
        return $this->currentStartDate;
    }  // getCurrentStartDate()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::getCurrentEndDate()
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentEndDate()
    {
        return $this->currentEndDate;
    }  // getCurrentEndDate()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::supportsDateRangeChunking()
     * ------------------------------------------------------------------------------------------
     */

    public function supportsDateRangeChunking()
    {
        return $this->supportDateRangeChunking;
    }  // supportsDateRangeChunking()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::isEnabled()
     * ------------------------------------------------------------------------------------------
     */

    public function isEnabled()
    {
        return $this->options->enabled;
    } // isEnabled()

    /** -----------------------------------------------------------------------------------------
     * @return The ETL overseer options
     * ------------------------------------------------------------------------------------------
     */

    public function getEtlOverseerOptions()
    {
        return $this->etlOverseerOptions;
    }  // getEtlOverseerOptions()

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
     * @see iAction::getOverseerRestrictionOverrides()
     * ------------------------------------------------------------------------------------------
     */

    public function getOverseerRestrictionOverrides()
    {
        return $this->overseerRestrictionOverrides;
    }  // getOverseerRestrictionOverrides()

    /** ---------------------------------------------------------------------------------------------------
     * The ETL overseer provides the ability to specify parameters that are interpreted as
     * restrictions on actions such as the ETL start/end dates and resources to include or exclude
     * from the ETL process.  However, in some cases these options may be overriden by the
     * configuration of an individual action such as resources to include or exclude for that
     * action. Keep track of the restrictions here.
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

    /** -----------------------------------------------------------------------------------------
     * Initialize the variable map based on ETL settings in the overseer options.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    private function initializeVariableStore()
    {
        if ( null === $this->etlOverseerOptions ) {
            return;
        }

        // Set up any variables associated with the Overseer that should be available for
        // substitution in actions such as start and end dates, number of days, etc.

        if ( null !== ( $value = $this->etlOverseerOptions->getStartDate() ) ) {
            $this->variableStore->START_DATE = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getEndDate() ) ) {
            $this->variableStore->END_DATE = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getNumberOfDays() ) ) {
            $this->variableStore->NUMBER_OF_DAYS = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getLastModifiedStartDate() ) ) {
            $this->variableStore->LAST_MODIFIED_START_DATE = $value;
            $this->variableStore->LAST_MODIFIED = $value;
        }

        if ( null !== ( $value = $this->etlOverseerOptions->getLastModifiedEndDate() ) ) {
            $this->variableStore->LAST_MODIFIED_END_DATE = $value;
        }

        // If resource codes have been passed into the overseer, make the first resource id
        // available as a macro. Useful for ingesting log data for a specific resource.

        if (
            null !== ( $value = $this->etlOverseerOptions->getIncludeOnlyResourceCodes() )
            && is_array($value)
            && 0 != count($value)
        ) {
            $resourceCode = current($value);
            if ( false === ($resourceId = $this->etlOverseerOptions->getResourceIdFromCode($resourceCode)) ) {
                $this->logAndThrowException(sprintf("Unknown resource code: '%s'", $resourceCode));
            }
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
            $this->variableStore->RESOURCE = $resourceCode;
            $this->variableStore->RESOURCE_ID = $resourceId;
        }

        // Set the default time zone and make it available as a variable
        $this->variableStore->TIMEZONE = date_default_timezone_get();

        // Make the ETL log email available to actions as a macro. If it is not available use
        // the the debug email instead.

        try {
            $section = \xd_utilities\getConfigurationSection("general");
            if ( array_key_exists('dw_etl_log_recipient', $section) && ! empty($section['dw_etl_log_recipient']) ) {
                $this->variableStore->DW_ETL_LOG_RECIPIENT = $section['dw_etl_log_recipient'];
            } elseif ( array_key_exists('debug_recipient', $section) && ! empty($section['debug_recipient']) ) {
                $this->variableStore->DW_ETL_LOG_RECIPIENT = $section['debug_recipient'];
            } else {
                $this->logger->warning(
                    "Cannot set ETL macro DW_ETL_LOG_RECIPIENT - XDMoD configuration option general.debug_recipient is not set or is empty."
                );
            }
        } catch (\Exception $e) {
            $this->logAndThrowException("'general' section not defined in XDMoD configuration");
        }

        return $this;
    }  // initializeVariableMap()


    /** -----------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeUtilityEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->utility)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableStore->UTILITY_SCHEMA = $endpoint->getSchema();
            }
            $this->logger->info("Utility endpoint: " . $endpoint);
            $this->utilityEndpoint = $endpoint;
            $this->utilityHandle = $endpoint->getHandle();
        }
        return $this;
    }

    /** -----------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeSourceEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->source)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableStore->SOURCE_SCHEMA = $endpoint->getSchema();
            }
            $this->logger->info("Source endpoint: " . $endpoint);
            $this->sourceEndpoint = $endpoint;
            $this->sourceHandle = $endpoint->getHandle();
        }
        return $this;
    }

    /** -----------------------------------------------------------------------------------------
     * Initialize the utility endpoint based on the options provided for this action
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function initializeDestinationEndpoint()
    {
        if ( false !== ($endpoint = $this->etlConfig->getDataEndpoint($this->options->destination)) ) {
            if ( $endpoint instanceof iRdbmsEndpoint ) {
                $this->variableStore->DESTINATION_SCHEMA = $endpoint->getSchema();
            }
            $this->logger->info("Destination endpoint: " . $endpoint);
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
