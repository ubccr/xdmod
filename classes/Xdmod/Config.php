<?php

namespace Xdmod;

use ArrayAccess;
use Exception;
use CCR\Json;

class Config implements ArrayAccess
{
    /**
     * The property that contains meta-data for the internal data
     * representation.
     *
     * @var string
     */
    const META_DATA_PROPERTY = 'meta-data';

    /**
     * Instance for singleton pattern;
     *
     * @var Config
     */
    private static $instance = null;

    /**
     * Config data by section.
     *
     * @var array
     */
    private $sections = array();

    /**
     * Config data by module by section
     *
     * @var array
     **/
    private $moduleSections = array();

    /**
     * Private constructor for factory pattern.
     */
    private function __construct()
    {
    }

    /**
     * Factory method.
     */
    public static function factory()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        try {
            $this->getFilePath($offset);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        if (!isset($this->sections[$offset])) {
            $this->sections[$offset] = $this->loadSection($offset);
        }

        return $this->sections[$offset];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception("Cannot set config section '$offset'");
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        throw new Exception("Cannot unset config section '$offset'");
    }

    /**
     * Attempt to retrieve the configuration data for the specified section.
     *
     * @param string $section the section to retrieve
     * @return mixed
     */
    public function getModuleSection($section, array $modules = array())
    {
        if (!isset($this->moduleSections[$section])) {
            $this->moduleSections[$section] = $this->loadModuleSection($section, $modules);
        }
        return $this->moduleSections[$section];
    }

    /**
     * Load a config section from a file.
     *
     * @param string $section The name of the config section.
     *
     * @return mixed
     */
    private function loadSection($section)
    {
        $file = $this->getFilePath($section);

        $data = Json::loadFile($file);

        $partialFiles = $this->getPartialFilePaths($section);

        foreach ($partialFiles as $file) {
            $partialData = Json::loadFile($file);
            $data = $this->mergeData($data, $partialData);
        }

        return $data;
    }

    /**
     * Load a section in a module aware fashion. This means that the section
     * data is returned for each module that is currently installed.
     *
     * @param string $section
     *
     * @return mixed
     **/
    private function loadModuleSection($section, array $modules = array())
    {
        $file = $this->getFilePath($section);

        $data = Json::loadFile($file);
        if (!empty($data)) {
            $data = $this->addMetaDataRecursive(
                $data,
                array(
                    'modules' => array(DEFAULT_MODULE_NAME)
                )
            );
        }


        $partialFiles = $this->getPartialFilePaths($section);

        foreach ($partialFiles as $file) {
            $module = $this->getModule(pathinfo($file, PATHINFO_FILENAME));
            if (!in_array($module, $modules)) {
                $module = DEFAULT_MODULE_NAME;
            }

            $partialData = Json::loadFile($file);
            if (!empty($partialData)) {
                $partialData = $this->addMetaDataRecursive(
                    $partialData,
                    array(
                        'modules' => array($module)
                    )
                );
            }

            $data = $this->mergeData($data, $partialData);
        }

        return $data;
    }

    /**
     * Attempt to retrieve the module name from a specified file name.  This is done by first
     * removing the file extension (typically ".json") and then checking for a submodule delimiter
     * (:) and returning the portion of the filename before the delimiter, or the filename if
     * no delimiter was present.
     *
     * For example:
     * value-analytics.json will return "value-analytics"
     * supremm:job-viewer.json will return "supremm"
     *
     * @param string $fileName the file name to be parsed.
     *
     * @return string
     **/
    private function getModule($fileName)
    {
        // Remove the extension (which _should_ be .json but could be capitalized)
        if (false !== ($extIndex = strrpos($fileName, '.'))) {
            $results = substr($fileName, 0, strlen($fileName) - $extIndex);
        } else {
            $results = $fileName;
        }

        // If there is a sub-module delimiter then take the portion before
        if (false !== ($index = strpos($results, ':'))) {
            $results = substr($results, 0, $index);
        }

        return $results;
    }

    /**
     * Get the file name for a configuration section.
     *
     * @param string $section
     *
     * @return string
     *
     * @throws Exception if there is not a file for the specified section
     * @throws Exception if the file for the specified section cannot be read
     */
    public function getFilePath($section)
    {
        $fileName = $this->getNormalizedName($section) . '.json';
        $filePath = $this->getConfigDirPath() . '/' . $fileName;

        if (!is_file($filePath)) {
            throw new Exception("Configuration file '$fileName' not found");
        }

        if (!is_readable($filePath)) {
            throw new Exception("Configuration file '$fileName' not readable");
        }

        return $filePath;
    }

    /**
     * Get the list of partial configuration files.
     *
     * The partial configuration files are intended to allow option
     * Open XDMoD packages to override or extend existing configuration.
     * For each (JSON) configuration file a directory may exist with
     * .json replaced by .d and the directory may contain .json files
     * with partial configuration data that will be merged into the
     * primary configuration file.
     *
     * @param string $section
     *
     * @return array
     */
    public function getPartialFilePaths($section)
    {
        $dirName = $this->getNormalizedName($section) . '.d';
        $dirPath = $this->getConfigDirPath() . '/' . $dirName;

        if (!is_dir($dirPath)) {
            return array();
        }

        $filePaths = glob("$dirPath/*.json");
        sort($filePaths);

        return $filePaths;
    }

    /**
     * Normalize a section name.
     *
     * @param string $section
     *
     * @return string
     */
    protected function getNormalizedName($section)
    {
        return preg_replace('/[^a-z_]/', '_', $section);
    }

    /**
     * Get the configuration directory path.
     *
     * @return string
     */
    public function getConfigDirPath()
    {
        return CONFIG_DIR;
    }

    /**
     * Merge partial data into primary data.
     *
     * NOTE: This only works if the top level data element is an
     * associative array.
     *
     * @param array $data Main data.
     * @param array $partialData Partial data.
     *
     * @return array
     *
     * @throws Exception
     */
    protected function mergeData(array $data, array $partialData)
    {
        foreach ($partialData as $key => $value) {
            if (substr($key, 0, 1) == '+') {

                // If the key starts with a "+", merge the values.

                $mainKey = substr($key, 1);
                if (array_key_exists($mainKey, $data)) {
                    $mainValue = $data[$mainKey];
                } else {
                    $mainValue = array();
                }

                if (!is_array($mainValue)) {
                    $msg
                        = 'Cannot merge non-array/object values: '
                        . 'key = ' . $mainKey
                        . ', '
                        . 'main data = ' . json_encode($data)
                        . ', '
                        . 'main data value = ' . json_encode($mainValue)
                        . ', '
                        . 'partial data value = ' . json_encode($value);
                    throw new Exception($msg);
                }

                if ($this->isAssocArray($mainValue)) {
                    // Recurse if the value is an associative array
                    // (JSON Object).
                    $data[$mainKey] = $this->mergeData($mainValue, $value);
                } else {

                    // Just merge if the value is a numeric array
                    // (JSON Array).
                    $data[$mainKey] = array_merge($mainValue, $value);
                }
            } elseif ($key === self::META_DATA_PROPERTY && isset($data[$key]) && is_array($value) && is_array($data[$key])) {
                // If we're processing the meta_data property then just merge
                // it on in.
                $data[$key] = array_merge_recursive($data[$key], $value);
            } else {
                // Otherwise overwrite the values.
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Check if value is an associative (not numeric) array.
     *
     * Since we're assuming that all the arrays are from JSON, this
     * function just checks to for keys that are strings.
     *
     * @param array $value
     *
     * @return bool True if the value is an associative array.
     */
    protected function isAssocArray($value)
    {
        if (!is_array($value)) {
            return false;
        }
        return (bool)count(array_filter(array_keys($value), 'is_string'));
    }

    /**
     * Filter $data by the provided $metaData. $data should be annotated via the
     * loadModuleSection function, or contain nodes with 'meta-data' attributes.
     * Filtered in this case means every node in $data that has some
     * intersection with $metaData.
     *
     * @param array $data     that data to be filtered
     * @param array $metaData the data that will be used to do the filtering
     * @return array the nodes in $data that have meta-data entries in common
     *               with $metaData
     */
    public function filterByMetaData(array $data, array $metaData)
    {
        $results = array();
        $processChildren = false;
        $isAssocArray = $this->isAssocArray($data);
        $hasMetaData = isset($data[self::META_DATA_PROPERTY]);
        $isArray = is_array($data);

        /* If we have a node that has a meta-data property and it has some
         * intersection with the filtering meta-data then we want to process
         * any children it may have for inclusion as well.
         */
        if ($isAssocArray && $hasMetaData) {
            $intersection = $this->arrayRecursiveIntersect(
                $data[self::META_DATA_PROPERTY],
                $metaData
            );
            $processChildren = !empty($intersection);
        } elseif ($isArray && !$hasMetaData) {
            /* the other case where we want to possibly process a nodes children
             * is when it is an array ( perhaps associative ) but definitely
             * does not have a meta-data property. This allows us to process
             * data structures that look like:
             * $data = array(
             *     'mgr' => array(
             *         'permitted_modules' => array(
             *             array( ... added by module 1 ... ),
             *             array( ... added by module 2 ... )
             *         ),
             *         'meta-data' => array( .. meta-data that matches .. )
             *     )
             * )
             * 'mgr' has meta-data so it will be included ( note it's an
             * associative array ), 'permitted_modules' is not an associative
             * array but we still want to interrogate / possibly include it's
             * children. Otherwise we would not have visibility into arrays that
             * contain data we would like to have filtered.
             */
            $processChildren = true;
        }

        if ($processChildren) {
            foreach ($data as $key => $value) {
                // we are not interested in the 'meta-data' property so skip it
                // is found.
                if ($key === self::META_DATA_PROPERTY) {
                    continue;
                }

                $isAssocArray = $this->isAssocArray($value);
                $isArray = is_array($value);
                $hasMetaData = isset($value[self::META_DATA_PROPERTY]);

                /* If:
                 *   - the value is an associative array & has meta-data that
                 *    intersects with the filter
                 * ...  or ...
                 *   - the value is an array just not associative
                 * Then:
                 *   - we want to inspect the child nodes of value, so send it
                 *     through the filter.
                 */
                if ($isAssocArray && $hasMetaData) {
                    $intersection = $this->arrayRecursiveIntersect(
                        $value[self::META_DATA_PROPERTY],
                        $metaData
                    );
                    if (!empty($intersection)) {
                        $results[$key] = $this->filterByMetaData($value, $metaData);
                    }
                } elseif (!$isAssocArray && $isArray) {
                    $results[$key] = $this->filterByMetaData($value, $metaData);
                } elseif (!$isAssocArray && !$isArray) {
                    /* This accounts for the data structure:
                     * $data = array(
                     *     ...
                     *     'key' => 'value'
                     *     ...
                     * );
                     *
                     */
                    $results[$key] = $value;
                }
            }
        }
        return $results;
    }

    /**
     * Add the specified metaData to all associative arrays contained within the
     * parameter $data. This includes nested associative arrays.
     *
     * @param array $data the data that is going to have it's meta-data
     *                        modified.
     * @param array $metaData the meta-data that will be merged into the data's
     *                        existing meta-data.
     * @return array the newly meta-data'd data.
     */
    protected function addMetaDataRecursive(array $data, array $metaData)
    {
        $isAssocArray = $this->isAssocArray($data);
        $hasMetaData = isset($data[self::META_DATA_PROPERTY]);
        if ($isAssocArray && !$hasMetaData) {
            $data[self::META_DATA_PROPERTY] = $metaData;
        } elseif ($isAssocArray && $hasMetaData) {
            $data[self::META_DATA_PROPERTY] = array_merge_recursive(
                $data[self::META_DATA_PROPERTY],
                $metaData
            );
        }
        foreach ($data as $key => $value) {
            $isAssocArray = $this->isAssocArray($value);
            if ($key !== self::META_DATA_PROPERTY && (is_array($value) || $isAssocArray)) {
                $data[$key] = $this->addMetaDataRecursive($value, $metaData);
            }
        }
        return $data;
    }

    /**
     * Recursively process the provided arrays and attempt to find the key /
     * values that they have in common.
     *
     * @param array $left
     * @param array $right
     * @return array of key / values that they have in common.
     */
    protected function arrayRecursiveIntersect(array $left, array $right)
    {
        $results = array();

        foreach ($left as $key => $value) {
            $isArray = is_array($value);
            $rightArray = is_array($right);
            $isAssoc = $this->isAssocArray($value);
            $rightAssoc = $this->isAssocArray($right);

            // If either of the values are associative then just perform the
            // regular intersection.
            if ($isAssoc || $rightAssoc) {

                // If the right array has a matching key...
                if (array_key_exists($key, $right)) {

                    // and both values are arrays...
                    if (is_array($value) && is_array($right[$key])) {

                        // attempt to find the intersection between values and if
                        // there are, include that in the results.
                        $intersect = $this->arrayRecursiveIntersect($value, $right[$key]);
                        if (count($intersect)) {
                            $results[$key] = $intersect;
                        }
                    } else {

                        /* handle the case where the data looks like:
                         * $left = array(
                         *   ...
                         *   'key' => 'value'
                         *   ...
                         * );
                         *
                         * $right = array(
                         *   ...
                         *   'key' => 'value'
                         *   ...
                         * );
                         *
                         */
                        if ($value === $right[$key]) {
                            $results[$key] = $value;
                        }
                    }
                }
            } elseif (!$isArray && $rightArray && !$rightAssoc) {
                /* If the value is not an array ( and there-by not an associative
                 * array ) AND the right value is an array ( but not an associative
                 * array ) then check for values existence in the right value and if
                 * found then include value in the results. This handles data
                 * that looks like:
                 * $left = array(
                 *   'key' => 'value'
                 * );
                 * $right = array(
                 *   'key' => array(
                 *      'value',
                 *      'value2',
                 *      ...
                 *   )
                 * );
                 */
                if (in_array($value, $right)) {
                    $results[] = $value;
                }
            }
        }

        return $results;
    }
}
