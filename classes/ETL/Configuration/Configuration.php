<?php
/* ==========================================================================================
 * Read and parse a JSON configuration file containing keys with associated values
 * (scalar, object, array). Key transformers may also be added allowing us to dynamically
 * extend functionality and transform keys and their associated values. For example, a
 * comment transformer may strip comments from the file so downstream processing does not need
 * to deal with them or may provide the ability to reference keys within other files.
 *
 * The process for parsing the configuration is as follows:
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
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-15
 *
 * @date 2017-04-11 - Added dynamic key transformers
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

    // The names of all sections discovered in the config file
    protected $sectionNames = array();

    // An associative array where keys are section names and values the data associated with that
    // section.
    protected $sectionData = array();

    /* ------------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param $filename Name of the JSON configuration file to parse
     * @param $baseDir Base directory for configuration files. Overrides the base dir provided in
     *   the top-level config file
     * @param $logger A PEAR Log object or null to use the null logger.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($filename, $baseDir = null, Log $logger = null)
    {
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

        $this->filename = \xd_utilities\resolve_path($this->filename);
        $this->baseDir = \xd_utilities\resolve_path($this->baseDir);

        // The comment transformer is the only default transformer, all others need to be added.

        $this->addKeyTransformer(new CommentTransformer($this->logger));
        $this->addKeyTransformer(new JsonReferenceTransformer($this->logger));

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize the configuration object.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize()
    {
        $this->logger->info("Loading configuration file " . $this->filename);
        $this->parse();
    }  // initialize()

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
        // Don't parse if the file has already been parsed unless we are forcing it.

        if ( null !== $this->parsedConfig && ! $force ) {
            return;
        }

        // --------------------------------------------------------------------------------
        // Parse and decode the JSON configuration file

        $opt = new DataEndpointOptions(array('name' => "Configuration",
                                             'path' => $this->filename,
                                             'type' => "jsonfile"));
        $jsonFile = DataEndpoint::factory($opt, $this->logger);

        $this->parsedConfig = $jsonFile->parse();

        // To keep the original parsed config we need to use unserialize(serialize()) to
        // break the reference

        $tmp = unserialize(serialize($this->parsedConfig));
        $this->transformedConfig = $this->processKeyTransformers($tmp);

        // Process the constructed configuration to create sections

        foreach ( $this->transformedConfig as $key => $value ) {
            $this->addSection($key, $value);
        }

        return true;

    }  // parse()

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
        if ( in_array($name, $this->sectionNames) ) {
            return $this;
        }

        $this->sectionNames[] = $name;
        $this->sectionData[$name] = $data;

        return $this;

    }  // addSection()

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
     * @param $key The key to delete (either an object or a class name with namespace)
     * @param $when When the handler is executed (before or after parsed keys are added to the
     *   configuration)
     *
     * @return The callable key handler, or FALSE if no handler was defined.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteKeyTransformer($transformer)
    {
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

    /* ==========================================================================================
     * Accessors
     * ==========================================================================================
     */

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
     * Get the list of section names.
     *
     * @return An array of section names
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionNames()
    {
        return $this->sectionNames;
    }  // getSectionNames()

    /* ------------------------------------------------------------------------------------------
     * @param $sectionName The name of the section to examine.
     *
     * @return TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function sectionExists($sectionName)
    {
        return in_array($sectionName, $this->sectionNames);
    }  // getSectionNames()

    /* ------------------------------------------------------------------------------------------
     * @param $sectionName The name of the section to examine.
     *
     * @return TRUE if a section is defined
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionData($sectionName)
    {
        return ( array_key_exists($sectionName, $this->sectionData)
                 ? $this->sectionData[$sectionName]
                 : false
            );
    }  // getSectionData()

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
