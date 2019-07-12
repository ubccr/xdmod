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

use Configuration\Configuration;
use Exception;
use stdClass;
use Log;
use ETL\aOptions;
use ETL\Ingestor\IngestorOptions;
use ETL\Aggregator\AggregatorOptions;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\EtlOverseerOptions;

class EtlConfiguration extends Configuration
{

    /**
     * Reserved keys in the top level of the configuration file. ETL section names cannot use one of
     * these keys.
     * @var array
     */
    private $etlConfigReservedKeys = array(
         'defaults',
         'endpoints',
         'paths',
         'global',
         'variables',
         'module'
     );

    /**
     * If this is a local configuration file we have the option of using global defaults from the parent.
     * @var stdclass
     */
    private $parentDefaults = null;

    /**
     * An array of data endpoints defined in the global defaults section. The keys are the endpoint
     * names (utility, source, destination, etc.) the values are endpoint keys used to reference the
     * $endpoints array.
     * @var array
     */
    private $globalEndpoints = array();

    /**
     * Associative array where the key is the generated endpoint key and the value is a DataEndpoint object.
     * @var array
     */
    private $endpoints = array();

    /**
     * An associative array where keys are section names and values are an array of action option
     * objects.
     * @var array
     */
    private $actionOptions = array();

    /**
     * An associative array containing options to either add to or override individual action
     * options. These will be applied to all actions, if present.
     * @var array
     */
    private $optionOverrides = null;

    /**
     * The default name to use for a module if not provided in the configuration file.
     * @var string
     */
    private $defaultModuleName = null;

    /**
     * A class containing path information for various configuration files and directories.
     * @var stdclass
     */
    private $paths = null;

    /** -----------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param $filename Name of the JSON configuration file to parse
     * @param $baseDir Base directory for configuration files. Overrides the base dir provided in
     *   the top-level config file
     * @param $logger A PEAR Log object or null to use the null logger.
     * @param $options An associative array of additional options passed from the parent. In
     *   addition to the options supported by Configuration, the following options are also
     *   supported:
     *   option_overrides: An array of key/value pairs (2-element arrays) containing options to
     *      either add to or override individual action options. These will be applied to all
     *      actions, if present.
     *   parent_defaults: The defaults class from the parent configuration file, if we are
     *      processing a configuration file in a subdirectory.
     *   default_module_name: The default name to use for a module if not provided in the
     *      configuration itself.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(
        $filename,
        $baseDir = null,
        Log $logger = null,
        array $options = array()
    ) {
        parent::__construct($filename, $baseDir, $logger, $options);

        foreach ( $options as $option => $value ) {
            if ( null === $value ) {
                continue;
            }
            switch ( $option ) {
                case 'option_overrides':
                    if ( ! is_array($value) ) {
                        $this->logAndThrowException(sprintf("%s must be an array, %s provided", $option, gettype($value)));
                    } elseif ( 0 !== count($value) ) {
                        $this->optionOverrides = $value;
                    }
                    break;

                case 'parent_defaults':
                    if ( ! is_object($value) ) {
                        $this->logAndThrowException(sprintf("%s must be an object, %s provided", $option, gettype($value)));
                    }
                    $this->parentDefaults = $value;
                    break;

                case 'default_module_name':
                    if ( ! is_string($value) ) {
                        $this->logAndThrowException(sprintf("%s must be a sting, %s provided", $option, gettype($value)));
                    }
                    $this->defaultModuleName = $value;
                    break;

                default:
                    break;
            }
        }

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @see Configuration::preTransformTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function preTransformTasks()
    {

        // At this point, the file has been parsed but keys have not been transformed. In
        // order to support macros in the JSON references we need access to the "paths"
        // key found in the main configuration file before we apply key transformations.

        $this->addBaseDirToPaths();

        if ( isset($this->parsedConfig->defaults->global->paths->local_config_dir) ) {
            $this->localConfigDir = $this->parsedConfig->defaults->global->paths->local_config_dir;
        }
        // Merge default values from the parent into defaults optionally specified for the local
        // config.  There can be a set of global defaults, plus one set for each section defined in
        // the configuration file (defaults are applied as long as the name of the entries in the
        // default and section blocks match).  If we are processing a local configuration file merge
        // the defaults from the parent with local defaults taking precedence. If local defaults are
        // not present use all of the parent defaults.
        //
        // NOTE: Since path variables must be available during the transform step the merging of
        // local and parent defaults needs to happen in preTransformTasks().

        if ( isset($this->parsedConfig->defaults) ) {

            // Note: The config object may contain nested objects so we cannot simply cast it to an
            // array and call array_replace_recursive(). json_decode() can convert objects to arrays
            // recursively and after the merge appears to correctly decode arrays and objects.

            $parentDefaults = ( null !== $this->parentDefaults ? json_decode(json_encode($this->parentDefaults), true) : array() );
            $localDefaults = json_decode(json_encode($this->parsedConfig->defaults), true);
            $this->parsedConfig->defaults = json_decode(json_encode(array_replace_recursive($parentDefaults, $localDefaults)));
        } elseif ( null !== $this->parentDefaults ) {
            $this->parsedConfig->defaults = $this->parentDefaults;
        }

        // Make all paths available as variables, although variables defined via the
        // 'config_variables' option (e.g., on the command line) still take precedence. The paths
        // need to be available for the transformers and can only contain variables specified by the
        // 'config_variables' option.

        foreach ( $this->parsedConfig->defaults->global->paths as $variable => $value ) {
            // Note that key transformers have not been run at this point so strip comments out of
            // the paths block.
            if ( 0 !== strpos('#', $variable) ) {
                $this->variableStore->$variable = $value;
            }
        }

        return parent::preTransformTasks();

    }  // preTransformTasks()

    /** -----------------------------------------------------------------------------------------
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

        $this->disambiguateActionNames();
        $config = $this->transformedConfig;
        $etlSectionNames = array_diff(array_keys(get_object_vars($config)), $this->etlConfigReservedKeys);

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
                $this->applyDefaultsToActionConfig($actionConfig, $sectionName, $this->transformedConfig->defaults);
            }
        }  // foreach ( $etlSectionNames as $typeName )

        // --------------------------------------------------------------------------------
        // Register the global utility endpoint in the main ETL configuration file because it may be
        // needed by the etl overseer script to look up resource ids from resource codes. We do not
        // register other global endpoints because these should only be registered if an enabled
        // action needs them.

        $this->endpoints = array();

        if ( ! $this->isLocalConfig && isset($this->transformedConfig->defaults->global->endpoints->utility) ) {
            $name = 'utility';
            try {
                $endpoint = $this->addDataEndpoint($this->transformedConfig->defaults->global->endpoints->utility);
                $this->globalEndpoints[$name] = $endpoint->getKey();
            } catch (Exception $e) {
                $this->logAndThrowException("Error registering default endpoint '$name': " . $e->getMessage());
            }
        }

        // --------------------------------------------------------------------------------
        // Register individual actions discovered in the configuration file

        foreach ( $etlSectionNames as $sectionName ) {

            // If the section key doesn't contain an object or array, skip it.
            if ( ! is_array($config->$sectionName) && ! is_object($config->$sectionName) ) {
                continue;
            }

            $this->addSection($sectionName, $config->$sectionName);
            $priorityVariables = $this->variableStore->toArray();

            foreach ( $config->$sectionName as &$actionConfig ) {

                // At this point, the variables specified in the action configuration don't contain
                // priority variables such as those defined on the command line or the path
                // variables so merge them together.

                if ( 0 != count($priorityVariables) ) {
                    if ( ! isset($actionConfig->variables) ) {
                        $actionConfig->variables = $priorityVariables;
                    } else {
                        $actionConfig->variables = (object) array_replace(
                            (array) $actionConfig->variables,
                            $priorityVariables
                        );
                    }
                }

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

    /**
     * Disambiguate ETL action names by pre-pending the (optional) module name and pipeline name to
     * the action: <module> + '.' + <pipeline> + '.' + <action>
     *
     * Also enforce that pipeline and action names cannot contain dots.
     */

    protected function disambiguateActionNames()
    {
        $config = $this->transformedConfig;
        $etlSectionNames = array_diff(array_keys(get_object_vars($config)), $this->etlConfigReservedKeys);
        $moduleName = ( isset($config->module) ? $config->module : $this->defaultModuleName );
        $modulePrefix = ( null !== $moduleName ? sprintf("%s.", $moduleName) : "" );

        foreach ( $etlSectionNames as $sectionName ) {

            $normalizedSectionName = sprintf("%s%s", $modulePrefix, $sectionName);

            // The section name cannot contain a dot.

            if ( false !== strpos($sectionName, '.') ) {
                throw new Exception(
                    sprintf("Pipeline names cannot contain dots: '%s'", $sectionName)
                );
            }

            // Normalize the section name, if needed.

            if ( $normalizedSectionName != $sectionName ) {
                $config->$normalizedSectionName = $config->$sectionName;
                unset($config->$sectionName);

                // Update any default sections referencing the non-normalized pipeline name.

                if ( isset($config->defaults->$sectionName) ) {
                    $config->defaults->$normalizedSectionName = $config->defaults->$sectionName;
                    unset($config->defaults->$sectionName);
                }
            }

            foreach ( $config->$normalizedSectionName as $actionConfig ) {

                // If the action name already contains the section prefix do not re-add it

                $actionName = $actionConfig->name;
                $normalizedActionName = sprintf('%s.%s', $normalizedSectionName, $actionName);

                // If the action name contains a dot, split it on the dot and confirm that the prefix is
                // the pipeline name and there are no dots in the action name.

                if ( false !== strpos($actionName, '.') ) {
                    throw new Exception(
                        sprintf("Action names cannot contain dots: '%s'", $actionName)
                    );
                }

                if ( $normalizedActionName != $actionName ) {
                    $actionConfig->name = $normalizedActionName;
                }
            }
        }

    }  // disambiguateActionNames()

    /** -----------------------------------------------------------------------------------------
     * Handle creation of an EtlConfiguration object for the given class.
     *
     * @see Configuration::processLocalConfig()
     * ------------------------------------------------------------------------------------------
     */

    protected function processLocalConfig($localConfigFile)
    {
        $options = array(
            'local_config_dir'   => $this->localConfigDir,
            'is_local_config'    => true,
            'option_overrides'   => $this->optionOverrides,
            'variable_store'     => $this->variableStore,
            'parent_defaults'    => $this->transformedConfig->defaults,
            'default_module_name' => $this->defaultModuleName
        );

        return EtlConfiguration::factory($localConfigFile, $this->baseDir, $this->logger, $options);

    }  // processLocalConfig()

    /** -----------------------------------------------------------------------------------------
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
                        "Duplicate action '%s' found in '%s' secti/localon '%s'",
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

    /**
     * @see Configuration::postMergeTasts()
     */

    protected function postMergeTasks()
    {
        // Clean up default values that are no longer needed after processing the config
        unset($this->parsedConfig->defaults);
        unset($this->transformedConfig->defaults);
        return parent::postMergeTasks();
    }  // postMergeTasks()

    /** -----------------------------------------------------------------------------------------
     * Clean up intermediate information that we don't need to keep around after processing. This
     * includes parsed and constructed JSON as well as defaults.
     * ------------------------------------------------------------------------------------------
     */

    public function cleanup()
    {
        parent::cleanup();
        $this->parentDefaults = null;
    }  // cleanup()

    /** -----------------------------------------------------------------------------------------
     * Perform verification that the paths block exists in the global configuration and apply the
     * base path to all relative paths in the "paths" block.
     * ------------------------------------------------------------------------------------------
     */

    protected function addBaseDirToPaths()
    {
        // The global configuration file must have a paths block set and it must be an object.

        if ( ! $this->isLocalConfig && ! isset($this->parsedConfig->paths) ) {
            $this->logAndThrowException(sprintf(
                "Required configuration 'paths' not found in config file: %s",
                $this->filename
            ));
        } elseif ( isset($this->parsedConfig->paths) && ! is_object($this->parsedConfig->paths) ) {
            $this->logAndThrowException(sprintf(
                "Configuration 'paths' must be an object in config file: %s",
                $this->filename
            ));
        }

        // Warn if the paths block is found inside of the defaults rather than outside.

        if ( isset($this->parsedConfig->defaults->paths) && is_object($this->parsedConfig->defaults->paths) ) {
            $this->logger->warning("Configuration 'paths' found in 'defaults' but expected at root");
        }

        // Add the base directory to each path

        if ( ! isset($this->parsedConfig->paths) ) {
            return;
        }

        foreach ( $this->parsedConfig->paths as $key => &$value ) {
            $value = \xd_utilities\qualify_path($value, $this->baseDir);
            $value = \xd_utilities\resolve_path($value);
        }
        unset($value); // Sever the reference with the last element

        // Add the base directory to the paths configuration so it is easily accessible
        $this->parsedConfig->paths->base_dir = $this->baseDir;

        // Place the path block into the global defaults section so it is automatically propagated
        // to all actions when defaults are applied and remove it from the config.

        if ( ! isset($this->parsedConfig->defaults) ) {
            $this->parsedConfig->defaults = new stdClass;
        }

        if ( ! isset($this->parsedConfig->defaults->global) ) {
            $this->parsedConfig->defaults->global = new stdClass;
        }

        $this->parsedConfig->defaults->global->paths = $this->parsedConfig->paths;
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

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
     * Apply both global and section-specific (local) defaults to a action config block.
     *
     * @param $actionConfig Reference to the configuration block for the action
     * @param $sectionName The name of the section for local defaults
     * @param $defaults An associative array containing all defaults (both global and local)
     * ------------------------------------------------------------------------------------------
     */

    protected function applyDefaultsToActionConfig(stdClass &$actionConfig, $sectionName, stdClass $defaults)
    {
        // Apply defaults where applicable. We will check for errors later when instantiating the
        // individual classes.

        // The order of precedence is local action defaults, followed by section and then global
        // defaults. Options specified on the command line override all of these but those are
        // handled prior to action instantiation.

        $defaultSectionKeys = array($sectionName, 'global');

        foreach ( $defaultSectionKeys as $defaultSectionKey ) {

            if ( ! isset($defaults->$defaultSectionKey) ) {
                continue;
            }

            foreach ( $defaults->$defaultSectionKey as $propertyKey => $propertyValue ) {

                // The action config doesn't have the property at all, set it.

                if ( ! isset($actionConfig->$propertyKey) ) {
                    $actionConfig->$propertyKey = $propertyValue;
                } elseif ( in_array($propertyKey, array('endpoints', 'variables')) ) {

                    if ( ! is_object($propertyValue) ) {
                        $this->logAndThrowException(
                            sprintf(
                                "Expected value of %s to be an object, %s provided",
                                $propertyKey,
                                gettype($propertyValue)
                            )
                        );
                    }

                    // Merge in any key definitions that are not already present in the action
                    // config

                    foreach ( $propertyValue as $key => $value ) {
                        if ( ! isset($actionConfig->$propertyKey->$key) ) {
                            $actionConfig->$propertyKey->$key = $value;
                        }
                    }
                }
            }
        }

        // Now apply default paths to the endpoints.

        if ( isset($defaults->global->paths) && isset($actionConfig->endpoints) ) {
            foreach ( $actionConfig->endpoints as $endpointName => &$endpointConfig ) {
                if ( ! isset($endpointConfig->paths) ) {
                    $endpointConfig->paths = $defaults->global->paths;
                }
            }
        }

    }  // applyDefaultsToActionConfig()

    /** -----------------------------------------------------------------------------------------
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

        // If the options class name does not include a namespace designation, use the namespace from
        // the action configuration.

        if ( ! isset($config->options_class) ) {
            $this->logAndThrowException("Key 'options_class' not defined for $actionName");
        }

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

        // Create the options object and ensure it extends aOptions

        $options = new $optionsClassName();

        if ( ! $options instanceof aOptions ) {
            $this->logAndThrowException("$optionsClassName does not extend aOptions");
        }

        // Set up the options with whatever was included in the config. The factory and implementation
        // classes will check for required parameters.

        // Register the endpoints. Endpoints are global and only one endpoint will be created
        // for each unique key. We need to register the endpoints first because the actions will
        // need the keys when they are executed.

        foreach ($config->endpoints as $endpointName => $endpointConfig) {
            try {
                $this->addDataEndpoint($endpointConfig);
            } catch (Exception $e) {
                $this->logAndThrowException(
                    "Error registering $endpointName endpoint for $sectionName '{$config->name}': "
                    . $e->getMessage()
                );
            }
        }  // foreach ($config->endpoints as $endpointName => $endpointConfig)

        foreach ( $config as $key => $value ) {

            // The source, destination, and utility entries are data endpoints and need the key to
            // reference the endpoints.

            if ( 'endpoints' == $key ) {
                foreach ($config->endpoints as $endpointName => $endpointConfig) {
                    if ( isset($endpointConfig->key) ) {
                        $options->$endpointName = $endpointConfig->key;
                    }
                }
            } else {
                $options->$key = $value;
            }
        }

        $this->addAction($sectionName, $options);

        return $this;

    }  // registerAction()

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
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

        return $this->endpoints[$endpointKey];

    }  // addDataEndpoint()

    /* ==========================================================================================
     * Accessors
     * ==========================================================================================
     */

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
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
            $parts = $this->parseActionName($actionName);
            $sectionName = $parts['section'];
        }

        return isset($this->actionOptions[$sectionName][$actionName]);

    }  // actionExists()

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
     * Get an the option object for the specified action in the specified section. If the section
     * is not provided it will be parsed from the action name. Action names have the format
     * module.section.action.
     *
     * @param $actionName The name of the action to examine.
     * @param $sectionName The name of the section to examine.
     *
     * @return An option object
     *
     * @throw Exception If the section is provided and does not exist, or the name does not exist in
     *   the provided section.
     * ------------------------------------------------------------------------------------------
     */

    public function getActionOptions($actionName, $sectionName = null)
    {
        if ( null === $sectionName ) {
            $parts = $this->parseActionName($actionName);
            $sectionName = $parts['section'];
        }

        if ( null === $sectionName ) {
            $this->logAndThrowException(sprintf("Could not determine section from action name '%s'", $actionName));
        }

        if ( ! isset($this->actionOptions[$sectionName][$actionName]) ) {
            $this->logAndThrowException(sprintf("Action '%s' not found in section '%s'", $actionName, $sectionName));
        }

        return $this->actionOptions[$sectionName][$actionName];

    }  // getActionOptions()

    /** ------------------------------------------------------------------------------------------
     * Parse an action name into component parts (module, section, action). If the name does not
     * include a module name, return NULL for the module. For example:
     *   module:        module
     *   short_section: section
     *   section:       module.section
     *   short_action:  action
     *   action:        module.section.action
     *
     * @param string $actionName An action name with the format [module.]section.action
     * @return array|bool An associative array containing the keys module, section, action,
     *   short_section, and short_action with values set to the corresponding part of the
     *   action name. Return FALSE if the action name was malformed.
     * ------------------------------------------------------------------------------------------
     */

    private function parseActionName($actionName)
    {
        $parts = explode('.', $actionName);
        if ( 3 == count($parts) ) {
            return array(
                'module'        => $parts[0],
                'short_section' => $parts[1],
                'section'       => sprintf('%s.%s', $parts[0], $parts[1]),
                'short_action'  => $parts[2],
                'action'        => $actionName
            );
        } elseif ( 2 == count($parts) ) {
            return array(
                'module'        => null,
                'short_section' => $parts[0],
                'section'       => $parts[0],
                'short_action'  => $parts[1],
                'action'        => $actionName
            );
        }

        return false;

    } // parseActionName()

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
     * Get the list of data endpoints. Only data endpoints for enabled ingestors are available.
     *
     * @return An array of object implementing the iDataEndpoint interface
     * ------------------------------------------------------------------------------------------
     */

    public function getDataEndpoints()
    {
        return $this->endpoints;
    }  // getDataEndpoints()

    /** -----------------------------------------------------------------------------------------
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

    /** -----------------------------------------------------------------------------------------
     * @return The configured paths object.
     * ------------------------------------------------------------------------------------------
     */

    public function getPaths()
    {
        return $this->paths;
    }  // getPaths()

    /**
     * @see iConfiguration::__sleep()
     */

    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }
}  // class EtlConfiguration
