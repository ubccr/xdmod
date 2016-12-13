<?php
/* ==========================================================================================
 * Read and parse a JSON configuration file. The configuration file contains keys with associated
 * values (scalar, object, array) and may have handlers specified for particular keys TOP-LEVEL
 * keys.  For example, the "include" key may have a scalar filename as a value or an array of
 * filenames and a handler that includes the contents of the specified file(s) into the current
 * file. The key handlers may executed before adding the data in the parsed configuration (e.g.,
 * include handlers) or after adding the data (e.g., defaults handlers)
 *
 * The process for parsing the configuration is as follows:
 *
 * 1. Read and parse the JSON configuration.
 * 2. Build up an internal representation of the configuration by:
 *    a. Execute any pre-handlers found in the parsed configuration and add their result to the
 *       internal representation.
 *    b. Add any parsed keys without handlers to the internal configuration
 *    c. Execute any post-handlers found in the parsed configuration on the current internal
 *       configuration, including the results of any pre-handlers.
 * 3. Build the list of top-level sections and associated data based on the fully processed
 *    internal configuraition.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-15
 * ==========================================================================================
 */

namespace ETL;

// PEAR logger
use Log;

use Exception;
use stdClass;
use ETL\DataEndpoint\DataEndpointOptions;

class Configuration extends Loggable implements \Iterator
{
    // Keys and handlers. Handlers will process the values associated with the keys with
    // pre-handlers running before the parsed keys are added to the configuration and post keys
    // processed afterwards. Note that this applies only to TOP LEVEL KEYS and are not applied
    // recursively.

    private $keyHandlersPre = array();
    private $keyHandlersPost = array();

    // Handler types. Pre and Post referrs to the adding of the keys parsed from the configuration
    // file to the internal representation. PRE_HANDLERs are processed prior to adding parsed keys
    // and POST_HANDLERS are processed after adding parsed keys.
    const PRE_HANDLER = 1;
    const POST_HANDLER = 2;

    // JSON object key represeting a comment to skip
    const COMMENT_KEY = "#";

    // NOTE: Any properties that need to be accessed in a handler defined in a subclass cannot be
    // private.

    // Configuraiton filename
    protected $filename = null;

    // The base directory for any paths that are not fully qualified
    protected $baseDir = null;

    // The parsed configuration file prior to manipulation
    protected $parsedConfig = null;

    // The configuration constructed from the parsed file after processing any handlers and
    // performing manipulations
    protected $constructedConfig = null;

    // The names of all sections discovered in the config file
    protected $sectionNames = array();

    // An associative array where keys are section names and values the data associated with that
    // section.
    protected $sectionData = array();

    /* ------------------------------------------------------------------------------------------
     * Constructor. Read and parse the configuration file.
     *
     * @param $filename Name of the JSON configuration file to parse
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($filename, $baseDir = null, Log $logger = null)
    {
        parent::__construct($logger);

        if ( empty($filename) ) {
            $msg = "Configuration filename cannot be empty";
            $this->logAndThrowException($msg);
        }

        // Apply the base directory to the current filename if it was provided.

        $this->filename = ( 0 !== strpos($filename, "/") && null !== $baseDir
                            ? $baseDir . "/" . $filename
                            : $filename );

        // If the base directory was provided use that value, otherwise use the dirname of the
        // configuration filename (which may be the current directory). If the directory is relative
        // prepend the current directory so we have a fully qualified path.

        $this->baseDir = ( null === $baseDir ? dirname($filename) : $baseDir );
        if ( 0 !== strpos($this->baseDir, "/") ) {
            $this->baseDir = getcwd() . "/" . $this->baseDir;
        }

        $this->addKeyHandler('include', array($this, 'includeHandler'), self::PRE_HANDLER);

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
        $this->constructedConfig = null;
    }  // cleanup()

    /* ------------------------------------------------------------------------------------------
     * Recursively remove any comments from the configuration. A comment is considered any key
     * starting with the comment character (e.g., #). This allows us to easily add documentation and
     * comment out keys during testing.
     *
     * "#": "This is a comment"
     * "# record": {
     *     "#": "This entire 'record' key above is commented out"
     * }
     *
     * @param $config A stdClass containing the parsed JSON
     * ------------------------------------------------------------------------------------------
     */

    protected function removeComments(stdClass &$config)
    {
        foreach ($config as $key => &$value) {
            if ( 0 === strpos($key, self::COMMENT_KEY) ) {
                unset($config->$key);
                continue;
            }
            if ( is_object($value) ) {
                $this->removeComments($value);
            }
        }
        unset($value);

    }  // removeComments()

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

        // Do not parse as an array because we want to differentiate between an object and an array

        $this->parsedConfig = $jsonFile->parse();
        $this->removeComments($this->parsedConfig);

        // Build up an internal configuration based on the parsed configuration plus the output of
        // any handlers. This allows us to do things like handle include files, apply defaults, etc.
        //
        // 1. For each key parsed from the configuration that has a handler, execute the handler
        //    passing it the value of the key along with the current internal configuration. The
        //    handler will return an updated internal configuration.
        // 2. Add any keys and associated values that do not have handlers to the internal
        //    configuration.
        // 3. Examine the internal configuration for any keys with post-handlers and execute those
        //    handlers passing it the value of the key along with the current internal
        //    configuration. The handler will return an updated internal configuration.

        $constructedConfig = new stdClass;

        // All top-level keys parsed from the file, minus comments
        $parsedKeys = array_keys(get_object_vars($this->parsedConfig));

        // Find the keys found in the config that have handlers
        $parsedKeysWithHandlers = array_intersect($parsedKeys, array_keys($this->keyHandlersPre));

        foreach ( $parsedKeysWithHandlers as $key ) {

            $handler = $this->keyHandlersPre[$key];
            
            // Callback takes (callable handler, reserved key data, current config)
            // Callback returns updated config
            
            $constructedConfig = call_user_func($handler, $key, $this->parsedConfig->$key, $constructedConfig);

        }  // foreach ( $configKeys as $configKey => $configValue )

        // Find all other keys parsed from the file
        $parsedKeysWithoutHandlers = array_diff($parsedKeys, $parsedKeysWithHandlers);
        
        // Copy the non-reserved keys into the configuration, overriding any existing keys

        foreach ( $parsedKeysWithoutHandlers as $key ) {
            $constructedConfig->$key = $this->parsedConfig->$key;
        }

        // Find the keys found in the current config that have handlers. These keys may have been
        // added by a pre-handler.
        $parsedKeysWithHandlers = array_intersect(array_keys(get_object_vars($constructedConfig)), array_keys($this->keyHandlersPost));
        
        foreach ( $parsedKeysWithHandlers as $key ) {
            
            $handler = $this->keyHandlersPost[$key];
            
            // Callback takes (callable handler, reserved key data, current config)
            // Callback returns updated config

            $constructedConfig = call_user_func($handler, $key, $constructedConfig->$key, $constructedConfig);

        }  // foreach ( $configKeys as $configKey => $configValue )

        // Process the constructed configuration to create sections

        foreach ( $constructedConfig as $key => $value ) {
            $this->addSection($key, $value);
        }

        $this->constructedConfig = $constructedConfig;

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
        if ( in_array($name, $this->sectionNames) ) {
            return $this;
        }

        $this->sectionNames[] = $name;
        $this->sectionData[$name] = $data;

        return $this;

    }  // addSection()

    /* ==========================================================================================
     * Key Handlers
     * ==========================================================================================
     */

    /* ------------------------------------------------------------------------------------------
     * Include all keys from a specified file into the configuration that we are constructing for
     * this file. If multiple files are processed, any keys in later files will overrite previous
     * files.
     *
     * @param $key The key that we are processing
     * @param $value The value associated with the key
     * @param $config The current state of the constructed configuration file
     *
     * @return The updated configuration file after processing the handler
     * ------------------------------------------------------------------------------------------
     */

    protected function includeHandler($key, $value, $config)
    {

        if ( null === $value ) {
            return $config;
        } else if ( is_object($value) ) {
            $msg = "Include handler for $key supports only scalars and arrays, skipping.";
            $this->logger->warning($msg);
            return $config;
        }

        // Normalize the value to an array to support multiple includes
        $value = ( is_array($value) ? $value : array($value) );

        foreach ( $value as $includeFilename ) {
            
            if ( 0 !== strpos($includeFilename, "/") ) {
                $includeFilename = $this->baseDir . "/" . $includeFilename;
            }

            $this->logger->debug("Processing include file '$includeFilename'");

            if ( ! is_readable($includeFilename) ) {
                $msg = "Include file not readable: '$includeFilename', skipping.";
                $this->logger->warning($msg);
                continue;
            }
         
            $includeFile = new Configuration($includeFilename);
            $includeFile->initialize();

            // Include each key found in the file. In some cases, it may be desirable to include the
            // entire file as-is such as an inline include. This should be done using a different
            // handler.

            foreach ( $includeFile as $key => $value ) {
                $config->$key = $value;
            }

        }  // foreach ( $value as $includeFilename )

        return $config;

    }  // includeHandler()

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
     * Key handler management
     * ==========================================================================================
     */

    /* ------------------------------------------------------------------------------------------
     * Add a key and handler function.
     *
     * @param $key A key that will be associated with a handler
     * @param $handler A callable handler
     * @param $when When the key handler should be run (e.g., before or after adding parsed
     *   configuration keys)
     *
     * @return This object for method chaining
     *
     * @throw Exception If the key is invalid
     * @throw Exception The handler is not callable
     * ------------------------------------------------------------------------------------------
     */

    public function addKeyHandler($key, $handler, $when = self::PRE_HANDLER)
    {
        if ( ! is_string($key) ) {
            $msg = "Invalid key: '$key'";
            $this->logAndThrowException($msg);
        }

        if ( ! is_callable($handler) ) {
            $msg = "Handler for key '$key' is not callable: '" . print_r($handler, true) . "'";
            $this->logAndThrowException($msg);
        }

        if ( self::PRE_HANDLER == $when ) {
            $this->keyHandlersPre[$key] = $handler;
        } else {
            $this->keyHandlersPost[$key] = $handler;
        }

        return $this;

    }  // addKeyHandler()

    /* ------------------------------------------------------------------------------------------
     * Check to see if a key handler has been defined.
     *
     * @param $key The key to check
     * @param $when When the handler is executed (before or after parsed keys are added to the
     *   configuration)
     *
     * @return TRUE if key has a handler defined
     * ------------------------------------------------------------------------------------------
     */

    public function hasKeyHandler($key, $when)
    {
        $retval = false;

        if ( self::PRE_HANDLER == $when && array_key_exists($key, $this->keyHandlersPre) ) {
            $retval = true;
        } else if ( self::POST_HANDLER == $when && array_key_exists($key, $this->keyHandlersPost) ) {
            $retval = true;
        }

        return $retval;

    }  // hasKeyHandler()

    /* ------------------------------------------------------------------------------------------
     * Get a key handler.
     *
     * @param $key The key to check
     * @param $when When the handler is executed (before or after parsed keys are added to the
     *   configuration)
     *
     * @return The callable key handler, or FALSE if not defined.
     * ------------------------------------------------------------------------------------------
     */

    public function getKeyHandler($key, $when) {

        $retval = false;

        if ( self::PRE_HANDLER == $when && array_key_exists($key, $this->keyHandlersPre) ) {
            $retval = $this->keyHandlersPre[$key];
        } else if ( self::POST_HANDLER == $when && array_key_exists($key, $this->keyHandlersPost) ) {
            $retval = $this->keyHandlersPost[$key];
        }

        return $retval;

    }  // getKeyHandler()

    /* ------------------------------------------------------------------------------------------
     * Get all key handlers for the specified value of $when (pre or post)
     *
     * @param $when When the handler is executed (before or after parsed keys are added to the
     *   configuration)
     *
     * @return An associative array of where the key is the configuration key and the value is the
     *   callable handler.
     * ------------------------------------------------------------------------------------------
     */

    public function getKeyHandlers($when) {

        if ( self::PRE_HANDLER == $when ) {
            return $this->keyHandlersPre;
        } else if ( self::POST_HANDLER == $when ) {
            return $this->keyHandlersPost;
        }
        return false;
    }  // getKeyHandlers()

    /* ------------------------------------------------------------------------------------------
     * Delete a key handler and return it.
     *
     * @param $key The key to delete
     * @param $when When the handler is executed (before or after parsed keys are added to the
     *   configuration)
     *
     * @return The callable key handler, or FALSE if no handler was defined.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteKeyHandler($key, $when)
    {
        $retval = false;

        if ( self::PRE_HANDLER == $when && array_key_exists($key, $this->keyHandlersPre) ) {
            $retval = $this->keyHandlersPre[$key];
            unset($this->keyHandlersPre[$key]);
        } else if ( self::POST_HANDLER == $when && array_key_exists($key, $this->keyHandlersPost) ) {
            $retval = $this->keyHandlersPost[$key];
            unset($this->keyHandlersPost[$key]);
        }

        return $retval;
    }  // deleteKeyHandler()

    /* ==========================================================================================
     * Accessors
     * ==========================================================================================
     */

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
