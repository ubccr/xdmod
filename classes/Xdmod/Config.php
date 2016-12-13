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
                $mainValue = $data[$mainKey];

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
