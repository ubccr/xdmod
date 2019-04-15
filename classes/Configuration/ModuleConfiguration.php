<?php

namespace Configuration;

use Exception;
use Log;
use stdClass;

/**
 * Class ModuleConfiguration
 *
 * Provides for tracking which module provides which portions of this configuration files contents.
 * You can retrieve which portions of this configuration file were provided by a particular module
 * via the `filterByMetaData`.
 *
 * Example Merged Config[ XDMoD module, additional module: NewModule ]:
 * $transformedConfig = stdClass(
 *     roles: stdClass(
 *         default: stdClass(
 *             query_descripters: array(
 *                 stdClass(
 *                     realm: 'Jobs',
 *                     group_by: 'none'
 *                 ),
 *                 ...
 *                 stdClass(
 *                     realm: 'Cloud',
 *                     group_by: 'submission_venue'
 *                 ),
 *                 stdClass(
 *                     realm: 'NewRealm',
 *                     group_by: 'none'
 *                 )
 *             )
 *         )
 *     )
 * );
 *
 * The annotated config would look like:
 * $annotatedConfig = stdClass(
 *     roles: stdClass(
 *         default: stdClass(
 *             query_descripters: array(
 *                 stdClass(
 *                     realm: 'Jobs',
 *                     group_by: 'none',
 *                     modules: array(
 *                         'xdmod'
 *                         'NewRealm'
 *                     )
 *                 ),
 *                 ...
 *                 stdClass(
 *                     realm: 'Cloud',
 *                     group_by: 'submission_venue',
 *                     modules: array(
 *                         'xdmod',
 *                         'NewRealm'
 *                     )
 *                 ),
 *                 stdClass(
 *                     realm: 'NewRealm',
 *                     group_by: 'none',
 *                     modules: array(
 *                         'NewRealm'
 *                     )
 *                 )
 *             ),
 *             modules: array(
 *                 'xdmod',
 *                 'NewRealm'
 *             )
 *         ),
 *         modules: array(
 *             'xdmod',
 *             'NewRealm'
 *         )
 *     ),
 *     modules: array(
 *         'xdmod',
 *         'NewRealm'
 *     )
 * );
 *
 * Now, when a user calls `$moduleConfiguration->filterByRealm('xdmod');`, it will recursively
 * traverse the annotated config and only include those properties whose `modules` property includes
 * the requested module. Please note that the final $annotatedConfig will be the results of both
 * merging in any local config files contained in this configuration files .d directory and
 * processing of any `extends` found within the merged file.
 *
 * The overall processing order for `ModuleConfiguration` is:
 *  - Read specified configuration file ( ex. roles.json )
 *  - If the `local_config_dir` option is provided then all files found in this directory will be
 *    merged into the base file.
 *  - The merged configuration data will be recursively scanned for any `extends` keywords and these
 *    will be resolved. Order of extension will be taken into account.
 *
 * @package Configuration
 */
class ModuleConfiguration extends XdmodConfiguration
{
    /**
     * The module that this configuration belongs to
     *
     * @var string
     */
    protected $module = DEFAULT_MODULE_NAME;

    /**
     * $transformedConfig that has had all of it's properties recursively annotated.
     *
     * @var \stdClass
     */
    protected $annotatedConfig = null;

    public function __construct($filename, $baseDir = null, Log $logger = null, array $options = array())
    {
        parent::__construct($filename, $baseDir, $logger, $options);

        $this->annotatedConfig = new \stdClass();
    } // __construct

    /**
     * @see Configuration::preTransformTasks()
     *
     * @return Configuration
     */
    protected function preTransformTasks()
    {
        parent::preTransformTasks();

        $this->addKeyTransformer(new ModuleTransformer($this->logger));

        return $this;
    } // preTransformTasks

    /**
     * @see Configuration::interpretData()
     *
     * @return Configuration
     * @throws Exception
     */
    protected function interpretData()
    {
        parent::interpretData();

        $this->processAnnotated();

        return $this;
    } // interpretData

    /**
     * @see Configuration::merge()
     *
     * @param Configuration $localConfigObj
     * @param bool $overwrite
     * @return Configuration
     */
    protected function merge(Configuration $localConfigObj, $overwrite = false)
    {
        parent::merge($localConfigObj, $overwrite);

        if ($localConfigObj instanceof ModuleConfiguration) {
            $this->annotatedConfig = $this->mergeLocal(
                $this->annotatedConfig,
                $localConfigObj->getAnnotatedConfig(),
                $localConfigObj->getFilename(),
                $overwrite
            );
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
    } // merge

    /**
     * @see Configuration::postMergeTasks()
     *
     * @return Configuration
     * @throws Exception
     */
    protected function postMergeTasks()
    {
        parent::postMergeTasks();

        $this->processAnnotated();

        return $this;
    }  // postMergeTasks()

    /**
     * @see XdmodConfiguration::processExtends()
     *
     * @throws Exception
     */
    protected function processExtends()
    {
        parent::processExtends();

        // We need to make sure that we extend our annotatedConfig
        if (!$this->isLocalConfig) {
            $this->handleExtendsFor($this->annotatedConfig);
        }
    } // processExtends

    /**
     * Handles the population of the `annotatedConfig` member variable.
     *
     * @throws Exception
     */
    protected function processAnnotated()
    {
        if (count(get_object_vars($this->annotatedConfig)) === 0) {
            $transformedCopy = unserialize(serialize($this->transformedConfig));

            $this->annotatedConfig = $this->recursivelyAnnotate(
                $transformedCopy,
                array(
                    'modules' => array($this->module)
                )
            );
        }
    } // processAnnotated

    /**
     * Provides the ability to recursively annotate $source with the properties contained in
     * $annotations.
     *
     * @param stdClass $source
     * @param array $annotations
     * @return stdClass the annotated $source
     * @throws Exception
     */
    protected function recursivelyAnnotate(stdClass $source, array $annotations)
    {
        foreach($annotations as $name => $value) {
            if (!property_exists($source, $name)) {
                $source->$name = $value;
            } else {
                $sourceValue = $source->$name;

                if(is_array($sourceValue) && is_array($value)) {
                    foreach($value as $key => $incomingValue) {
                        if (!in_array($incomingValue, $sourceValue)) {
                            array_push($sourceValue, $incomingValue);
                        }
                    }
                } else {
                    throw new Exception("Unable to recursively annotate non-array values.");
                }
            }

        }

        foreach($source as $property => $value) {
            if (is_object($value)) {
                $source->$property = $this->recursivelyAnnotate($value, $annotations);
            } elseif (is_array($value)) {
                foreach($value as $k => $v) {
                    if (is_object($v)) {
                        $value[$k] = $this->recursivelyAnnotate($v, $annotations);
                    }
                }
            }
        }

        return $source;
    } // recursivelyAnnotate

    /**
     * Helper function that determines whether or not $array contains an object. It is assumed that
     * array contents are homogeneous.
     *
     * @param array $array
     * @return bool
     */
    private function hasObjects(array $array)
    {
        foreach($array as $k => $v) {
            if (is_object($v)) {
                return true;
            }
        }
        return false;
    } // hasObjects

    /**
     * A helper method that allows the caller to just specify which module they want to filter this
     * configuration files contents by as opposed to worrying about building up the whole `$metadata`
     * argument for `filterByMetaData`.
     *
     * @param string $module the module that will be used to filter the contents of this
     * configuration file.
     *
     * @return mixed
     */
    public function filterByModule($module)
    {
        $modules = array(DEFAULT_MODULE_NAME);

        if ($module !== DEFAULT_MODULE_NAME) {
            $modules[] = $module;
        }

        $metadata = array(
            'modules' => $modules
        );

        return $this->filterByMetaData($metadata);
    }

    /**
     * Provides a non-recursive starting point for recursively filtering $source based on the
     * provided $metadata. Once the filtering is complete, the keys that were used to filter $source
     * will be removed.
     *
     * @param array $metadata
     * @param stdClass|null $source
     * @param bool $stripMetadata
     * @return mixed
     */
    protected function filterByMetaData(array $metadata, $source = null, $stripMetadata = true) {
        if ($source === null) {
            $source = unserialize(serialize($this->annotatedConfig));
        }

        $result = $this->performRecursiveFilter($metadata, $source);

        if ($stripMetadata === true) {
            $properties = array_keys($metadata);

            // Make sure that 'module' is included in the properties that are removed from $source
            if (!in_array(ModuleTransformer::KEY, $properties)){
                $properties[] = ModuleTransformer::KEY;
            }
            $result = $this->recursiveStripProperties($result, $properties);
        }

        return $result;
    } // filterByMetaData

    /**
     * This function will recursively filter $source based on the provided $metadata.
     *
     * @param array $metadata
     * @param $source
     * @return mixed|null
     */
    protected function performRecursiveFilter(array $metadata, $source)
    {
        $metaDataKeys = array_keys($metadata);
        $sourceProperties = array_keys(get_object_vars($source));

        $similarKeys = array_intersect($metaDataKeys, $sourceProperties);

        $processChildren = false;
        if (!empty($similarKeys)) {
            foreach($similarKeys as $similarKey) {
                $intersection = array_intersect($metadata[$similarKey], $source->$similarKey);
                $processChildren = !empty($intersection);
                if (!$processChildren) {
                    return null;
                }
            }
        } else {
            return null;
        }

        if ($processChildren) {
            foreach($source as $property => &$value) {
                if (is_object($value)) {
                    $newValue = $this->performRecursiveFilter($metadata, $value);
                    if ($newValue !== null) {
                        $source->$property = $newValue;
                    }
                } elseif (is_array($value)) {
                    foreach($value as $k => $v) {
                        if (is_object($v)) {
                            $newValue = $this->performRecursiveFilter($metadata, $v);
                            if ($newValue === null) {
                                unset($value[$k]);
                            }
                        }
                    }
                }
            }
        }

        return $source;
    } // performRecursiveFilter

    /**
     * Recursively strip the provided $properties from $source.
     *
     * @param $source
     * @param array $properties
     * @return mixed
     */
    protected function recursiveStripProperties($source, array $properties)
    {
        $sourceKeys = array_keys(get_object_vars($source));
        $removeProperties = array_intersect($properties, $sourceKeys);

        foreach ($removeProperties as $removeProperty) {
            unset($source->$removeProperty);
        }

        foreach ($source as $property => $value) {
            if (is_object($value)) {
                $this->recursiveStripProperties($value, $properties);
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_object($v)) {
                        $this->recursiveStripProperties($v, $properties);
                    }
                }
            }
        }

        return $source;
    } // recursiveStripProperties

    /**
     * Set this ModuleConfiguration's $module property.
     *
     * @param $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    } // setModule

    /**
     * Retrieve this ModuleConfiguration's $module property.
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    } // getModule

    /**
     * Retrieve this ModuleConfiguration's $annotatedConfig property.
     *
     * @return stdClass
     */
    public function getAnnotatedConfig()
    {
        return $this->annotatedConfig;
    } // getAnnotatedConfig
}
