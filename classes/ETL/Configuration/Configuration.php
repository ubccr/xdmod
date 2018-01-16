<?php
/* ==========================================================================================
 * Read and parse a JSON configuration file containing keys with associated values
 * (scalar, object, array). Values that are objects are considered to be fixed entities
 * that define something in the configuration. Key transformers may also be added allowing
 * us to dynamically extend functionality and transform keys and their associated
 * values. For example, a comment transformer may strip comments from the file so
 * downstream processing does not need to deal with them or may provide the ability to
 * reference keys within other files.
 *
 * No interpretation of the data is performed in this class. The file is simply parsed,
 * transformed, and put into a structure with section (key) names and the data associated
 * with each section. To interpret the data, a child class should override
 * Configuration::interpretData().
 *
 * The process for applying transformers to the configuration is as follows:
 *
 * 1. Define key transformers that implement the `iConfigFileKeyTransformer` interface.
 *    Multiple transformers could be defined for the same key.
 * 2. Attach transformers to the Config object. Transformers will be processed in the
 *    order that they are added.
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
 * Perform any pre-transformation tasks such as setting or adding key transformers
 *
 * 2. interpretData()
 *
 * Apply any logic to interpret the configuration data such as handling defaults,
 * registering ETL actions, etc.
 *
 * 3. processLocalConfig()
 *
 * Local configuration files should be processed instantiating the appropriate class
 * (typically the class used to process the main file) and passing the correct options.
 *
 * 4. merge()
 *
 * Merge data from local configuration files into the global namespace.
 *
 * 5. cleanup()
 *
 * Perform any necessary data structure cleanup.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-15
 *
 * @date 2017-04-11 - Added dynamic key transformers
 *
 * @date 2017-04-18 - Moved parsing of local configuration files into this class
 * ==========================================================================================
 */

namespace ETL\Configuration;

// PEAR logger
use Log;

use Exception;
use stdClass;
use ETL\Loggable;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint;

class Configuration extends Loggable implements \Iterator
{
    // Key transformers will be used to dynamically add functionality for matching keys and
    // must implement the iConfigFileKeyTransformer interface.

    private $keyTransformers = array();

    // NOTE: Any properties that need to be accessed in a transformer defined in a subclass cannot be
    // private.

    // Configuraiton filename
    protected $filename = null;

    // The base directory for any paths that are not fully qualified
    protected $baseDir = null;

    // The parsed configuration file prior to manipulation
    protected $parsedConfig = null;

    // The configuration constructed from the parsed file after processing any transformers and
    // performing manipulations
    protected $transformedConfig = null;

    // An associative array where keys are section names and values the data associated with that
    // section.
    protected $sectionData = array();

    // TRUE if this is a local configuration file as opposed to the main configuration file
    protected $isLocalConfig = false;

    // Directory to look for local configuration files (e.g. sub-configs)
    protected $localConfigDir = null;

    /* ------------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param $filename Name of the JSON configuration file to parse
     * @param $baseDir Base directory for configuration files. Overrides the base dir provided in
     *   the top-level config file. If not set, use the same directory as the config file.
     * @param $logger A PEAR Log object or null to use the null logger.
     * @param $options An associative array of additional options passed from the parent. These
     *   include:
     *   local_config_dir: Directory to look for local configuration files
     *   is_local_config: TRUE if this filename is a local config file as opposed to the main file
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

        if ( array_key_exists('local_config_dir', $options) && null !== $options['local_config_dir'] ) {
            $this->localConfigDir = $options['local_config_dir'];
        }

        $this->isLocalConfig = ( array_key_exists('is_local_config', $options) && $options['is_local_config'] );

        // Clean up directory paths
        $this->filename = \xd_utilities\resolve_path($this->filename);
        $this->baseDir = \xd_utilities\resolve_path($this->baseDir);
        $this->localConfigDir = \xd_utilities\resolve_path($this->localConfigDir);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize the configuration object.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize()
    {
        $this->logger->info("Loading" . ( $this->isLocalConfig ? " local" : "" ) . " configuration file " . $this->filename);

        // Parse the configuration file

        $this->parse();

        // Perform any post-parsing tasks such as validation or manipulating the parsed
        // data that must happen before transformation.

        $this->preTransformTasks();

        // Run the key transformers on the parsed data.

        $this->transform();

        // Add sections for each of the transformed keys

        foreach ( $this->transformedConfig as $key => $value ) {
            $this->addSection($key, $value);
        }

        // Perform local interpretation on the data and apply contextual meaning

        $this->interpretData();

        // Process any local configuration files.

        if ( ! $this->isLocalConfig && null !== $this->localConfigDir ) {

            if ( ! is_dir($this->localConfigDir) ) {
                $this->logger->debug(sprintf("Local configuration directory '%s' not found", $this->localConfigDir));
                return;
            }

            if ( false === ($dh = @opendir($this->localConfigDir)) ) {
                $this->logAndThrowException(sprintf("Error opening configuration directory '%s'", $this->localConfigDir));
            }

            // Examine the subdirectory for .json files and parse each one, then merge the results back
            // into this object

            while ( false !== ( $file = readdir($dh) ) ) {

                // Only process .json files

                $len = strlen($file);
                $pos = strrpos(strtolower($file), ".json");
                if ( false === $pos || ($len - $pos) != 5 ) {
                    continue;
                }

                $localConfigObj = $this->processLocalConfig($this->localConfigDir . "/" . $file);
                $this->merge($localConfigObj);
                $localConfigObj->cleanup();

            }  //  while ( false !== ( $file = readdir($dh) ) )

            closedir($dh);

        }  // if ( null !== $confSubdirectory )

    }  // initialize()


    /* ------------------------------------------------------------------------------------------
     * Parse the configuration file.
     *
     * @param $force TRUE if the configuration file should be re-parsed.
     *
     * @return This object to support method chaining.
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
        $this->parsedConfig = $jsonFile->parse();

        return $this;

    }  // parse()

    /* ------------------------------------------------------------------------------------------
     * Perform any tasks that must happen after parsing but before we continue on to
     * transformation. For example, in a configuration file we may want to apply a base
     * path to some elements before transforming JSON reference pointers.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function preTransformTasks()
    {
        $this->addKeyTransformer(new CommentTransformer($this->logger));
        $this->addKeyTransformer(new JsonReferenceTransformer($this->logger));
        return $this;
    }  //preTransformTasks()

    /* ------------------------------------------------------------------------------------------
     * Perform transformation by running any key transformers that have been added.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    private function transform()
    {
        // To keep the original parsed config we need to use unserialize(serialize()) to
        // break the reference.

        $tmp = unserialize(serialize($this->parsedConfig));
        $this->transformedConfig = $this->processKeyTransformers($tmp);

        return $this;

    }  // transform()

    /* ------------------------------------------------------------------------------------------
     * Interpret the transformed data in the configuration file. By default no
     * interpretation is performed by this class so child classes should override this
     * method as needed.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function interpretData()
    {
        return $this;
    }  // interpretData()

    /* ------------------------------------------------------------------------------------------
     * Given a path to a local configuration file, create a Configuration object and parse
     * the file. This functionality is broken out so child classes can apply any necessary
     * options and object types.
     *
     * @param string $localConfigFile The path to the local configuration file
     *
     * @return A Configuration object containing the parsed config.
     * ------------------------------------------------------------------------------------------
     */

    protected function processLocalConfig($localConfigFile)
    {
        $options = array(
            'local_config_dir' => $this->localConfigDir,
            'is_local_config' => true
        );

        $localConfigObj = new Configuration($localConfigFile, $this->baseDir, $this->logger, $options);
        $localConfigObj->initialize();

        return $localConfigObj;

    }  // processLocalConfig()

    /* ------------------------------------------------------------------------------------------
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
     * @param Configuration $subConfigObj A configuration object generated from a
     *   local config file
     * @param bool $overwrite If TRUE, overwrite data (e.g. a key) in this Configuration
     *   with data found in a local configuration.
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    protected function merge(Configuration $localConfigObj, $overwrite = false)
    {

        // If overwriting or the key doesn't exist, set it. Otherwise if the value is an
        // array append it. If not overwriting and the value is not an array silently skip
        // it.

        foreach ( $localConfigObj->getTransformedConfig() as $k => $v ) {
            if ( $overwrite || ! isset($this->transformedConfig->$k) ) {
                $this->transformedConfig->$k = $v;
            } elseif ( is_array($this->transformedConfig->$k) ) {
                array_push($this->transformedConfig->$k, $v);
            } else {
                $this->logger->debug("Skip duplicate key in local config (overwrite == false)");
            }
        }

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

    /* ------------------------------------------------------------------------------------------
     * Clean up intermediate information that we don't need to keep around after processing. This
     * includes parsed and constructed JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function cleanup()
    {
        $this->parsedConfig = null;
        $this->transformedConfig = null;
    }  // cleanup()

    /* ------------------------------------------------------------------------------------------
     * Compare object keys against the list of key transformers and recursively apply
     * transformers where they match.
     *
     * @param $stdClass $obj The object that we are transforming.
     *
     * @return The transformed object, although this is not strictly necessary since PHP
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

                $stop = ( ! $transformer->transform($transformKey, $value, $obj, $this) );

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

        }  // foreach ( $obj as $key => $value )

        return $obj;

    }  // processKeyTransformers()

    /* ------------------------------------------------------------------------------------------
     * Get the list of section names.
     *
     * @return An array of section names
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionNames()
    {
        return array_keys($this->sectionData);
    }  // getSectionNames()

    /* ------------------------------------------------------------------------------------------
     * @param $name The name of the section to examine.
     *
     * @return TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function sectionExists($name)
    {
        return array_key_exists($name, $this->sectionData);
    }  // sectionExists()

    /* ------------------------------------------------------------------------------------------
     * @param $name The name of the section to examine.
     *
     * @return TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionData($name)
    {
        return ( $this->sectionExists($name)
                 ? $this->sectionData[$name]
                 : false
            );
    }  // getSectionData()

    /* ------------------------------------------------------------------------------------------
     * Add a new section to the internal data structures if it doesn't already exist or
     * update the data associated with the section if it does exist (unless $overwrite ==
     * false)
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
        if ( ! $overwrite && $this->sectionExists($name) ) {
            return $this;
        }

        $this->sectionData[$name] = $data;

        return $this;

    }  // addSection()

    /* ------------------------------------------------------------------------------------------
     * Remove a section from the internal data structures.
     *
     * @param $name The name of the section
     *
     * @return This object for method chaining
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

    /* ------------------------------------------------------------------------------------------
     * Add a key transformer to the configuration, warning and overwriting if a duplicate is added.
     *
     * @param iConfigFileKeyTransformer $transformer A key transformer to add.
     *
     * @return This object for method chaining
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

    /* ------------------------------------------------------------------------------------------
     * Return TRUE if the transformer has already been added to this configuration file.
     *
     * @param (string || iConfigFileKeyTransformer) $transformer A key transformer object or class name
     *
     * @return TRUE if the transformer has already been added to this configuration file.
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

    /* ------------------------------------------------------------------------------------------
     * Get all key transformers for this configuration file.
     *
     * @return An associative array of where the key is the transformer class name and the
     *   value is the transformer object.
     * ------------------------------------------------------------------------------------------
     */

    public function getKeyTransformers() {

        return $this->keyTransformers;

    }  // getKeyTransformers()

    /* ------------------------------------------------------------------------------------------
     * Delete a key handler and return it.
     *
     * @param (string || iConfigFileKeyTransformer) $transformer A key transformer object or class
     *   name configuration)
     *
     * @return The callable key handler, or FALSE if no handler was defined.
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

    /* ------------------------------------------------------------------------------------------
     * Get the base directory for this configuration.
     *
     * @return The base directory for this configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getBaseDir()
    {
        return $this->baseDir;
    }  // getBaseDir()

    /* ------------------------------------------------------------------------------------------
     * Get the configuration after applying transforms.
     *
     * @return The transformed configuration.
     * ------------------------------------------------------------------------------------------
     */

    public function getTransformedConfig()
    {
        return $this->transformedConfig;
    }  // getTransformedConfig()

    /* ------------------------------------------------------------------------------------------
     * Getter method for accessing data keys using object notation.
     *
     * NOTE: When querying for existance we can't use isset() and must use NULL === $options->key
     *
     * @param $property The name of the property to retrieve
     *
     * @return The property, or NULL if the property doesn't exist.
     * ------------------------------------------------------------------------------------------
     */

    public function __get($property)
    {
        if ( array_key_exists($property, $this->sectionData) ) {
            return $this->sectionData[$property];
        }

        return null;
    }  // __get()

    /* ------------------------------------------------------------------------------------------
     * Return TRUE if a property is set and is not NULL.
     *
     * @param $property The name of the property to retrieve
     *
     * @return TRUE if the property exists and is not NULL, or FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function __isset($property)
    {
        return ( array_key_exists($property, $this->sectionData) && null !== $this->sectionData[$property] );
    }  // __isset()

    /* ------------------------------------------------------------------------------------------
     * Return the JSON representation of the parsed and translated Configuration.
     *
     * @return A JSON representation of the Configuration object.
     * ------------------------------------------------------------------------------------------
     */

    public function toJson()
    {
        return json_encode($this->transformedConfig);
    }  // toJson()

    /* ------------------------------------------------------------------------------------------
     * Generate a string representation of this object. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return A string representation of the object
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return get_class($this) . " ({$this->filename})";
    }  // __toString()
}  // class Configuration
