<?php
/** =========================================================================================
 * Read and parse a JSON configuration file containing keys with associated values
 * (scalar, object, array). Values that are objects are considered to be fixed entities
 * that define something in the configuration. Key transformers may also be added allowing
 * us to dynamically extend functionality and transform keys and their associated
 * values. For example, a comment transformer may strip comments from the file so
 * downstream processing does not need to deal with them and a reference transformer may provide
 * the ability to reference keys/data within other files to promote reuse.
 *
 * No interpretation of the data is performed in this class. The file is simply parsed,
 * transformed, and put into a data structure with section (key) names and the data associated
 * with each section. During processing, the data is kept in the $transformedConfig property but is
 * cleared in Configuration::cleanup(). To interpret the data, a child class should override
 * Configuration::interpretData().
 *
 * The process for applying transformers to the configuration is as follows:
 *
 * 1. Define key transformers that implement the `iConfigFileKeyTransformer` interface.
 *    Multiple transformers could be defined for the same key.
 * 2. Add transformers to the Config object using addKeyTransformer(). Transformers will be
 *    processed in the order that they are added.
 * 3. Parse the JSON file
 * 4. Traverse the keys of the JSON file, checking all transformers against each key
 *    4a. If the transformer matches the key, process the value
 *    4b. The key and value will be replaced by the values returned by the transformer. Note
 *        that both keys and values may be altered and may be set to `null`.
 *        - If the returned key and value is `null`, remove the key/value pair from the JSON.
 *        - If the key is `null` but the value is set, replace the **entire object** with the
 *          resulting value.
 *        - If the key and value are both set replace them both.
 *    4c. If a key transformer returns `false` do not process any other transformers.
 *    4d. Recursively traverse keys in the returned JSON and apply transformers to the result.
 *
 * To make it easier to extend this class for task-specific configuration files, the
 * following methods are available for overwriting by child classes. This allows
 * additional processing and/or interpretation of the data.
 *
 * 1. preTransformTasks()
 *
 * Perform any pre-transformation tasks such as adding key transformers.
 *
 * 2. interpretData()
 *
 * This is an empty method and is supplied so that a child class may apply any logic to interpret
 * the configuration data such as handling defaults, registering ETL actions, etc.
 *
 * 3. processLocalConfig()
 *
 * Local configuration files should be processed instantiating the appropriate class
 * (typically the class used to process the main file) and passing the correct options.
 *
 * 4. merge()
 *
 * Merge data from local configuration files into the global namespace, calling cleanup() on local
 * files.
 *
 * 5. postMergeTasks()
 *
 * Perform any tasks that need to occur after the merging of the local configuration objects (if
 * any) into the global configuration object. This is useful for massaging the final configuration
 * object.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-15
 *
 * @date 2017-04-11 - Added dynamic key transformers
 *
 * @date 2017-04-18 - Moved parsing of local configuration files into this class
 *
 * @date 2018-03-09 - Added postMergeTasks()
 * ==========================================================================================
 */

namespace Configuration;

// PEAR logger
use CCR\Loggable;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\VariableStore;
use Exception;
use Log;
use stdClass;
use Traversable;

class Configuration extends Loggable implements \Iterator
{
    // NOTE: Any properties that need to be accessed in a transformer defined in a subclass cannot be
    // private.

    /**
     * Key transformers will be used to dynamically add functionality for matching keys and
     * must implement the iConfigFileKeyTransformer interface.
     * @var array(iConfigFileKeyTransformer)
     */

    private $keyTransformers = array();

    /**
    * Configuration file name
    * @var string
    */

    protected $filename = null;

    /**
    * The base directory for any paths that are not fully qualified
    * @var string
    */

    protected $baseDir = null;

    /**
     * The parsed configuration file prior to manipulation
     * @var stdClass
     */

    protected $parsedConfig = null;

    /**
     * The configuration constructed from the parsed file after processing any transformers and
     * performing manipulations. Note that this is cleared if cleanup() is called.
     * @var stdClass
     */

    protected $transformedConfig = null;

    /**
     * An associative array where keys are section names and values the data associated with that
     * section.
     * @var array(stdclass)
     */

    protected $sectionData = array();

    /**
     * TRUE if this is a local configuration file as opposed to the main configuration file
     * @var boolean
     */

    protected $isLocalConfig = false;

    /**
     * Directory to look for local configuration files (e.g. sub-configs)
     * @var string
     */

    protected $localConfigDir = null;

    /**
     * An associative array of options that was passed in from the parent. This is useful for
     * providing additional customized information to the implementation such as additional
     * parameters used by child classes or variables and values that the transformers may use to
     * support variable substitution.
     * @var array
     */

    protected $options = array();

    /**
     * A collection of variable names and values available for substitution during the ETL process.
     * Note that the contents of this collection may change over time as variables can be added via
     * the command line, configuration files, or ETL actions themselves.
     * @var VariableStore
     */

    protected $variableStore = null;

    /**
     * @var boolean TRUE if the configuration has already been initialized.
     */

    protected $initialized = false;

    /**
     * Will force the results of `parse` to be an array.
     *
     * @var boolean
     */
    protected $forceArrayReturn = false;

    /** -----------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param string $filename Name of the JSON configuration file to parse
     * @param string $baseDir Base directory for configuration files. Overrides the base dir provided in
     *   the top-level config file. If not set, use the same directory as the config file.
     * @param Log $logger A PEAR Log object or null to use the null logger.
     * @param array $options An associative array of additional options passed from the parent.
     *   These include, but are not limited to:
     *   local_config_dir: Directory to look for local configuration files
     *   is_local_config: TRUE if this filename is a local config file as opposed to the global file
     *   variable_store: An existing VariableStore object that will be used to initialize the list
     *     of available configuration variables.  This is useful when passing existing variables
     *     into a new Configuration object such as parsing local configuration files.
     *   config_variables: An associative array of variables=value pairs that can be substituted in
     *     the configuration file.  These variables take precedence over any that were passed in
     *     via the variable_store option.
     *
     *   DEPRECATED:
     *   variables: An associative array of variables and their value. These may be used by
     *     transformers to support variable substitution. NOTE: JsonReferenceTransforer must
     *     be modified to use the VariableStore.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(
        $filename,
        $baseDir = null,
        Log $logger = null,
        array $options = array()
    ) {
        parent::__construct($logger);

        if ( empty($filename) ) {
            $this->logAndThrowException("Configuration filename cannot be empty");
        }

        // If the base directory was provided use that value and ensure the filename is
        // fully qualified. If not provided, use the dirname of the configuration filename
        // (which may be the current directory) and qualify the filename with the current
        // directory so the correct, fully qualified path, shows in the logs.

        if ( null === $baseDir ) {
            // This will be used for the main config file, any sub-files should have the
            // base explicitly dir passed to them.
            $this->baseDir = \xd_utilities\qualify_path(dirname($filename), getcwd());
            $this->filename = \xd_utilities\qualify_path($filename, getcwd());
        } else {
            $this->baseDir = $baseDir;
            $this->filename = \xd_utilities\qualify_path($filename, $this->baseDir);
        }

        if ( isset($options['local_config_dir']) ) {

            // Before continuing, make sure that the specified directory actually exists. It not
            // existing could have unexpected consequences for an XDMoD installation.
            if (!is_dir($options['local_config_dir'])) {
                $this->logAndThrowException(sprintf("Unable to find the specified local configuration directory: %s", $options['local_config_dir']));
            }

            $this->localConfigDir = $options['local_config_dir'];
        } else {

            // Set $localConfigDir to the default value.
            $this->localConfigDir = implode(DIRECTORY_SEPARATOR, array($this->baseDir, sprintf("%s.d", basename($filename, '.json'))));
        }

        $this->isLocalConfig = ( isset($options['is_local_config']) && $options['is_local_config'] );

        $this->options = $options;

        // Clean up directory paths
        $this->filename = \xd_utilities\resolve_path($this->filename);
        $this->baseDir = \xd_utilities\resolve_path($this->baseDir);
        $this->localConfigDir = \xd_utilities\resolve_path($this->localConfigDir);

        if ( isset($options['variable_store']) && $options['variable_store'] instanceof VariableStore ) {
            $this->variableStore = $options['variable_store'];
        } else {
            $this->variableStore = new VariableStore();
        }

        // Make any configuration variables available immediately and override any existing
        // variables. These will then be available to any local configuration files.

        if ( isset($options['config_variables']) && is_array($options['config_variables']) ) {
            foreach ( $options['config_variables'] as $variable => $value ) {
                $this->variableStore->overwrite($variable, $value);
            }
        }

        if ( isset($options['force_array_return']) ) {
            $this->forceArrayReturn = $options['force_array_return'];
        }
    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * Initialize the configuration objecton.
     *
     * @param boolean $force TRUE to force re-initialization of the configuration even if it has
     *   previously been initialized.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize($force = false)
    {
        if ( $this->initialized && ! $force ) {
            return;
        }

        $this->logger->debug("Loading" . ( $this->isLocalConfig ? " local" : "" ) . " configuration file " . $this->filename);

        // Parse the configuration file

        $this->parse();

        // Perform any post-parsing tasks such as validation or manipulating the parsed
        // data that must happen before transformation.

        $this->preTransformTasks();

        // Run the key transformers on the parsed data.

        $this->transform();

        // At this point, the only items in the variable store will be those passed in via the
        // 'config_variables' option which are typically variables supplied on the command line and
        // have the highest priority. Substitute them in the transformed configuration prior to
        // calling interpretData() or a child class that extends this method may not get the
        // substituted value.

        $this->transformedConfig = $this->substituteVariables($this->transformedConfig);

        // Perform local interpretation on the data and apply contextual meaning

        $this->interpretData();

        // Process any local configuration files iff $localConfigDir exists, is actually a directory,
        // and this is not a local config file itself.
        if ( ! $this->isLocalConfig && null !== $this->localConfigDir && is_dir($this->localConfigDir) ) {

            if ( false === ($dh = @opendir($this->localConfigDir)) ) {
                $this->logAndThrowException(sprintf("Error opening configuration directory '%s'", $this->localConfigDir));
            }

            // Examine the subdirectory for .json files and collect them for later use.
            $files = array();
            while ( false !== ( $file = readdir($dh) ) ) {

                // Only process .json files

                $len = strlen($file);
                $pos = strrpos(strtolower($file), ".json");
                if ( false === $pos || ($len - $pos) != 5 ) {
                    continue;
                }

                $files[] = $this->localConfigDir . "/" . $file;

            }  //  while ( false !== ( $file = readdir($dh) ) )

            // Sort the retrieved .json files.
            sort($files, SORT_LOCALE_STRING);

            // Process each .json file before merging into the main file.
            foreach( $files as $file ) {

                try {
                    $localConfigObj = $this->processLocalConfig($file);
                } catch ( Exception $e ) {
                    throw new Exception(sprintf("Processing %s: %s", $file, $e->getMessage()));
                }

                $this->merge($localConfigObj);

                $localConfigObj->cleanup();
            } // foreach($files as $file)

            closedir($dh);
        } // if ( ! $this->isLocalConfig && null !== $this->localConfigDir && is_dir($this->localConfigDir) )

        $this->postMergeTasks();

        $this->initialized = true;

    }  // initialize()


    /** -----------------------------------------------------------------------------------------
     * Parse the configuration file.
     *
     * @param boolean $force TRUE if the configuration file should be re-parsed.
     *
     * @return Configuration This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    private function parse($force = false)
    {
        // Don't parse if the file has already been parsed unless we are forcing it.

        if ( null !== $this->parsedConfig && ! $force ) {
            return $this;
        }

        // Parse and decode the JSON configuration file

        $options = new DataEndpointOptions(array(
            'name' => "Configuration",
            'path' => $this->filename,
            'type' => "jsonfile"
        ));

        $jsonFile = DataEndpoint::factory($options, $this->logger);

        $parsed = $jsonFile->parse();

        /* We currently support json files that have an object or an array as the root element.
         * If the root element is an object then $parsed will contain the first element in the
         * object ( missing both of these if cases ). If it's an array, then the `elseif` will hit
         * and we use the `getRecordList` to retrieve all of the data parsed from the file as
         * opposed to just the first record in the element.
         *
         * If there is a problem parsing the file then `false` will be returned. To allow for the
         * the rest of this class to work we supply an empty object.
         */
        if ($parsed === false) {
            $parsed = new stdClass();
        } elseif(count($jsonFile) > 1) {
            $parsed = $jsonFile->getRecordList();
        }

        if ($this->forceArrayReturn && !is_array($parsed)) {
            $parsed = array($parsed);
        }

        $this->parsedConfig = $parsed;

        return $this;

    }  // parse()

    /** -----------------------------------------------------------------------------------------
     * Perform any tasks that must happen after parsing but before we continue on to
     * transformation. For example, in a configuration file we may want to apply a base
     * path to some elements before transforming JSON reference pointers.
     *
     * @return Configuration This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function preTransformTasks()
    {
        $this->addKeyTransformer(new CommentTransformer($this->logger));
        $this->addKeyTransformer(new JsonReferenceTransformer($this->logger));
        $this->addKeyTransformer(new StripMergePrefixTransformer($this->logger));
        $this->addKeyTransformer(new IncludeTransformer($this->logger));
        return $this;
    }  //preTransformTasks()

    /** -----------------------------------------------------------------------------------------
     * Perform transformation by running any key transformers that have been added.
     *
     * @return Configuration This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    private function transform()
    {
        // Objects are passed by reference so to keep the original parsed config we need to use
        // unserialize(serialize()) to break the reference. Cloning only produces a shallow copy and
        // any properties that are references to other variables will remain references

        $tmp = unserialize(serialize($this->parsedConfig));

        // We need to account for `parsedConfig` being an object or an array of objects. The
        // following `if/else` statement handles either of these to scenarios.
        if (is_array($tmp)) {
            foreach($tmp as $key => $value) {
                if (is_object($value)) {
                    $tmp[$key] = $this->processKeyTransformers($value);
                }
            }
            $this->transformedConfig = $tmp;
        } else {
            $this->transformedConfig = $this->processKeyTransformers($tmp);
        }


        return $this;

    }  // transform()

    /** -----------------------------------------------------------------------------------------
     * Interpret the transformed data in the configuration file. By default the only interpretation
     * performed is adding transformed configuration sections and data to the section list. Child
     * classes should override this method as needed.
     *
     * @return Configuration This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function interpretData()
    {
        // Add sections for each of the transformed keys
        foreach ( $this->transformedConfig as $key => $value ) {
            $this->addSection($key, $value);
        }
        return $this;
    }  // interpretData()

    /** -----------------------------------------------------------------------------------------
     * Given a path to a local configuration file, create a Configuration object and parse
     * the file. This functionality is broken out so child classes can apply any necessary
     * options and object types.
     *
     * @param string $localConfigFile The path to the local configuration file
     *
     * @return Configuration A Configuration object containing the parsed config.
     * ------------------------------------------------------------------------------------------
     */

    protected function processLocalConfig($localConfigFile)
    {
        $options = array(
            'local_config_dir' => $this->localConfigDir,
            'is_local_config'  => true,
            'variable_store'   => $this->variableStore
        );

        // The static keyword uses late binding to create an instance of the class that you called
        // the method on. This allows classes extending Configuration to create instances of
        // themselves when calling this method if they have not overriden it.

        $localConfigObj = new static($localConfigFile, $this->baseDir, $this->logger, $options);
        $localConfigObj->initialize();

        return $localConfigObj;

    }  // processLocalConfig()

    /** -----------------------------------------------------------------------------------------
     * Merge data from the specified local configuration object, either overwriting or
     * merging data from local configuration objects into the current object. If
     * $overwrite is TRUE, overwrite data for a given key in this Configuration object
     * with data found in a local configuration object, or create the key if it does not
     * exist. If $overwrite is FALSE, create new keys as needed and if the key exists in
     * this configuration and can be appended (i.e., is an array) then append the data
     * found in the local configuration to the existing data. If the data cannot be
     * appended (such as a scalar or an object) then overwrite it. This functionality is
     * broken out in its own method so child classes can implement logic specific to their
     * data if needed.
     *
     * @param Configuration $localConfigObj A configuration object generated from a
     *   local config file
     * @param bool $overwrite If TRUE, overwrite data (e.g. a key) in this Configuration
     *   with data found in a local configuration.
     *
     * @return Configuration This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function merge(Configuration $localConfigObj, $overwrite = false)
    {
        $this->transformedConfig = $this->mergeLocal(
            $this->transformedConfig,
            $localConfigObj->getTransformedConfig(),
            $localConfigObj->getFilename(),
            $overwrite
        );

        foreach ( $localConfigObj->getSectionNames() as $sectionName ) {
            $localConfigData = $localConfigObj->getSectionData($sectionName);
            $myData = $this->getSectionData($sectionName);

            if ( $overwrite || false == $myData ) {
                $this->addSection($sectionName, $localConfigData);
            } elseif ( is_array($myData) ) {
                array_push($myData, $localConfigData);
                $this->addSection($sectionName, $myData);
            }
        }

        return $this;

    }  // merge()

    /**
     * Merge $incoming object into the $existing object recursively.
     *
     * @param \stdClass $existing         the object to be merged into.
     * @param \stdClass $incoming         the object to be merged from.
     * @param string    $incomingFileName the file that $incoming originates from.
     * @param bool      $overwrite        whether or not to force overwriting of all $existing w/
     * $incoming.
     * @param bool      $overwriteScalar  whether or not to force overwriting of just scalar values.
     * @return \stdClass the updated $existing object.
     */
    protected function mergeLocal(\stdClass $existing, \stdClass $incoming, $incomingFileName, $overwrite = false, $overwriteScalar = true)
    {
        foreach($incoming as $property => $incomingValue) {

            if ( $overwrite || ! isset($existing->$property) ) {
                $existing->$property = $incoming->$property;
            } else {
                $existingValue = &$existing->$property;

                if (is_object($existingValue) && is_object($incomingValue)) {
                    $existing->$property = $this->mergeLocal($existingValue, $incomingValue, $incomingFileName, $overwrite);
                } elseif (is_array($existingValue) && is_array($incomingValue)) {


                    /* Since we only deal with numeric arrays ( of stdClass objects ) we can't rely
                     * on `array_merge` as that could lead to duplicate values. Instead we utilize
                     * `in_array` which works as expected for stdClass objects.
                     *
                     * From: https://secure.php.net/manual/en/function.array-merge.php
                     * "If the input arrays have the same string keys, then the later value for that
                     * key will overwrite the previous one. If, however, the arrays contain numeric
                     * keys, the later value will not overwrite the original value, but will be
                     * appended."
                     */
                    foreach($incomingValue as $value) {
                        if (!in_array($value, $existingValue)) {
                            array_push($existingValue, $value);
                        }
                    }
                } elseif (is_scalar($existingValue) && is_scalar($incomingValue)) {
                    // When this function is used to `extend` an entry we do not want to overwrite
                    // values that already exist as the json merge step has already occurred. In
                    // that case $overwriteScalar will be false.
                    if ($overwriteScalar) {
                        $existing->$property = $incomingValue;
                    }
                } else {
                    $this->logger->warning(
                        sprintf(
                            "Type mismatch. Unable to merge local value for key '%s' (type: %s) with global value (type: %s) from local file %s",
                            $property,
                            gettype($incomingValue),
                            gettype($existingValue),
                            $incomingFileName
                        )
                    );
                }
            }
        }

        return $existing;
    }

    /**
     * Perform any tasks that need to occur after merging the local configuration objects into the
     * global configuration object. By default no actions are performed, allowing child classes to
     * customize the global configuration object as needed.
     *
     * @return Configuration This object to support method chaining.
     * @throws Exception
     */
    protected function postMergeTasks()
    {

        return $this;
    }  // postMergeTasks()

    /**
     * Perform variable substitution on an entity. The entity may be a simple string, an array, or
     * more complex objects. Arrays and complex objects are recursively traversed.
     *
     * @param mixed $entity The entity that we are performing substitution on.
     *
     * @return mixed The entity after performing variable substitution.
     */

    protected function substituteVariables($entity)
    {
        if ( is_string($entity) ) {
            return $this->variableStore->substitute($entity);
        } elseif ( is_array($entity) || $entity instanceof \stdClass || $entity instanceof \Traversable ) {
            return $this->recursivelySubstituteVariables($entity);
        }
        return $entity;
    }

    /**
     * Recursively traverse a complex object and perform variable substitution on any values that are
     * strings.
     *
     * @param  Traversable $traversable A traversable entity.
     *
     * @return Traversable The input parameter with variable substitution performed on string values.
     */

    private function recursivelySubstituteVariables($traversable)
    {
        foreach ( $traversable as $property => &$value ) {
            if ( is_string($value) ) {
                $value = $this->variableStore->substitute($value);
            } elseif ( is_array($value) || $value instanceof \stdClass || $value instanceof \Traversable ) {
                $value = $this->recursivelySubstituteVariables($value);
            }
        }
        return $traversable;
    }

    /** -----------------------------------------------------------------------------------------
     * Clean up intermediate information that we don't need to keep around after processing. This
     * includes parsed and constructed JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function cleanup()
    {
        $this->parsedConfig = null;
        $this->transformedConfig = null;
    }  // cleanup()

    /** -----------------------------------------------------------------------------------------
     * Compare object keys against the list of key transformers and recursively apply
     * transformers where they match.
     *
     * @param stdClass $obj The object that we are transforming.
     *
     * @return stdClass The transformed object, although this is not strictly necessary since PHP
     *   passes objects by reference.
     * ------------------------------------------------------------------------------------------
     */

    protected function processKeyTransformers(stdClass $obj)
    {
        // Bail if there are no transformers to process

        if ( 0 == count($this->keyTransformers) ) {
            return $obj;
        }

        // Examine each key in the configuration object. If there is a transformer that
        // matches the key, transform the key and value.

        foreach ( $obj as $key => &$value ) {

            // Examine all transformers

            foreach ( $this->keyTransformers as $transformer ) {

                $transformKey = $key;

                if ( ! $transformer->keyMatches($transformKey) ) {
                    continue;
                }

                try {
                    $stop = ( ! $transformer->transform($transformKey, $value, $obj, $this) );
                } catch ( Exception $e ) {
                    throw new Exception(sprintf("%s: %s", $this->filename, $e->getMessage()));
                }

                if ( null === $transformKey && null === $value ) {

                    // If the returned key and value are both null, remove the key/value
                    // pair from the JSON.

                    unset($obj->$key);

                } elseif ( null === $transformKey ) {

                    // If the key is null but the value is set, replace the ENTIRE OBJECT
                    // with the value. Note that if we are replacing the value with an
                    // array we need to assign it by reference so we can modify it later.

                    if ( is_array($value) ) {
                        $obj = &$value;
                    } else {
                        $obj = $value;
                    }

                } else {

                    // If the key and value are both set replace them both (either may be
                    // modified).

                    if ( $transformKey != $key ) {
                        unset($obj->$key);
                    }
                    $obj->$transformKey = $value;

                }

                if ( $stop ) {
                    break;
                }

            }  // foreach ( $this->keyTransformers as $transformer )

            // A value can be an object, array, or salcar. The (possibly transformed)
            // value should now be examined for an objects that may need to be transformed
            // and recursively process any objects.

            if ( is_object($value) ) {
                $value = $this->processKeyTransformers($value);
            } elseif ( is_array($value) ) {

                // Arrays may contain objects or scalars. We are not handling the case
                // where an object may be deeply nested in an array.

                foreach ( $value as $index => &$element ) {
                    if ( is_object($element) ) {
                        $element = $this->processKeyTransformers($element);
                    }
                }  // foreach ( $value as $element )
            }

            // If we have replaced the object by something that is not Traversable (such as an
            // included string) then do not continue the loop or the foreach will try to call
            // valid() and next() on a non-Traversable.

            if ( ! ( is_array($obj) || is_object($obj) || ($obj instanceof \Traversable) ) ) {
                break;
            }

        }  // foreach ( $obj as $key => $value )

        return $obj;

    }  // processKeyTransformers()

    /** -----------------------------------------------------------------------------------------
     * Get the list of section names.
     *
     * @return array An array of section names
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionNames()
    {
        return array_keys($this->sectionData);
    }  // getSectionNames()

    /** -----------------------------------------------------------------------------------------
     * @param string $name The name of the section to examine.
     *
     * @return array TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function sectionExists($name)
    {
        return array_key_exists($name, $this->sectionData);
    }  // sectionExists()

    /** -----------------------------------------------------------------------------------------
     * @param string $name The name of the section to examine.
     *
     * @return boolean TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionData($name)
    {
        return ( $this->sectionExists($name)
                 ? $this->sectionData[$name]
                 : false
            );
    }  // getSectionData()

    /** -----------------------------------------------------------------------------------------
     * Add a new section to the internal data structures if it doesn't already exist or
     * update the data associated with the section if it does exist (unless $overwrite ==
     * false)
     *
     * @param string $name The name of the new section
     * @param stdClass $data The data associated with the new section
     * @param boolean $overwrite TRUE if any existing data for the given section should be overwritten
     *
     * @return Configuration This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function addSection($name, $data = null, $overwrite = true)
    {
        if ( ! $overwrite && $this->sectionExists($name) ) {
            return $this;
        }

        $this->sectionData[$name] = $data;

        return $this;

    }  // addSection()

    /** -----------------------------------------------------------------------------------------
     * Remove a section from the internal data structures.
     *
     * @param string $name The name of the section
     *
     * @return Configuration This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function deleteSection($name)
    {
        if ( $this->sectionExists($name) ) {
            unset($this->sectionData[$name]);
        }

        return $this;

    }  // deleteSection()

    /* ==========================================================================================
     * Iterator implementation. Allow iteration over the list of sections.
     * ==========================================================================================
     */

    public function current()
    {
        return current($this->sectionData);
    }  // current()

    public function key()
    {
        return key($this->sectionData);
    }  // key()

    public function next()
    {
        return next($this->sectionData);
    }  // next()

    public function rewind()
    {
        return reset($this->sectionData);
    }  // rewind()

    public function valid()
    {
        return false !== current($this->sectionData);
    }  // valid()

    /* ==========================================================================================
     * Key Transformer Management
     * ==========================================================================================
     */

    /** -----------------------------------------------------------------------------------------
     * Add a key transformer to the configuration, warning and overwriting if a duplicate is added.
     *
     * @param iConfigFileKeyTransformer $transformer A key transformer to add.
     *
     * @return Configuration This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function addKeyTransformer(iConfigFileKeyTransformer $transformer)
    {
        $className = get_class($transformer);

        if ( array_key_exists($className, $this->keyTransformers) ) {
            $this->logger->warning(
                sprintf("%s: Adding duplicate transformer '%s', overwriting", $this->__toString(), $className)
            );
        }

        $this->keyTransformers[$className] = $transformer;

        return $this;

    }  // addKeyTransformer()

    /** -----------------------------------------------------------------------------------------
     * Return TRUE if the transformer has already been added to this configuration file.
     *
     * @param string|iConfigFileKeyTransformer $transformer A key transformer object or class name
     *
     * @return boolean TRUE if the transformer has already been added to this configuration file.
     * ------------------------------------------------------------------------------------------
     */

    public function hasKeyTransformer($transformer)
    {
        $className = '';

        if ( is_object($transformer) && $transformer instanceof iConfigFileKeyTransformer ) {
            $className = get_class($transformer);
        } elseif ( is_string($transformer) ) {
            $className = $transformer;
        } else {
            $msg = 'Transformer is not an object or a string';
            if ( is_object($transformer) ) {
                $msg = sprintf("Transformer '%s' does not implement iConfigFileKeyTransformer", get_class($transformer));
            }
            $this->logAndThrowException($msg);
        }

        return array_key_exists($className, $this->keyTransformers);

    }  // hasKeyTransformer()

    /** -----------------------------------------------------------------------------------------
     * Get all key transformers for this configuration file.
     *
     * @return array An associative array of where the key is the transformer class name and the
     *   value is the transformer object.
     * ------------------------------------------------------------------------------------------
     */

    public function getKeyTransformers() {

        return $this->keyTransformers;

    }  // getKeyTransformers()

    /** -----------------------------------------------------------------------------------------
     * Delete a key handler and return it.
     *
     * @param string|iConfigFileKeyTransformer $transformer A key transformer object or class
     *   name configuration)
     *
     * @return Configuration This object for method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function deleteKeyTransformer($transformer)
    {
        $className = null;

        if ( is_object($transformer) && $transformer instanceof iConfigFileKeyTransformer ) {
            $className = get_class($transformer);
        } elseif ( is_string($transformer) ) {
            $className = $transformer;
        } else {
            $msg = 'Transformer is not an object or a string';
            if ( is_object($transformer) ) {
                $msg = sprintf("Transformer '%s' does not implement iConfigFileKeyTransformer", get_class($transformer));
            }
            $this->logAndThrowException($msg);
        }

        if ( ! array_key_exists($className, $this->keyTransformers) ) {
            $this->logger->warning(
                sprintf("%s: Cannot delete transformer '%s', does not exist", $this->__toString(), $className)
            );
        } else {
            unset($this->keyTransformers[$className]);
        }

        return $this;

    }  // deleteKeyHandler()

    /** -----------------------------------------------------------------------------------------
     * Get the base directory for this configuration.
     *
     * @return string The base directory for this configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getBaseDir()
    {
        return $this->baseDir;
    }  // getBaseDir()

    /**
     * ------------------------------------------------------------------------------------------
     * @return array The associative array of options that was passed in from the parent, and
     *   possibly augmented locally.
     * ------------------------------------------------------------------------------------------
     */

    public function getOptions()
    {
        return $this->options;
    }  // getOptions()

    /**
     * ------------------------------------------------------------------------------------------
     * @return VariableStore The VariableStore currently in use for this configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getVariableStore()
    {
        return $this->variableStore;
    }  // getVariableStore()

    /** -----------------------------------------------------------------------------------------
     * Get the configuration after applying transforms.
     *
     * @return stdClass The transformed configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getTransformedConfig()
    {
        return $this->transformedConfig;
    }  // getTransformedConfig()

    /** -----------------------------------------------------------------------------------------
     * Getter method for accessing data keys using object notation.
     *
     * NOTE: When querying for existance we can't use isset() and must use NULL === $options->key
     *
     * @param string $property The name of the property to retrieve
     *
     * @return mixed The property, or NULL if the property doesn't exist.
     * ------------------------------------------------------------------------------------------
     */

    public function __get($property)
    {
        if ( array_key_exists($property, $this->sectionData) ) {
            return $this->sectionData[$property];
        }

        return null;
    }  // __get()

    /** -----------------------------------------------------------------------------------------
     * Return TRUE if a property is set and is not NULL.
     *
     * @param string $property The name of the property to retrieve
     *
     * @return boolean TRUE if the property exists and is not NULL, or FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function __isset($property)
    {
        return ( array_key_exists($property, $this->sectionData) && null !== $this->sectionData[$property] );
    }  // __isset()

    /**
     * Return this Configuration's $filename property.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * A factory / helper method for instantiating a Configuration object, initializing it, and
     * returning the results of its `toAssocArray` function.
     *
     * @param string         $filename the base configuration file name to be processed.
     * @param string|null    $baseDir  the directory in which $filename can be found.
     * @param Log|null       $logger   a Log instance that Configuration will utilize during its processing.
     * @param array          $options  options that will be used during construction of the Configuration object.
     *
     * @return array the results of the instantiated configuration objects `toAssocArray` function.
     */
    public static function assocArrayFactory(
        $filename,
        $baseDir = null,
        Log $logger = null,
        array $options = array()
    ) {

        return self::factory($filename, $baseDir, $logger, $options)->toAssocArray();
    }

    /**
     * A helper function that instantiates, initializes, and returns a Configuration object.
     *
     * @param string         $filename the base configuration file name to be processed.
     * @param string|null    $baseDir  the directory in which $filename can be found.
     * @param Log|null       $logger   a Log instance that Configuration will utilize during its processing.
     * @param array          $options  options that will be used during construction of the Configuration object.
     *
     * @return Configuration an initialized instance of Configuration.
     */
    public static function factory(
        $filename,
        $baseDir = null,
        Log $logger = null,
        array $options = array()
    ) {
        $instance = new static($filename, $baseDir, $logger, $options);
        $instance->initialize();
        return $instance;
    }

    /** -----------------------------------------------------------------------------------------
     * Return the JSON representation of the parsed and translated Configuration.
     *
     * @return string A JSON representation of the Configuration object.
     * ------------------------------------------------------------------------------------------
     */

    public function toJson()
    {
        return json_encode($this->transformedConfig);
    }  // toJson()

    /**
     * Retrieve this `Configuration` objects data formatted as an associative array.
     *
     * @return array
     */
    public function toAssocArray()
    {
        return json_decode(json_encode($this->transformedConfig), true);
    } // toAssocArray

    /** -----------------------------------------------------------------------------------------
     * Generate a string representation of this object. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return string A string representation of the object
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return get_class($this) . " ({$this->filename})";
    }  // __toString()
}  // class Configuration
