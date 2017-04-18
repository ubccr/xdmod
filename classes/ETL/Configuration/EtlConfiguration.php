<?php
/* ==========================================================================================
 * Read and parse the ETL process configuration file (JSON). The configuration file contains a
 * "defaults" section and one or more action defintion (ingestors, aggregators, etc.)
 * sections. Each section defines an array of actions. The defaults section can contain entries
 * for each action section as well as a global section where source, destination, and utility data
 * endpoints can be defined. Optional sub-configuration files in a directory are also supported with
 * the options defined being berged back into the global namespace. Action sections can be
 * identified using any name but special support is included for "ingestors", and "aggregators"
 * sections including class constants and default options objects.
 *
 * Default options specific to any section can be included in the "defaults" section using the same
 * name as the target section.  When applying defaults, we start with the options configured in the
 * action definition and then apply defaults specific to that section. Global defaults are then
 * applied. This ensures that options appearing in the action configuration take precedence,
 * followed by section-specific defaults, followed by global defaults.
 *
 * NOTE: There is a single GLOBAL namespace for ingestors, aggregators, groups, and data endpoints.
 * NOTE: Any "comment" properties are ignored.
 * NOTE: The "defaults" and "global" sections are reserved and cannot be used except in the defaults
 *   block.
 *
 * The process for parsing the configuration is as follows:
 *
 * 1. Read and parse the JSON configuration.  The "defaults" section is reserved for defining
 *    default options either globally or for individual section names.
 * 2. Examine the defaults (global, ingestor, aggregator, etc.) and apply the defaults to each local
 *    section
 *    - Defaults will not override existing definitions (local definitions take priority)
 *    - Common defaults include source and destination endpoints, namespaces, and aggregation units
 * 3. Register the global utility endpoint (e.g., modw)
 * 4. Register enabled actions
 *    - Register source, destination, and utility endpoints (Data endpoints are referenced globally by
 their key)
 *    - Create an options object and insert the values into it
 * 5. If a subdirectory for configuration files has been defined, parse each .json file in the
 *    subdirectory and generate an EtlConfiguration object. Merge the values of each configuration
 *    into the main object.
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

    // TRUE if this is a sub-configuration file
    private $isSubConfig = false;

    // If this is a sub-configuration file we have the option of using global defaults from the parent
    // private $parentDefaults = array();
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

    // An associative array where keys are section names and values are the names of enabled actions
    // in that section.
    private $enabledActionNames = array();

    // An associative array where keys are section names and values are the names of configured
    // actions in that section.
    private $configuredActionNames = array();

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
     *   is_subconfig: TRUE if this is a sub-configuration file
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
        parent::__construct($filename, $baseDir, $logger);

        $this->isSubConfig = ( array_key_exists('is_subconfig', $options) && $options['is_subconfig'] );

        if ( array_key_exists('option_overrides', $options) && null !== $options['option_overrides'] ) {
            if ( ! is_array($options['option_overrides']) ) {
                $this->logAndThrowException("Option overrides must be an array");
            } else {
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
     * Initialize the configuration object. Parse the configuration file and process any
     * subdirectories.
     */

    public function initialize()
    {
        parent::initialize();

        // If this is the main configuration file and a subdirectory is specified parse each config file
        // in it and merge the results back into the main object.
        //
        // NOTE: Ingestors, aggregators, and endpoints are part of a global namespace and must have
        // unique names.

        if ( ! $this->isSubConfig && isset($this->paths->local_config_dir) ) {
            $subConfigDir = $this->paths->local_config_dir;

            if ( ! is_dir($subConfigDir) ) {
                $this->logAndThrowException("ETL configuration directory not found '$subConfigDir'");
            }

            if ( false === ($dh = @opendir($subConfigDir)) ) {
                $this->logAndThrowException("Error opening configuration directory '$subConfigDir'");
            }

            // Examine the subdirectory for .json files and parse each one, then merge the results back
            // into this object

            while ( false !== ( $file = readdir($dh) ) ) {
                $len = strlen($file);
                $pos = strrpos($file, ".json");
                if ( false === $pos || ($len - $pos) != 5 ) {
                    continue;
                }
                $subConfigFile = $subConfigDir . "/" . $file;

                $options = array(
                    'is_subconfig' => true,
                    'option_overrides' => $this->optionOverrides,
                    'parent_defaults' => $this->localDefaults
                );
                $subConfig = new EtlConfiguration($subConfigFile, $this->baseDir, $this->logger, $options);
                $subConfig->setLogger($this->logger);
                $subConfig->initialize();

                // Merge the transformed JSON configuration into the parent so we can get
                // the final generated JSON.

                foreach ( $subConfig->getTransformedConfig() as $k => $v ) {
                    $this->transformedConfig->$k = $v;
                }

                // Merge everything back into the main object

                foreach ( $subConfig->getDataEndpoints() as $key => $value ) {
                    if ( ! array_key_exists($key, $this->endpoints) ) {
                        $this->endpoints[$key] = $value;
                    }
                }  // foreach ( $subConfig->getDataEndpoints() as $key => $value )

                // Merge in sections from the sub-config. A sub-config may contain a duplicate
                // section name, in which case we will merge the actions with the master config. Do
                // not allow duplicate action names in the same section.

                foreach ( $subConfig->getSectionNames() as $sectionName ) {

                    $this->addSection($sectionName);

                    if ( false === ($sectionActionOptions = $subConfig->getSectionActionOptions($sectionName)) ) {
                        $this->logAndThrowException(
                            sprintf("Section '%s' not found in subconfig file '%s'", $sectionName, $subConfigFile)
                        );
                    }

                    foreach ( $sectionActionOptions as $actionName => $options ) {
                        if ( array_key_exists($actionName, $this->actionOptions[$sectionName]) ) {
                            $this->logAndThrowException(
                                sprintf("WARNING: Duplicate action '%s' found in %s section '%s', skipping.", $actionName, $subConfigFile, $sectionName)
                            );
                        } else {
                            $this->actionOptions[$sectionName][$actionName] = $options;
                            $this->configuredActionNames[$sectionName][] = $actionName;
                            if ($options->enabled) {
                                $this->enabledActionNames[$sectionName][] = $actionName;
                            }
                        }
                    }
                }  // foreach ( $subConfig->getSectionNames() as $sectionName )

                $subConfig->cleanup();

            }  //  while ( false !== ( $file = readdir($dh) ) )

            closedir($dh);

        }  // if ( null !== $confSubdirectory )

    }  // initialize()

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
     *
     * @param $key The key that we are processing
     * @param $value The value associated with the key
     * @param $config The current state of the constructed configuration file
     *
     * @return The updated configuration file after processing the handler
     * ------------------------------------------------------------------------------------------
     */

    protected function addBaseDirToPaths()
    {
        if ( $this->isSubConfig ) {
            return;
        }

        // The paths object must be present

        if ( ! isset($this->transformedConfig->paths) ) {
            $this->logAndThrowException("Required configuration 'paths' not found in config file");
        } elseif ( ! is_object($this->transformedConfig->paths) ) {
            $this->logAndThrowException("Configuration 'paths' must be an object");
        }

        foreach ( $this->transformedConfig->paths as $key => &$value ) {
            $value = \xd_utilities\qualify_path($value, $this->baseDir);
        }
        unset($value); // Sever the reference with the last element

        // Add the base directory to the paths configuration so it is easily accessible
        $this->transformedConfig->paths->base_dir = $this->baseDir;

        // Place the path block into the global defaults so it is automatically propogated to all
        // actions when defaults are applied.

        if ( ! isset($this->transformedConfig->defaults) ) {
            $this->transformedConfig->defaults = new stdClass;
            $this->transformedConfig->defaults->global = new stdClass;
        } elseif ( ! isset($this->transformedConfig->defaults->global) ) {
            $this->transformedConfig->defaults->global = new stdClass;
        }

        $this->transformedConfig->defaults->global->paths = $this->transformedConfig->paths;

        // Save it for later and remove it from the config.

        $this->paths = $this->transformedConfig->paths;
        unset($this->transformedConfig->paths);

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
     * Parse the configuration file.
     *
     * @param $force TRUE if the configuration file should be re-parsed.
     *
     * @throw Exception If the file is does not exist or is not readable
     * @throw Exception If there is an error parsing the file
     * ------------------------------------------------------------------------------------------
     */

    public function parse($force = false)
    {
        parent::parse();

        // At this point, the file has been parsed but keys have not been transformed. In
        // order to support macros in the JSON references we need access to the "paths"
        // key found in the main configuration file before we apply key transformations.

        $this->addBaseDirToPaths();

        // Now that we have access to the base paths, remove the JSON ref transformer and
        // replace it with one that understands path macros.

        $this->deleteKeyTransformer('ETL\Configuration\JsonReferenceTransformer');
        $this->addKeyTransformer(new JsonReferenceWithMacroTransformer($this->logger));
        $this->processKeyTransformers($this->transformedConfig);

        // ------------------------------------------------------------------------------------------
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
        // in the default and section blocks match.  If we are processing a sub-configuration file merge
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

            // If the section key doesn't contain an object or array, skip it.
            if ( ! is_array($config->$sectionName) && ! is_object($config->$sectionName) ) {
                continue;
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
        // Register the global default endpoints (the overseer script needs access to the utility
        // endpoint) but note that individual actions may define their own.

        $this->endpoints = array();

        if ( isset($this->localDefaults->global->endpoints) ) {
            foreach ( $this->localDefaults->global->endpoints as $name => $endpointConfig ) {
                try {
                    $endpoint = $this->addDataEndpoint($endpointConfig);
                    $this->globalEndpoints[$name] = $endpoint->getKey();
                } catch (Exception $e) {
                    $this->logAndThrowException("Error registering default endpoint '$name': " . $e->getMessage());
                }
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
                    $this->addAction($actionConfig, $sectionName);
                } catch ( Exception $e ) {
                    $actionName = ( isset($actionConfig->name) ? $actionConfig->name : "" );
                    $this->logAndThrowException(
                        "Error adding action '$actionName' in section '$sectionName': " . $e->getMessage()
                    );
                }
            }
        }  // foreach ( $etlSectionNames as $sectionName )

        $this->transformedConfig = $config;

        return true;

    }  // parse()

    /* ------------------------------------------------------------------------------------------
     * Add a new section to the internal data structures if it doesn't already exist
     *
     * @param $name The name of the new section
     * @param $data The data associated with the new section
     *
     * @return This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function addSection($name, $data = null)
    {
        if ( array_key_exists($name, $this->actionOptions) || in_array($name, $this->etlConfigReservedKeys) ) {
            return $this;
        }

        $this->sectionNames[] = $name;
        $this->actionOptions[$name] = array();
        $this->enabledActionNames[$name] = array();
        $this->configuredActionNames[$name] = array();

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
                if ( ! isset($endpointconfig->paths) ) {
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
     * ------------------------------------------------------------------------------------------
     */

    protected function addAction(stdClass &$config, $sectionName)
    {
        // Verify the section name was provided

        if ( null === $sectionName || empty($sectionName) ) {
            $this->logAndThrowException("Empty or null ETL section name");
        } elseif ( ! isset($config->name) ) {
            $this->logAndThrowException("Action name not set");
        }

        // Verify that the action name was not already provided for this section

        $actionName = $config->name;

        if ( array_key_exists($actionName, $this->actionOptions[$sectionName]) ) {
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

        // Register the endpoints. Endpoints are global and only one endpoint will be created for each
        // unique key. We need to register the endpoints first because the actions will need the keys
        // when they are executed.

        if ( $config->enabled ) {

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
            // For actions that are not configured, set minimal information needed for listing actions
            $options->enabled = false;
            $options->name = $config->name;
            if ( isset($config->description) ) {
                $options->description = $config->description;
            }
        }  // else ( $config->enabled )

        $this->actionOptions[$sectionName][$actionName] = $options;
        $this->configuredActionNames[$sectionName][] = $actionName;
        if ( $config->enabled ) {
            $this->enabledActionNames[$sectionName][] = $actionName;
        }

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

            $endpointName = $endpoint->getName();
            foreach ( $this->endpoints as $check ) {
                if ( $check->getName() ==  $endpointName ) {
                    $this->logger->warning("Duplicate Data Endpoint name '{$endpointName}'");
                }
            }

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
        if ( ! array_key_exists($sectionName, $this->enabledActionNames) ) {
            return false;
        }
        return $this->enabledActionNames[$sectionName];
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

        foreach ( $this->configuredActionNames as $sectionName => $sectionActionNames ) {
            if ( in_array($actionName, $sectionActionNames) ) {
                $sectionNameMatches[] = $sectionName;
            }
        }

        return ( 0 == count($sectionNameMatches) ? false : $sectionNameMatches );

    }  // findActionSections()

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
        if ( ! array_key_exists($sectionName, $this->configuredActionNames) ) {
            return false;
        }
        return $this->configuredActionNames[$sectionName];
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
        if ( ! array_key_exists($sectionName, $this->configuredActionNames) ) {
            return false;
        }
        return  array_diff($this->configuredActionNames[$sectionName], $this->enabledActionNames[$sectionName]);
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
        if ( ! array_key_exists($sectionName, $this->actionOptions) ) {
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
        if ( ! array_key_exists($sectionName, $this->actionOptions) ) {
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
            if ( ! array_key_exists($sectionName, $this->actionOptions) ) {
                $this->logAndThrowException("Invalid section name '$sectionName'");
            } elseif ( ! array_key_exists($actionName, $this->actionOptions[$sectionName]) ) {
                $this->logAndThrowException("Action '$actionName' not found in section '$sectionName'");
            }
        } else {
            $sectionList = $this->findActionSections($actionName);
            if ( count($sectionList) > 1 ) {
                $this->logAndThrowException(
                    "Ambiguous action '$actionName' found in multiple sections '"
                    . implode("', '", $sectionList)
                    . "'"
                );
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
