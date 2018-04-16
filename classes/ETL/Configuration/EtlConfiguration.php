<?php
/* ==========================================================================================
 * Read and parse the ETL process JSON configuration file. The configuration file contains
 * a "defaults" section and one or more action defintion (ingestors, aggregators, etc.)
 * sections called pipelines. Each pipeline defines an array of actions. The defaults
 * section can contain entries for each pipeline as well as a global section where source,
 * destination, and utility data endpoints can be defined. Optional local configuration
 * files in a sub-directory are also supported with the actions and pipelines defined
 * being merged back into the global namespace. Pipelines can be identified using any name
 * but special support is included for "ingestors", and "aggregators" sections including
 * class constants and default options objects.
 *
 * Default options specific to any pipeline can be included in the "defaults" section
 * using the same name as the target pipeline.  When applying defaults, we start with the
 * options configured in the action definition and then apply defaults specific to the
 * pipeline that the action is defined in. Global defaults are then applied. This ensures
 * that options appearing in the action configuration take precedence, followed by
 * pipeline-specific defaults, followed by global defaults.
 *
 * Transformers can be defined that will transform the values of matching keys.
 *
 * NOTE: The "defaults" and "global" sections are reserved and cannot bes used except in
 *   the defaults block.
 *
 * @see Configuration
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-25
 * ==========================================================================================
 */

namespace ETL\Configuration;

use Exception;
use stdClass;
use Log;
use ETL\aOptions;
use ETL\Ingestor\IngestorOptions;
use ETL\Aggregator\AggregatorOptions;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class EtlConfiguration extends Configuration
{
    // Named ETL sections are defined here for ease of reference but any section can be defined in
    // the configuration file.
    const MAINTENANCE = "maintenance";
    const INGESTORS = "ingestors";
    const AGGREGATORS = "aggregators";

    // Default class names for section options, if not specified in the configuration file
    const DEFAULT_MAINTENANCE_OPTIONS_CLASS = "\\ETL\\Maintenance\\MaintenanceOptions";
    const DEFAULT_INGESTOR_OPTIONS_CLASS = "\\ETL\\Ingestor\\IngestorOptions";
    const DEFAULT_AGGREGATOR_OPTIONS_CLASS = "\\ETL\\Aggregator\\AggregatorOptions";

    // The key used to identify global defaults to apply across all sections regardless of name. This
    // must also be present in the $etlConfigReservedKeys array.
    const GLOBAL_DEFAULTS = "global";

    // JSON object key represeting a list of data endpoints
    const DATA_ENDPOINT_KEY = "endpoints";

    // JSON object key represeting a set of filw and directory paths
    const PATHS_KEY = "paths";

    // Reserved keys in the top level of the configuration file. ETL section names cannot use one of
    // these keys.
    private $etlConfigReservedKeys =
        array("defaults",
              self::DATA_ENDPOINT_KEY,
              self::PATHS_KEY,
              self::GLOBAL_DEFAULTS);

    // If this is a local configuration file we have the option of using global defaults from the parent
    private $parentDefaults = null;

    // An array of endpoints defined in the global defaults section. The keys are the endpoint names
    // (utility, source, destination, etc.) the values are endpoint keys used to reference the
    // $endpoints array.
    private $globalEndpoints = array();

    // Associative array where the key is the endpoint key and the value is a DataEndpoint object
    private $endpoints = array();

    // An associative array where keys are section names and values are an array of action option
    // objects.
    private $actionOptions = array();

    // An array of key/value pairs (2-element arrays) containing options to either add to or
    // override individual action options. These will be applied to all actions, if present.
    private $optionOverrides = null;

    // Path information for various configuration files and directories
    private $paths = null;

    // Path information for various configuration files and directories
    private $localDefaults = null;

    /* ------------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param $filename Name of the JSON configuration file to parse
     * @param $baseDir Base directory for configuration files. Overrides the base dir provided in
     *   the top-level config file
     * @param $logger A PEAR Log object or null to use the null logger.
     * @param $options An associative array of additional options passed from the parent. These
     *   include:
     *   is_local_config: TRUE if this is a local configuration file
     *   option_overrides: An array of key/value pairs (2-element arrays) containing options to
     *      either add to or override individual action options. These will be applied to all
     *      actions, if present.
     *   parent_defaults: The defaults class from the parent configuration file, if we are
     *      processing a configuration file in a subdirectory.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(
        $filename,
        $baseDir = null,
        Log $logger = null,
        array $options = array()
    ) {
        parent::__construct($filename, $baseDir, $logger, $options);

        if ( array_key_exists('option_overrides', $options) && null !== $options['option_overrides'] ) {
            if ( ! is_array($options['option_overrides']) ) {
                $this->logAndThrowException("Option overrides must be an array");
            } elseif ( 0 !== count($options['option_overrides']) ) {
                $this->optionOverrides = $options['option_overrides'];
            }
        }

        if ( array_key_exists('parent_defaults', $options) && null !== $options['parent_defaults'] ) {
            if ( ! is_object($options['parent_defaults']) ) {
                $this->logAndThrowException("Parent defaults must be an object");
            } else {
                $this->parentDefaults = $options['parent_defaults'];
            }
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see Configuration::preTransformTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function preTransformTasks()
    {

        // At this point, the file has been parsed but keys have not been transformed. In
        // order to support macros in the JSON references we need access to the "paths"
        // key found in the main configuration file before we apply key transformations.

        $this->addBaseDirToPaths();

        if ( isset($this->paths->local_config_dir) ) {
            $this->localConfigDir = $this->paths->local_config_dir;
        }

        return parent::preTransformTasks();

    }  // preTransformTasks()

    /* ------------------------------------------------------------------------------------------
     * Perform additional operations on the parsed configuration file. This includes
     * handling ETL-specific components such as the "paths" block, applying defaults, and
     * initializing data endpoints and action objects.
     *
     * @param $force TRUE if the configuration file should be re-parsed.
     *
     * @throw Exception If the file is does not exist or is not readable
     * @throw Exception If there is an error parsing the file
     * ------------------------------------------------------------------------------------------
     */

    protected function interpretData()
    {
        // Auto-detect the ETL section names in the config file.  To be as flexible as possible, we
        // don't care what the names are as long as they are not one of the reserved keys. Defaults
        // are applied by checking for a section name in the defaults configuration and, if present,
        // applying those default values to the section if they are not already present.

        $config = $this->transformedConfig;
        $etlSectionNames = array_diff(array_keys(get_object_vars($config)), $this->etlConfigReservedKeys);
        $defaultSectionNames = array_merge(array(self::GLOBAL_DEFAULTS, self::DATA_ENDPOINT_KEY), $etlSectionNames);

        // ------------------------------------------------------------------------------------------
        // Manage default values.  There can be a global default section, plus one for each section
        // defined in the configuration file.  Defaults are applied as long as the name of the entries
        // in the default and section blocks match.  If we are processing a local configuration file merge
        // the defaults from the parent with local defaults taking precedence. If local defaults are
        // not present but there are parent defaults, use all of them. All defaults are propogated,
        // not only those for sections defined here.

        if ( isset($config->defaults) ) {

            // Note: The config object may contain nested objects so we cannot simply cast it to an
            // array and call array_replace_recursive(). json_decode() can convert objects to arrays
            // recursively and after the merge appears to correctly decode arrays and objects.

            $parentDefaults = ( null !== $this->parentDefaults ? json_decode(json_encode($this->parentDefaults), true) : array() );
            $localDefaults = json_decode(json_encode($config->defaults), true);
            $this->localDefaults = json_decode(json_encode(array_replace_recursive($parentDefaults, $localDefaults)));

        } elseif ( null !== $this->parentDefaults ) {
            $this->localDefaults = $this->parentDefaults;
        }

        // Now that the local defaults are stored in the object remove them from the constructed
        // config.
        unset($config->defaults);

        // Apply global and section-specific (local) defaults. Section-specific defaults take
        // precedence over globals.

        foreach ( $etlSectionNames as $sectionName ) {

            // The value of each section must be an array of objects

            if ( ! is_array($config->$sectionName) ) {
                $this->logAndThrowException(
                    sprintf("'%s', expected array of action objects, got %s", $sectionName, gettype($config->$sectionName))
                );
            }

            foreach ( $config->$sectionName as &$actionConfig ) {
                if ( ! is_object($actionConfig) ) {
                    $this->logAndThrowException(
                        sprintf("In section '%s', expected action object, got %s", $sectionName, gettype($actionConfig))
                    );
                }
                $this->applyDefaultsToAction($actionConfig, $sectionName, $this->localDefaults);
            }
        }  // foreach ( $etlSectionNames as $typeName )

        // --------------------------------------------------------------------------------
        // Register the global utility endpoint in the main ETL configuration file because it may be
        // needed by the etl overseer script to look up resource ids from resource codes. We do not
        // register other global endpoints because these should only be registered if an enabled
        // action needs them.

        $this->endpoints = array();

        if ( ! $this->isLocalConfig && isset($this->localDefaults->global->endpoints->utility) ) {
            $name = 'utility';
            try {
                $endpoint = $this->addDataEndpoint($this->localDefaults->global->endpoints->utility);
                $this->globalEndpoints[$name] = $endpoint->getKey();
            } catch (Exception $e) {
                $this->logAndThrowException("Error registering default endpoint '$name': " . $e->getMessage());
            }
        }  // if ( isset(($this->localDefaults->global->endpoints) )

        // --------------------------------------------------------------------------------
        // Register individual actions discovered in the configuration file

        foreach ( $etlSectionNames as $sectionName ) {

            // If the section key doesn't contain an object or array, skip it.
            if ( ! is_array($config->$sectionName) && ! is_object($config->$sectionName) ) {
                continue;
            }

            $this->addSection($sectionName);

            foreach ( $config->$sectionName as &$actionConfig ) {

                // Intercept the action configuration and add/override options provided on the
                // command line.

                if ( null !== $this->optionOverrides ) {
                    foreach ( $this->optionOverrides as $tag => $value ) {

                        // Support JSON constructs such as arrays in overrides. Be careful because
                        // json_decode() returns NULL if a string cannot be decoded or is actually
                        // null. A simple string such as "bob" cannot be decoded and should be used
                        // verbatim as should a value of null.

                        $decodedValue = json_decode($value);
                        $actionConfig->$tag = ( "null" !== strtolower($value) && null === $decodedValue
                                                ? $value
                                                : json_decode($value) );
                    }
                }

                try {
                    $this->registerAction($actionConfig, $sectionName);
                } catch ( Exception $e ) {
                    $actionName = ( isset($actionConfig->name) ? $actionConfig->name : "" );
                    $this->logAndThrowException(
                        "Error adding action '$actionName' in section '$sectionName': " . $e->getMessage()
                    );
                }
            }
        }  // foreach ( $etlSectionNames as $sectionName )

        $this->transformedConfig = $config;

    }  // interpretData()

    /* ------------------------------------------------------------------------------------------
     * Handle creation of an EtlConfiguration object for the given class.
     *
     * @see Configuration::processLocalConfig()
     * ------------------------------------------------------------------------------------------
     */

    protected function processLocalConfig($localConfigFile)
    {
        $options = array(
            'local_config_dir' => $this->localConfigDir,
            'is_local_config'  => true,
            'option_overrides' => $this->optionOverrides,
            'parent_defaults'  => $this->localDefaults
        );

        $localConfigObj = new EtlConfiguration($localConfigFile, $this->baseDir, $this->logger, $options);
        $localConfigObj->initialize();

        return $localConfigObj;

    }  // processLocalConfig()

    /* ------------------------------------------------------------------------------------------
     * Merge data from the specified local configuration object, either overwriting or
     * merging data from local configuration objects into the current object.  Overrides
     * Configuration::merge().
     *
     * @see Configuration::merge()
     * ------------------------------------------------------------------------------------------
     */

    protected function merge(Configuration $localConfigObj, $overwrite = false)
    {
        if ( ! $localConfigObj instanceof EtlConfiguration ) {
            $this-logAndThrowException("Local config object is not of type EtlConfiguration");
        }

        parent::merge($localConfigObj, $overwrite);

        // Merge everything back into the main object

        foreach ( $localConfigObj->getDataEndpoints() as $key => $value ) {
            if ( ! array_key_exists($key, $this->endpoints) ) {
                $this->endpoints[$key] = $value;
            }
        }  // foreach ( $localConfigObj->getDataEndpoints() as $key => $value )

        // Merge in sections from the local config. A local config may contain a duplicate
        // section name, in which case we will merge the actions with the master
        // config. Do not allow duplicate action names in the same section.

        foreach ( $localConfigObj as $localSectionName => $localSectionActionOptions ) {

            // It is recommended that action names are unique, but they don't need to
            // be. If the same action name is found in a local config with the same
            // pipeline name then use the $overwrite setting to handle it.

            foreach ( $localSectionActionOptions as $localActionName => $localActionOptions ) {

                if ( $this->actionExists($localActionName, $localSectionName) ) {

                    $msg = sprintf(
                        "Duplicate action '%s' found in '%s' section '%s'",
                        $localActionName,
                        $this->filename,
                        $localSectionName
                    );

                    // Since an individual action cannot be merged with a duplicate, if
                    // the action name already exists and we are not overwriting then skip
                    // it.

                    if ( $overwrite ) {
                        $msg .= ", replacing existing action.";
                        $this->logger->info($msg);
                    } else {
                        $msg .= ", ignoring.";
                        $this->logger->warning($msg);
                        continue;
                    }
                }

                $this->addAction($localSectionName, $localActionOptions);

            }  // foreach ( $sectionActionOptions as $actionName => $options )

        }  // foreach ( $localConfigObj->getSectionNames() as $sectionName )

        return $this;

    }  // merge()

    /* ------------------------------------------------------------------------------------------
     * Clean up intermediate information that we don't need to keep around after processing. This
     * includes parsed and constructed JSON as well as defaults.
     * ------------------------------------------------------------------------------------------
     */

    public function cleanup()
    {
        parent::cleanup();
        $this->parentDefaults = null;
        $this->localDefaults = null;
    }  // cleanup()

    /* ------------------------------------------------------------------------------------------
     * Apply the base path to all relative paths in the "paths" block.
     * ------------------------------------------------------------------------------------------
     */

    protected function addBaseDirToPaths()
    {
        // Base paths are only supported in the main configuration file.

        if ( $this->isLocalConfig ) {
            return;
        }

        // The paths object must be present

        if ( ! isset($this->parsedConfig->paths) ) {
            $this->logAndThrowException("Required configuration 'paths' not found in config file");
        } elseif ( ! is_object($this->parsedConfig->paths) ) {
            $this->logAndThrowException("Configuration 'paths' must be an object");
        }

        foreach ( $this->parsedConfig->paths as $key => &$value ) {
            $value = \xd_utilities\qualify_path($value, $this->baseDir);
            $value = \xd_utilities\resolve_path($value);
        }
        unset($value); // Sever the reference with the last element

        // Add the base directory to the paths configuration so it is easily accessible
        $this->parsedConfig->paths->base_dir = $this->baseDir;

        // Place the path block into the global defaults so it is automatically propogated to all
        // actions when defaults are applied.

        if ( ! isset($this->parsedConfig->defaults) ) {
            $this->parsedConfig->defaults = new stdClass;
            $this->parsedConfig->defaults->global = new stdClass;
        } elseif ( ! isset($this->parsedConfig->defaults->global) ) {
            $this->parsedConfig->defaults->global = new stdClass;
        }

        $this->parsedConfig->defaults->global->paths = $this->parsedConfig->paths;

        // Save it for later and remove it from the config.

        $this->paths = $this->parsedConfig->paths;
        unset($this->parsedConfig->paths);

    }  // addBaseDirToPaths()

    /* ==========================================================================================
     * Iterator implementation. Allow iteration over the list of sections.
     * ==========================================================================================
     */

    public function current()
    {
        return current($this->actionOptions);
    }  // current()

    public function key()
    {
        return key($this->actionOptions);
    }  // key()

    public function next()
    {
        return next($this->actionOptions);
    }  // next()

    public function rewind()
    {
        return reset($this->actionOptions);
    }  // rewind()

    public function valid()
    {
        return false !== current($this->actionOptions);
    }  // valid()

    /* ------------------------------------------------------------------------------------------
     * Add a new section to the internal data structures if it doesn't already exist or
     * update the data associated with the section if it does exist (unless $overwrite ==
     * false)
     *
     * @see Configuration::addSection()
     *
     * @param $name The name of the new section
     * @param $data The data associated with the new section
     * @param $overwrite TRUE if any existing data for the given section should be overwritten
     *
     * @return This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function addSection($name, $data = null, $overwrite = true)
    {
        if ( in_array($name, $this->etlConfigReservedKeys) ) {
            return $this;
        }

        if ( ! $this->sectionExists($name) ) {
            $this->actionOptions[$name] = array();
        }

        parent::addSection($name, $data, $overwrite);

        return $this;

    }  // addSection()

    /* ------------------------------------------------------------------------------------------
     * Apply both global and section-specific (local) defaults to a action config block.
     *
     * @param $actionConfig Reference to the configuration block for the action
     * @param $sectionName The name of the section for local defaults
     * @param $defaults An associative array containing all defaults (both global and local)
     * ------------------------------------------------------------------------------------------
     */

    protected function applyDefaultsToAction(stdClass &$actionConfig, $sectionName, stdClass $defaults)
    {
        // Apply defaults where applicable and insure that a source and destination endpoint are defined
        // for each aggregator.  We will check for errors later when instantiating the individual classes.

        // ------------------------------------------------------------------------------------------
        // Apply local (section) defaults first as they override globals

        if ( isset($defaults->$sectionName) ) {
            foreach ( $defaults->$sectionName as $propertyKey => $propertyValue ) {

                // The action config doesn't have the property at all, set it.

                if ( ! isset($actionConfig->$propertyKey) ) {
                    $actionConfig->$propertyKey = $propertyValue;
                } elseif ( self::DATA_ENDPOINT_KEY == $propertyKey ) {

                    // This is the data endpoint property. Only apply endpoints that are not defined in the
                    // action config

                    foreach ( $propertyValue as $endpointName => $endpointConfig ) {
                        if ( ! isset($actionConfig->$propertyKey->$endpointName) ) {
                            $actionConfig->$propertyKey->$endpointName = $endpointConfig;
                        }
                    }
                }  // elseif ( self::DATA_ENDPOINT_KEY == $property )

            }  // foreach ( $defaults->$sectionName as $propertyKey => $value )
        }  // if ( isset($defaults->$sectionName) )

        // ------------------------------------------------------------------------------------------
        // Apply global defaults

        $globalDefaultKey = self::GLOBAL_DEFAULTS;

        if ( isset($defaults->$globalDefaultKey) ) {
            foreach ( $defaults->$globalDefaultKey as $propertyKey => $propertyValue ) {

                // The action config doesn't have the property at all, set it.

                if ( ! isset($actionConfig->$propertyKey) ) {
                    $actionConfig->$propertyKey = $propertyValue;
                }

                // This is the data endpoint property. Only apply endpoints that are not defined in the
                // action config

                if ( self::DATA_ENDPOINT_KEY == $propertyKey ) {
                    foreach ( $propertyValue as $endpointName => $endpointConfig ) {
                        if ( ! isset($actionConfig->$propertyKey->$endpointName) ) {
                            $actionConfig->$propertyKey->$endpointName = $endpointConfig;
                        }
                    }
                }  // elseif ( self::DATA_ENDPOINT_KEY == $propertyKey )

            }  // foreach ( $defaults->$globalDefaultKey as $propertyKey => $propertyValue )
        }  // if ( isset($defaults->$globalDefaultKey )

        // ------------------------------------------------------------------------------------------
        // Now apply default paths to the endpoints.

        $pathsKey = self::PATHS_KEY;

        if ( isset($defaults->$globalDefaultKey->$pathsKey) && isset($actionConfig->endpoints) ) {
            foreach ( $actionConfig->endpoints as $endpointName => &$endpointConfig ) {
                if ( ! isset($endpointConfig->paths) ) {
                    $endpointConfig->paths = $defaults->$globalDefaultKey->$pathsKey;
                }
            }
        }  // if ( isset($defaults->$globalDefaultKey->$pathsKey) && isset($actionConfig->endpoints) )

    }  // applyDefaultsToAction()

    /* ------------------------------------------------------------------------------------------
     * Register an action and also register any data endpoints that it has.  Registered actions
     * have their options set up but they are not instantiated.
     *
     * @param $config Reference to the action configuration section
     * @param $sectionName The name of the section that this action belongs to.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function registerAction(stdClass &$config, $sectionName)
    {
        // Verify the section name was provided

        if ( null === $sectionName || empty($sectionName) ) {
            $this->logAndThrowException("Empty or null ETL section name");
        } elseif ( ! isset($config->name) ) {
            $this->logAndThrowException("Action name not set");
        }

        // Verify that the action name was not already provided for **this section**.

        $actionName = $config->name;

        if ( $this->actionExists($actionName, $sectionName) ) {
            $this->logAndThrowException(
                "Action '$actionName' is already defined in section '$sectionName'"
            );
        }

        // If the class is not specified assume that the name is the class

        if ( ! isset($config->class) ) {
            $config->class = $config->name;
        }

        // Verify that we have a class name for the options and it exists.  Ingestors and Aggregators
        // have default options classes if they are not otherwise specified.

        if ( ! isset($config->options_class) ) {
            switch ( $sectionName ) {
                case self::MAINTENANCE:
                    $config->options_class = self::DEFAULT_MAINTENANCE_OPTIONS_CLASS;
                    break;
                case self::INGESTORS:
                    $config->options_class = self::DEFAULT_INGESTOR_OPTIONS_CLASS;
                    break;
                case self::AGGREGATORS:
                    $config->options_class = self::DEFAULT_AGGREGATOR_OPTIONS_CLASS;
                    break;
                default:
                    $this->logAndThrowException("Options class not specified for '$config->name'");
                    break;
            }
        }  // if ( ! isset($config->options_class) )

        // If the options class name does not include a namespace designation, use the namespace from
        // the action configuration.

        $optionsClassName = $config->options_class;

        if ( false === strstr($optionsClassName, '\\') ) {
            if ( isset($config->namespace) ) {
                $optionsClassName = $config->namespace .
                    ( strpos($config->namespace, '\\') != strlen($config->namespace) - 1 ? "\\" : "" ) .
                    $optionsClassName;
            }
        }

        if ( ! class_exists($optionsClassName) ) {
            $this->logAndThrowException("Options class '$optionsClassName' not found");
        }

        if ( ! class_exists($optionsClassName) ) {
            $this->logAndThrowException("Options class '$optionsClassName' not found");
        }

        // Create the options object and ensure it extends aOptions

        $options = new $optionsClassName();

        if ( ! $options instanceof aOptions ) {
            $this->logAndThrowException("$optionsClassName does not extend aOptions");
        }

        // Set up the options with whatever was included in the config. The factory and implementation
        // classes will check for required parameters.

        // Actions are enabled by default and do not require the "enabled" key, but it may still be
        // specified in the config.

        if ( ! isset($config->enabled) || $config->enabled ) {

            // Register the endpoints. Endpoints are global and only one endpoint will be created
            // for each unique key. We need to register the endpoints first because the actions will
            // need the keys when they are executed.

            $endpointKey = self::DATA_ENDPOINT_KEY;
            foreach ($config->$endpointKey as $endpointName => $endpointConfig) {
                try {
                    $this->addDataEndpoint($endpointConfig);
                } catch (Exception $e) {
                    $this->logAndThrowException(
                        "Error registering $endpointName endpoint for $sectionName '{$config->name}': "
                        . $e->getMessage()
                    );
                }
            }  // foreach ($config->$endpointKey as $endpointName => $endpointConfig)

            foreach ( $config as $key => $value ) {

                // The source, destination, and utility entries are data endpoints and need the key to
                // reference the endpoints.

                if ( self::DATA_ENDPOINT_KEY == $key ) {
                    foreach ($config->$endpointKey as $endpointName => $endpointConfig) {
                        if ( isset($endpointConfig->key) ) {
                            $options->$endpointName = $endpointConfig->key;
                        }
                    }
                } else {
                    $options->$key = $value;
                }
            }
        } else {
            // For actions that are not enabled, set minimal information needed for listing actions
            $options->enabled = false;
            $options->name = $config->name;
            if ( isset($config->description) ) {
                $options->description = $config->description;
            }
        }  // else ( $config->enabled )

        $this->addAction($sectionName, $options);

        return $this;

    }  // registerAction()

    /* ------------------------------------------------------------------------------------------
     * Add an action to the list of actions for the specified section.
     *
     * @param $sectionName The name of the section that we are adding the action to
     * @param $options The options for the action
     *
     * @returns This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function addAction($sectionName, aOptions $options)
    {
        $this->actionOptions[$sectionName][$options->name] = $options;
    }  // addAction()

    /* ------------------------------------------------------------------------------------------
     * Add a data endpoint if it hasn't already been added.  We will maintain a list of data endpoints
     * referenced by keys to use during the ingestion process so we don't maintain potentially 10's of
     * the same endpoint.
     *
     * @param stdClass $config Reference to the configuration section
     *
     * @return The data endpoint object
     * ------------------------------------------------------------------------------------------
     */

    private function addDataEndpoint(stdClass &$config)
    {
        // Set up the options with whatever was included in the config. The factory and implementation
        // classes will check for required parameters.

        $options = new DataEndpointOptions();
        foreach ( $config as $key => $value ) {
            $options->$key = $value;
        }

        // Register the endpoint if it hasn't already been

        $endpoint = DataEndpoint::factory($options, $this->logger);
        $endpointKey = $endpoint->getKey();

        if ( ! array_key_exists($endpointKey, $this->endpoints) ) {
            $this->endpoints[$endpointKey] = $endpoint;
        }

        // Register the key with the configuration
        $config->key = $endpointKey;

        return $endpoint;

    }  // addDataEndpoint()

    /* ==========================================================================================
     * Accessors
     * ==========================================================================================
     */

    /* ------------------------------------------------------------------------------------------
     * Get the list of enabled action names. This includes actions that are properly configured
     * and enabled.
     *
     * @param $sectionName The name of the section to examine.
     *
     * @return The list of enabled actions for the specified section, or FALSE if the section was
     *   not found.
     * ------------------------------------------------------------------------------------------
     */

    public function getEnabledActionNames($sectionName)
    {
        if ( ! $this->sectionExists($sectionName) ) {
            return false;
        }

        return array_reduce(
            $this->actionOptions[$sectionName],
            function ($carry, aOptions $item) {
                if ( $item->enabled ) {
                    array_push($carry, $item->name);
                }
                return $carry;
            },
            array()
        );

    }  // getEnabledActionNames()

    /* ------------------------------------------------------------------------------------------
     * Search for an action name across all sections and return the sections where it was found.
     *
     * @param $actionName The name of the action to search for.
     *
     * @return An array containing the sections where the action name was found, or FALSE if none
     *  was found.
     * ------------------------------------------------------------------------------------------
     */

    private function findActionSections($actionName)
    {
        $sectionNameMatches = array();

        foreach ( $this->actionOptions as $sectionName => $sectionActionOptions ) {
            if ( array_key_exists($actionName, $sectionActionOptions) ) {
                $sectionNameMatches[] = $sectionName;
            }
        }

        return ( 0 == count($sectionNameMatches) ? false : $sectionNameMatches );

    }  // findActionSections()

    /* ------------------------------------------------------------------------------------------
     * @param $actionName The name of the action to search for.
     * @param $sectionName Optional section name to look for the action
     *
     * @return TRUE if the action exists in the specified section name, or any section if
     *   no section name was provided. False otherwise.
     * ------------------------------------------------------------------------------------------
     */

    private function actionExists($actionName, $sectionName = null)
    {
        if ( null === $sectionName ) {
            return ( false !== $this->findActionSections($actionName) );
        } elseif ( ! $this->sectionExists($sectionName) ) {
            return false;
        } else {
            return array_key_exists($actionName, $this->actionOptions[$sectionName]);
        }

    }  // actionExists()

    /* ------------------------------------------------------------------------------------------
     * Get the list of configured action names. This includes all actions that are properly
     * configured whether they are enabled or disabled.
     *
     * @param $sectionName The name of the section to examine.
     *
     * @return The list of configured actions for the specified section, or FALSE if the section was
     *   not found.
     * ------------------------------------------------------------------------------------------
     */

    public function getConfiguredActionNames($sectionName)
    {
        if ( ! $this->sectionExists($sectionName) ) {
            return false;
        }

        return array_keys($this->actionOptions[$sectionName]);

    }  // getConfiguredActionNames()

    /* ------------------------------------------------------------------------------------------
     * Get the list of disabled action names. These are actions that are configured, but have been
     * marked as disabled.
     *
     * @param $sectionName The name of the section to examine.
     *
     * @return The list of disabled actions for the specified section, or FALSE if the section was
     *   not found.
     * ------------------------------------------------------------------------------------------
     */

    public function getDisabledActionNames($sectionName)
    {
        if ( ! $this->sectionExists($sectionName) ) {
            return false;
        }

        return array_diff(array_keys($this->actionOptions[$sectionName]), $this->getEnabledActionNames($sectionName));

    }  // getDisabledActionNames()

    /* ------------------------------------------------------------------------------------------
     * Get the list of option objects for the specified section. Only options for configured ingestors
     * are available.
     *
     * @param $sectionName The name of the section to examine.
     *
     * @return An array of option objects, or FALSE if the section name was not found
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionActionOptions($sectionName)
    {
        if ( ! $this->sectionExists($sectionName) ) {
            return false;
        }

        return $this->actionOptions[$sectionName];

    }  // getSectionActionOptions()

    /* ------------------------------------------------------------------------------------------
     * Get the list of option objects for the specified section. Only options for configured ingestors
     * are available.
     *
     * @param $sectionName The name of the section to examine.
     *
     * @return An array of option objects, or FALSE if the section name was not found
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionActionNames($sectionName)
    {
        if ( ! $this->sectionExists($sectionName) ) {
            return false;
        }

        return array_keys($this->actionOptions[$sectionName]);

    }  // getSectionActionNames()

    /* ------------------------------------------------------------------------------------------
     * Get an individual option object for the specified action in the specified section. If the
     * section is not provided all sections will be searched for the action. If an action is found
     * in multiple sections an exception will be thrown so a section name should be provided where
     * possible.
     *
     * @param $actionName The name of the action to examine.
     * @param $sectionName The name of the section to examine.
     *
     * @return An option object
     *
     * @throw Exception If the section is procided and does not exist, or the name does not exist in
     *   the provided section.
     * @throw Exception If the same action name was found in multuple sections.
     * ------------------------------------------------------------------------------------------
     */

    public function getActionOptions($actionName, $sectionName = null)
    {
        if ( null !== $sectionName ) {
            if ( ! $this->sectionExists($sectionName) ) {
                $this->logAndThrowException("Invalid section name '$sectionName'");
            } elseif ( ! $this->actionExists($actionName, $sectionName) ) {
                $this->logAndThrowException("Action '$actionName' not found in section '$sectionName'");
            }
        } else {
            $sectionList = $this->findActionSections($actionName);
            if ( count($sectionList) > 1 ) {
                $this->logAndThrowException(sprintf(
                    "Ambiguous action '%s' found in multiple sections '%s'",
                    $actionName,
                    implode("', '", $sectionList)
                ));
            } elseif ( false === $sectionList ) {
                $this->logAndThrowException("Action '$actionName' not found");
            }
            $sectionName = array_shift($sectionList);
        }

        return $this->actionOptions[$sectionName][$actionName];

    }  // getActionOptions()

    /* ------------------------------------------------------------------------------------------
     * Get a globally defined endpoint, or FALSE if it is not defined.
     *
     * @param $name The name of the global endpoint (e.g., utility, source, destination)
     *
     * @return An object implementing the iDataEndpoint interface or FALSE if the key was not found
     * ------------------------------------------------------------------------------------------
     */

    public function getGlobalEndpoint($name)
    {
        return ( array_key_exists($name, $this->globalEndpoints)
                 ? $this->endpoints[ $this->globalEndpoints[$name] ]
                 : false );
    }  // getGlobalEndpoint()

    /* ------------------------------------------------------------------------------------------
     * Get the list of data endpoints. Only data endpoints for enabled ingestors are available.
     *
     * @return An array of object implementing the iDataEndpoint interface
     * ------------------------------------------------------------------------------------------
     */

    public function getDataEndpoints()
    {
        return $this->endpoints;
    }  // getDataEndpoints()

    /* ------------------------------------------------------------------------------------------
     * Get the named data endpoint.
     *
     * @param string $name The name/identifier for the data endpoint
     *
     * @return An object implementing the iDataEndpoint interface or FALSE if the key was not found
     * ------------------------------------------------------------------------------------------
     */

    public function getDataEndpoint($name)
    {
        if ( null === $name ) {
            return false;
        }
        return ( array_key_exists($name, $this->endpoints) ? $this->endpoints[$name] : false );
    }  // getDataEndpoint()

    /* ------------------------------------------------------------------------------------------
     * @return The configured paths object.
     * ------------------------------------------------------------------------------------------
     */

    public function getPaths()
    {
        return $this->paths;
    }  // getPaths()
}  // class EtlConfiguration
