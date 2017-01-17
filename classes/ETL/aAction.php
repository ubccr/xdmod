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

use \Log;
use ETL\EtlOverseerOptions;

abstract class aAction extends aEtlObject
{
    // aOptions object with configuration information for this action
    protected $options = null;

    // EtlConfiguration object. This is the parsed configuration.
    protected $etlConfig = null;

    // EtlOverseerOptions object. Contains the options for this ingestion run.
    protected $etlOverseerOptions = null;

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

        if (null !== $this->options->definition_file) {
            // Set up the path to the definition file for this action

            $this->definitionFile = $this->options->applyBasePath(
                "paths->definition_file_dir",
                $this->options->definition_file
            );

            // Parse the action definition so it is available before initialize() is called. If it
            // has already been set by a child constructor leave it alone.

            if (null === $this->parsedDefinitionFile) {
                $this->logger->info("Parse definition file: '" . $this->definitionFile . "'");
                $this->parsedDefinitionFile = new Configuration(
                    $this->definitionFile,
                    $this->options->paths->base_dir,
                    $logger
                );
                $this->parsedDefinitionFile->parse();
                $this->parsedDefinitionFile->cleanup();
            }
        }  // if ( null !== $this->options->definition_file )

        // Set the default time zone and make it available as a variable
        $this->variableMap['TIMEZONE'] = date_default_timezone_get();

        // Make the ETL log email available to actions as a macro

        try {
            $section = \xd_utilities\getConfigurationSection("general");
            if (array_key_exists("dw_etl_log_recipient", $section)) {
                $this->variableMap['DW_ETL_LOG_RECIPIENT'] = $section['dw_etl_log_recipient'];
            } else {
                $msg = "XDMoD configuration option general.dw_etl_log_recipient is not set";
                $this->logger->warning($msg);
            }
        } catch (\Exception $e) {
            $msg = "'general' section not defined in XDMoD configuration";
            $this->logAndThrowException($msg);
        }
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        if (null === $this->etlOverseerOptions) {
            $msg = "ETL Overseer options not set";
            $this->logAndThrowException($msg);
        }

        parent::verify();

        return true;
    }  // verify()

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

    /* ----------------------------------------------------------------------------------------------------
     * The ETL overseer provides the ability to specify parameters that are interpreted as
     * restrictions on the actions such as the ETL start/end dates and resources to include or
     * exclude from the ETL process.  However, in some cases these options may be overriden by the
     * configuration of a individual action such as resources to include or exclude for that
     * action. Keep track of the restrictions here.
     * ----------------------------------------------------------------------------------------------------
     */

    protected function setOverseerRestrictionOverrides()
    {
        $this->overseerRestrictionOverrides = array();

        if (isset($this->options->include_only_resource_codes) && is_array($this->options->include_only_resource_codes)) {
            $this->overseerRestrictionOverrides[EtlOverseerOptions::RESTRICT_INCLUDE_ONLY_RESOURCES] = $this->options->include_only_resource_codes;
        }

        if (isset($this->options->exclude_resource_codes) && is_array($this->options->exclude_resource_codes)) {
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
     * Initialize data needed to perform the action. This must occur AFTER the constructors have been
     * called.  Initialize should be called prior to verification and/or execution.
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function initialize();
}  // abstract class aAction
