<?php

namespace Xdmod;

use ArrayAccess;
use Exception;
use CCR\Json;

class Config implements ArrayAccess
{

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
     **/
    public function getModuleSection($section)
    {
        if (!isset($this->moduleSections[$section])) {
            $this->moduleSections[$section] = $this->loadModuleSection($section);
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
    private function loadModuleSection($section)
    {
        $results = array();

        $file = $this->getFilePath($section);

        $data = Json::loadFile($file);

        $results[DEFAULT_MODULE_NAME] = $data;

        $partialFiles = $this->getPartialFilePaths($section);

        foreach($partialFiles as $file) {
            $module = $this->getModule(pathinfo($file, PATHINFO_FILENAME));

            $partialData = Json::loadFile($file);
            if (isset($results[$module]) && is_array($results[$module])) {
                $results[$module] = array_merge($results[$module], $this->sanitizeKeys($partialData));
            } else {
                $results[$module] = $this->sanitizeKeys($partialData);
            }
        }

        return $results;
    }

    /**
     * Attempt to retrieve the module name from a specified file name.
     * This works by splitting on any one of the following characters:
     * '.', '_', '-' and returning the first value of the split.
     * Example: 'supremm-single-job-viewer.json'
     * Result:  'supremm'
     *
     * @param string $fileName the file name to be parsed.
     *
     * @return string
     **/
    private function getModule($fileName)
    {
        $results = preg_split('/[._-]/', $fileName);
        return $results[0];
    }

    /**
     * Attempt to walk the provided array and if there are any keys with that
     * start with a '+' character, replace that key with that keys value minus
     * the '+' character.
     *
     * @param array $data the data whose keys will be sanitized
     *
     * @return array
     **/
    private function sanitizeKeys($data)
    {
        $results = array();

        foreach($data as $key => $value) {
            $hasPlus = substr($key, 0, 1) === '+';
            $newKey = false === $hasPlus ? $key : substr($key, 1);
            if (is_array($value) && $this->isAssocArray($value)) {
                $results[$newKey] = $this->sanitizeKeys($value);
            } else {
                $results[$newKey] = $value;
            }
        }

        return $results;
    }

    /**
     * Get the file name for a configuration section.
     *
     * @param string $section
     *
     * @return string
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
     */
    protected function mergeData(array $data, array $partialData)
    {
        foreach ($partialData as $key => $value) {
            if (substr($key, 0, 1) == '+') {

                // If the key starts with a "+", merge the values.

                $mainKey   = substr($key, 1);
                if (array_key_exists($mainKey, $data)){
                    $mainValue = $data[$mainKey];
                }
                else {
                    $mainValue = null;
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
                        . 'partial data value = '. json_encode($value);
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
            } else {

                // Otherwise overwrite the values.
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Check if an array is associative (not numeric).
     *
     * Since we're assuming that all the arrays are from JSON, this
     * function just checks to for keys that are strings.
     *
     * @param array $arr
     *
     * @return bool True if the array is associative.
     */
    protected function isAssocArray(array $arr)
    {
        return (bool)count(array_filter(array_keys($arr), 'is_string'));
    }
}
