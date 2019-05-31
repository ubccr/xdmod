<?php
/**
 * Interface for a class to read and parse a JSON configuration file containing keys with associated
 * values (scalar, object, array). Values that are objects are considered to be fixed entities that
 * define something in the configuration. Key transformers may also be added allowing us to
 * dynamically extend functionality and transform keys and their associated values. For example, a
 * comment transformer may strip comments from the file so downstream processing does not need to
 * deal with them and a reference transformer may provide the ability to reference keys/data within
 * other files to promote reuse.
 */

namespace Configuration;

use Log;  // PEAR logger

interface iConfiguration extends \Iterator
{
    /**
     * A factory / helper method for instantiating a Configuration object, initializing it, and
     * returning the results of its `toAssocArray` function.
     *
     * @param string $filename The base configuration file name to be processed.
     * @param string|null $baseDir The directory in which $filename can be found.
     * @param Log|null $logger A Log instance that Configuration will utilize during its processing.
     * @param array $options The options that will be used during construction of the Configuration object.
     *
     * @return array the results of the instantiated configuration objects `toAssocArray` function.
     */

    public static function assocArrayFactory($filename, $baseDir = null, Log $logger = null, array $options = array());

    /**
     * A helper function that instantiates, initializes, and returns a Configuration object.
     *
     * @param string $filename The base configuration file name to be processed.
     * @param string|null $baseDir The directory in which $filename can be found.
     * @param Log|null $logger A Log instance that Configuration will utilize during its processing.
     * @param array $options The options that will be used during construction of the Configuration object.
     *
     * @return Configuration an initialized instance of Configuration.
     */

    public static function factory($filename, $baseDir = null, Log $logger = null, array $options = array());

    /**
     * Enable the object cache.
     */

    public static function enableObjectCache();

    /**
     * Disable the object cache and clear any cached objects.
     */

    public static function disableObjectCache();

    /**
     * Constructor. Read and parse the configuration file.
     *
     * *** Note that the constructor should not be called directly (e.g., new Configuration). Use
     * *** the factory() method instead.
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
     */

    public function __construct($filename, $baseDir = null, Log $logger = null, array $options = array());

    /**
     * Initialize the configuration objecton.
     *
     * @param boolean $force TRUE to force re-initialization of the configuration even if it has
     *   previously been initialized.
     *
     * @return Configuration This object to support method chaining.
     */

    public function initialize($force = false);

    /**
     * Clean up intermediate information that we don't need to keep around after processing.
     */

    public function cleanup();

    /**
     * Get the list of section names.
     *
     * @return array An array of section names
     */

    public function getSectionNames();

    /**
     * @param string $name The name of the section to examine.
     *
     * @return array TRUE if a section is defined
     */

    public function sectionExists($name);

    /**
     * @param string $name The name of the section to examine.
     *
     * @return boolean TRUE if a section is defined
     */

    public function getSectionData($name);

    /* ==========================================================================================
     * Key Transformer Management
     * ==========================================================================================
     */

    /**
     * Add a key transformer to the configuration, warning and overwriting if a duplicate is added.
     *
     * @param iConfigFileKeyTransformer $transformer A key transformer to add.
     *
     * @return Configuration This object for method chaining
     */

    public function addKeyTransformer(iConfigFileKeyTransformer $transformer);

    /**
     * Return TRUE if the transformer has already been added to this configuration file.
     *
     * @param string|iConfigFileKeyTransformer $transformer A key transformer object or class name
     *
     * @return boolean TRUE if the transformer has already been added to this configuration file.
     */

    public function hasKeyTransformer($transformer);

    /**
     * Get all key transformers for this configuration file.
     *
     * @return array An associative array of where the key is the transformer class name and the
     *   value is the transformer object.
     */

    public function getKeyTransformers();

    /**
     * Delete a key handler and return it.
     *
     * @param string|iConfigFileKeyTransformer $transformer A key transformer object or class
     *   name configuration)
     *
     * @return Configuration This object for method chaining
     */

    public function deleteKeyTransformer($transformer);

    /**
     * Get the base directory for this configuration.
     *
     * @return string The base directory for this configuration.
     */

    public function getBaseDir();

    /**
     * @return array The associative array of options that was passed in from the parent, and
     *   possibly augmented locally.
     */

    public function getOptions();

    /**
     * @return string The late static binding name for this class (e.g., the instantiated class
     * name)
     */

    public function getCalledClassName();

    /**
     * @return VariableStore The VariableStore currently in use for this configuration.
     */

    public function getVariableStore();

    /**
     * Getter method for accessing data keys using object notation.
     *
     * NOTE: When querying for existance we can't use isset() and must use NULL === $options->key
     *
     * @param string $property The name of the property to retrieve
     *
     * @return mixed The property, or NULL if the property doesn't exist.
     */

    public function __get($property);

    /**
     * Return TRUE if a property is set and is not NULL.
     *
     * @param string $property The name of the property to retrieve
     *
     * @return boolean TRUE if the property exists and is not NULL, or FALSE otherwise.
     */

    public function __isset($property);

    /**
     * Return this Configuration's $filename property.
     *
     * @return string
     */

    public function getFilename();

    /**
     * Get the configuration after applying transforms. Note that NULL will be returned if
     * initialize() has not been called or if cleanup() has been called.
     *
     * @return stdClass|null A stdClass representing the transformed configuration.
     */

    public function toStdClass();

    /**
     * Return the JSON representation of the parsed and translated Configuration.
     *
     * @return string A JSON representation of the Configuration object.
     */

    public function toJson();

    /**
     * Retrieve this `Configuration` objects data formatted as an associative array.
     *
     * @return array
     */

    public function toAssocArray();

    /**
     * Generate a string representation of this object. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return string A string representation of the object
     */

    public function __toString();
}
