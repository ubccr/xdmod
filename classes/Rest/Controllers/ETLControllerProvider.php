<?php

namespace Rest\Controllers;

use ArrayObject;
use CCR\Json;
use Configuration\Configuration;
use ETL\Configuration\EtlConfiguration;
use ETL\DataEndpoint\File;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\EtlOverseer;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use Exception;
use RecursiveRegexIterator;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ETLControllerProvider extends BaseControllerProvider
{

    private $etlDir;

    private $configFiles;
    const SOURCES_ID = 'sources';
    const DESTINATIONS_ID = 'destinations';

    private static $defaultNodes = array(
        array(
            'group' => 'nodes',
            'data' => array(
                'id' => self::SOURCES_ID,
                'name' => 'Sources'
            )
        ),
        array(
            'group' => 'nodes',
            'data' => array(
                'id' => self::DESTINATIONS_ID,
                'name' => 'Destinations'
            )
        )
    );

    /**
     * This function is responsible for the setting up of any routes that this
     * ControllerProvider is going to be managing. It *must* be overridden by
     * a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $class = get_class($this);

        $controller->get("$root/pipelines/actions", "$class::getActionsForPipelines");
        $controller->post("$root/pipelines/actions", "$class::getActionsForPipelines");

        $controller->get("$root/pipelines/{pipeline}/actions", "$class::getActionsForPipeline");
        $controller->get("$root/pipelines/{pipeline}/endpoints", "$class::getEndpointsForPipeline");

        $controller->get("$root/pipelines", "$class::getPipelines");
        $controller->post("$root/pipelines", "$class::getPipelines");

        $controller->get("$root/files", "$class::getFileNames");
        $controller->post("$root/files", "$class::getFileNames");

        $controller->get("$root/endpoints", "$class::getDataEndpoints");

        $controller->get("$root/search", "$class::search");
        $controller->post("$root/search", "$class::search");

        $controller->get("$root/graph/pipelines/{pipeline}", "$class::getPipelinesForGraph");
        $controller->post("$root/graph/pipelines/{pipeline}", "$class::getPipelinesForGraph");

        $controller->get("$root/graph/pipelines/{pipeline}/actions/{action}", "$class::getPipelineActionForGraph");
        $controller->post("$root/graph/pipelines/{pipeline}/actions/{action}", "$class::getPipelineActionForGraph");
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws Exception
     */
    public function getPipelines(Request $request, Application $app)
    {
        $etlConfig = $this->retrieveETLConfig();

        $pipelineNames = $etlConfig->getSectionNames();
        sort($pipelineNames);

        $results = array();
        foreach ($pipelineNames as $pipelineName) {
            $pipeline = array(
                'name' => $pipelineName
            );

            $actions = $etlConfig->getConfiguredActionNames($pipelineName);
            $pipeline['children'] = array();

            foreach ($actions as $actionName) {
                $action = array(
                    'name' => $actionName
                );

                $options = $etlConfig->getActionOptions($actionName, $pipelineName);
                $action['children'] = array();

                foreach ($options as $key => $value) {
                    $translated = $value;
                    if (in_array($key, array('source', 'destination', 'utility'))) {
                        $endpoint = $etlConfig->getDataEndpoint($value);
                        if ($endpoint instanceof iRdbmsEndpoint) {
                            $translated = $endpoint->getSchema();
                        } elseif ($endpoint instanceof File) {
                            $translated = realpath($endpoint->getPath());
                        } else {
                            $translated = json_encode($translated);
                        }
                    } elseif ($key === 'definition_file' && isset($value)) {
                        $definitionPath = $options->paths->action_definition_dir . "/$value";
                        $definition = Configuration::factory($definitionPath);
                        $translated = $this->convertForTreeGrid(Json::loadFile($definitionPath));
                    } elseif (is_object($value) || is_array($value)) {
                        $translated = $this->convertForTreeGrid($value);
                    }

                    $option = array(
                        'name' => $key
                    );

                    if (is_array($translated)) {
                        $option['children'] = $translated;
                    } else {
                        $option['value'] = $translated;
                        $option['leaf'] = true;
                    }

                    $action['children'][] = $option;
                }
                $pipeline['children'][] = $action;
            }

            $results[] = $pipeline;
        }


        return $app->json($results);
    }

    public function getFileNames(Request $request, Application $app)
    {
        $this->authorize($request, array(ROLE_ID_MANAGER));

        $query = $request->get('query');

        // Make sure that if they send an empty string then we still set query to null.
        $query = !empty($query) ? $query : null;

        $etlDir = implode(DIRECTORY_SEPARATOR, array(CONFIG_DIR, 'etl'));

        $results = $this->retrieveFilenames($etlDir, $query);

        return $app->json(
            array(
                'results' => $results
            )
        );
    }

    public function getDataEndpoints(Request $request, Application $app)
    {
        $results = array();

        $etlConfig = $this->retrieveETLConfig();

        $pipelineNames = $etlConfig->getSectionNames();
        sort($pipelineNames);

        foreach($pipelineNames as $pipelineName) {
            $results[$pipelineName] = $this->getPipelineEndpoints($pipelineName);
        }

        return $app->json(
            array(
                'results' => $results
            )
        );
    }

    /**
     * @param array $scriptOptions
     * @return \Configuration\Configuration
     * @throws Exception
     */
    public function retrieveETLConfig(array $scriptOptions = array(
        'config-file' => DEFAULT_ETL_CONFIG_FILE,
        'base-dir' => null,
        'default_module_name' => DEFAULT_MODULE_NAME)
    )
    {
        $etlConfig = EtlConfiguration::factory(
            $scriptOptions['config-file'],
            $scriptOptions['base-dir'],
            null,
            array(
                'default_module_name' => $scriptOptions['default_module_name']
            )
        );
        Utilities::setEtlConfig($etlConfig);
        return $etlConfig;
    }

    protected function convertForTreeGrid($source)
    {
        $results = array();

        $keys = array();
        $isArray = is_array($source);
        $isObject = is_object($source);

        if ($isArray) {
            $keys = array_keys($source);
        } elseif ($isObject) {
            $keys = array_keys(get_object_vars($source));
        }

        foreach ($keys as $key) {
            $value = null;
            if ($isArray) {
                $value = $source[$key];
            } elseif ($isObject) {
                $value = $source->$key;
            }

            $item = array(
                'name' => "$key"
            );

            if (is_object($value) || is_array($value)) {
                $item['children'] = $this->convertForTreeGrid($value);
            } else {
                $item['value'] = $value;
                $item['leaf'] = true;
            }

            $results[] = $item;
        }

        return $results;
    }

    private function retrieveFilenames($baseDir, $query)
    {
        return $this->retrieveFiles($baseDir, function ($filePath, &$carry) use ($query, $baseDir) {
            $startPos = strpos($filePath, $baseDir) !== false ? strlen($baseDir) + 1 : 0;
            $fileName = substr($filePath, $startPos);

            if ($query === null || preg_match(".*$query.*", $fileName) !== false) {
                $carry[] = array('name' => $fileName);
            }
        });
    }


    private function retrieveFiles($baseDir, callable $handler)
    {
        $results = array();

        $jsonFiles = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir)
            ),
            '/^.+\.json$/',
            RecursiveRegexIterator::GET_MATCH
        );

        foreach ($jsonFiles as $jsonFile) {
            $rawName = $jsonFile[0];
            $handler($rawName, $results);
        }
        return $results;
    }

    public function getActionsForPipelines(Request $request, Application $app)
    {
        $configOptions = array('default_module_name' => 'xdmod');
        $configOptions['config_variables'] = array(
            'CLOUD_EVENT_LOG_DIRECTORY' => 'cloud_openstack/events',
            'CLOUD_RESOURCE_SPECS_DIRECTORY' => 'cloud_openstack/resource_specs'
        );

        $etlConfig = EtlConfiguration::factory(
            CONFIG_DIR . '/etl/etl.json',
            null,
            null,
            $configOptions
        );
        $pipelineNames = $etlConfig->getSectionNames();
        sort($pipelineNames);

        $results = array();
        foreach ($pipelineNames as $pipelineName) {
            try {
                $results[$pipelineName] = $this->getPipelineActions($pipelineName);
            } catch (\Exception $e){

            }

        }

        return $app->json($this->convertForTreeGrid($results));
    }

    private function getAllActionsAndEndpoints()
    {
        $configOptions = array('default_module_name' => 'xdmod');
        $configOptions['config_variables'] = array(
            'CLOUD_EVENT_LOG_DIRECTORY' => 'cloud_openstack/events',
            'CLOUD_RESOURCE_SPECS_DIRECTORY' => 'cloud_openstack/resource_specs'
        );

        $etlConfig = EtlConfiguration::factory(
            CONFIG_DIR . '/etl/etl.json',
            null,
            null,
            $configOptions
        );
        $pipelineNames = $etlConfig->getSectionNames();
        sort($pipelineNames);

        $results = array();
        foreach ($pipelineNames as $pipelineName) {
            try {
                $results[$pipelineName] =  $this->getPipelineActionsAndEndpoints($pipelineName);
            } catch (\Exception $e) {

            }

        }
        return $results;
    }

    public function getActionsForPipeline(Request $request, Application $app, $pipeline)
    {
        return $app->json(
            $this->getPipelineActions($pipeline)
        );
    }

    public function getEndpointsForPipeline(Request $request, Application $app, $pipeline)
    {
        $flattened = $request->get('flatten', false);

        $endpoints = $this->getPipelineEndpoints($pipeline);

        return $app->json(
            $flattened ? $this->flattenEndpoints($endpoints) : $endpoints
        );
    }

    public function search(Request $request, Application $app)
    {
        $term = $this->getStringParam($request, 'term', true);

        $results = array();

        $actionsAndEndPoints = $this->getAllActionsAndEndpoints();
        foreach($actionsAndEndPoints as $pipeline => $value) {
            list($actions, $endpoints) = $value;
            $actionResults = $this->recursiveSearch($actions, $term);
            $endpointResults = $this->recursiveSearch($endpoints, $term);
            if (!empty($actionResults)) {
                $results[$pipeline]['actions'] = $actionResults;
            }
            if (!empty($endpointResults)) {
                $results[$pipeline]['endpoints'] = $endpointResults;
            }
        }

        return $app->json($results);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $pipeline
     * @return JsonResponse
     */
    public function getPipelinesForGraph(Request $request, Application $app, $pipeline)
    {
        return $app->json(
            array(
                'success' => true,
                'message' => "Retrieved $pipeline",
                'data' => $this->preparePipelineForGraph($pipeline)
            )
        );
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $pipeline
     * @param string $action
     * @return JsonResponse
     */
    public function getPipelineActionForGraph(Request $request, Application $app, $pipeline, $action)
    {
        return $app->json(
            array(
                'success' => true,
                'message' => "Retrieved $action of $pipeline",
                'data' => $this->preparePipelineForGraph($pipeline, $action)
            )
        );
    }

    protected function preparePipelineForGraph($pipeline, $action = null)
    {
        /* Parent Group nodes should be formatted as such:
         *   { group: 'nodes', data: { id: '<unique_id>', name: '<name>'} }
         * Child nodes should be formatted as such:
         *   { group: 'nodes', data: { id: '<unique_id>', name: '<name>', parent: '<parent_id>' } }
         */

        /* Edge nodes should be formatted as such:
         *   { group: 'edges', data: { id: '<unique_edge_id>', source: '<source_node_id>', target: '<target_node_id>', name: '<optional>'} }
         */
        $results = self::$defaultNodes;

        $actions = $this->getPipelineActions($pipeline);
        $results = array_merge($results, $this->getTargetTypeNodes($actions));

        /**
         * Example Hierarchy:
         * - Pipeline: xdmod.jobs-cloud-import-users-openstack
         *   - Sources: <Group>
         *     - MySQL: <Type>
         *       - Schema: <Sub Type>
         *         - Table:
         *     - JSON File: <Type>
         *       - Source 1,2,3..N: File name
         *   - Actions: <Group>
         *     - Action 1,2,3..N: Action Name
         *   - Destinations: <Group>
         *     - MySQL: <Type>
         *       - Schema: <Sub Type>
         *         - Table:
         */
        $results[] = array(
            'group' => 'nodes',
            'data' => array(
                'id' => $pipeline,
                'name' => $pipeline,
            )
        );

        foreach($actions as $key => $actionData) {

            $actionName = $actionData['name'];
            if (isset($action) && $action !== $actionName) {
                break;
            }

            $action = array(
                'group' => 'nodes',
                'data' => array(
                    'id' => $actionName,
                    'name' => $key,
                    'parent' => $pipeline
                )
            );
            $source = array(
                'group' => 'nodes',
                'data' => $this->getTargetData($actionData['source'], 'source')
            );
            $destination = array(
                'group' => 'nodes',
                'data' => $this->getTargetData($actionData['destination'], 'destination')
            );

            $results[] = $action;
            $results[] = $source;
            $results[] = $destination;

            $sourceId = $source['data']['id'];
            $actionId = $action['data']['id'];
            $destinationId = $destination['data']['id'];

            $sourceChildren = $this->getTargetChildren($actionData['source'], $sourceId);
            $destinationChildren = $this->getTargetChildren($actionData['destination'], $destinationId);

            $results = array_merge($results, $sourceChildren);
            $results = array_merge($results, $destinationChildren);

            foreach($sourceChildren as $sourceChild) {
                $sourceChildId = $sourceChild['data']['id'];
                $results[] = array(
                    'group' => 'edges',
                    'data' => array(
                        'id' => sprintf("%s-%s", $sourceChildId, $actionId),
                        'source' => $sourceChildId,
                        'target' => $actionId
                    )
                );
            }

            foreach($destinationChildren as $destinationChild) {
                $destinationChildId = $destinationChild['data']['id'];
                $results[] = array(
                    'group' => 'edges',
                    'data' => array(
                        'id' => sprintf("%s-%s", $actionId, $destinationChildId),
                        'source' => $actionId,
                        'target' => $destinationChildId
                    )
                );
            }
        }

        return $results;
    }

    private function getTargetData($target, $idPrefix = null)
    {
        return array(
            'id' => $idPrefix !== null ? sprintf("$idPrefix-%s", $target['key']) : $target['key'],
            'name' => $this->getFirstProperty($target, array('schema', 'name')),
            'parent' => $idPrefix !== null ? sprintf("$idPrefix-%s", $target['type']) : $target['type']
        );
    }

    private function getTargetChildren($target, $parentId)
    {
        $results = array();
        $properties = array('tables', 'definition_file_list', 'sql_file_list', 'definition_file');
        $key = null;
        foreach($properties as $property) {
            if (array_key_exists($property, $target)) {
                $key = $property;
                break;
            }
        }

        if ($key === null) {
            return $results;
        }

        foreach($target[$key] as $child) {
            $results[] = array(
                'group' => 'nodes',
                'data' => array(
                    'id' => "$parentId-$child",
                    'name' => $child,
                    'parent' => $parentId
                )
            );
        }

        return $results;
    }

    /**
     *
     * @param array $source
     * @param array $properties
     * @param mixed|null $default
     * @return mixed
     */
    function getFirstProperty($source, $properties, $default = null)
    {
        foreach($properties as $property) {
            if (array_key_exists($property, $source)) {
                return $source[$property];
            }
        }
        return $default;
    }

    private function getTargetTypeNodes(array $actions)
    {
        $targetDefinitions = array(
            'mysql' => 'MySQL',
            'jsonfile' => 'JSON File'
        );

        $results = array();
        $sourceTypes = array();
        $destinationTypes = array();

        foreach($actions as $actionName => $action) {
            $source = $action['source'];
            $destination = $action['destination'];
            if (!in_array($source['type'], $sourceTypes)) {
                $sourceTypes[] = $source['type'];
            }
            if (!in_array($destination['type'], $destinationTypes)) {
                $destinationTypes[] = $destination['type'];
            }
        }

        foreach($sourceTypes as $sourceType) {
            $results[] = array(
                'group' => 'nodes',
                'data' => array(
                    'id' => sprintf('source-%s', $sourceType),
                    'name' => $targetDefinitions[$sourceType],
                    'parent' => self::SOURCES_ID
                )
            );
        }

        foreach($destinationTypes as $destinationType) {
            $results[] = array(
                'group' => 'nodes',
                'data' => array(
                    'id' => sprintf('destination-%s', $destinationType),
                    'name' => $targetDefinitions[$destinationType],
                    'parent' => self::DESTINATIONS_ID
                )
            );
        }
        return $results;
    }

    private function getPipelineEndpoints($pipeline)
    {
        $results = array();
        list($actions, $endpoints) = $this->getPipelineActionsAndEndpoints($pipeline);

        foreach($endpoints as $key => $value) {
            if (!isset($results[$value['type']])) {
                $results[$value['type']] = array(
                    'name'=> $value['type'],
                    'endpoints' => array()
                );
            }
            $results[$value['type']]['endpoints'][] = $value;
        }
        return array_values($results);
    }

    private function getPipelineActions($pipeline)
    {
        list($actions, $endpoints) = $this->getPipelineActionsAndEndpoints($pipeline);

        return $actions;
    }

    private function getPipelineActionsAndEndpoints($pipeline)
    {
        $configOptions = array('default_module_name' => 'xdmod');
        $configOptions['config_variables'] = array(
            'CLOUD_EVENT_LOG_DIRECTORY' => 'cloud_openstack/events',
            'community-user' => 'community_user'
        );

        $etlConfig = EtlConfiguration::factory(
            CONFIG_DIR . '/etl/etl.json',
            null,
            null,
            $configOptions
        );

        if (!$etlConfig->getSectionData($pipeline)) {
            throw new NotFoundHttpException("Requested pipeline [$pipeline] does not exist.");
        }

        Utilities::setEtlConfig($etlConfig);

        $scriptOptions = array_merge(
            array(
                'default-module-name' => 'xdmod',
                'process-sections' => array($pipeline)
            )
        );
        $overseerOptions = new EtlOverseerOptions($scriptOptions);

        $utilitySchema = $etlConfig->getGlobalEndpoint('utility')->getSchema();
        $overseerOptions->setResourceCodeToIdMapSql(sprintf("SELECT id, code from %s.resourcefact", $utilitySchema));

        $overseer = new EtlOverseer($overseerOptions);

        $actions = $overseer->verifySections($etlConfig, array($pipeline));

        return $this->parseActions(json_decode(json_encode($actions)), $etlConfig);
    }

    /**
     * @param array $pipelineActions
     * @param EtlConfiguration $etlConfig
     * @return array
     */
    private function parseActions($pipelineActions, $etlConfig)
    {
        $endpoints = array();
        $results = array();
        foreach ($pipelineActions as $pipelineName => $actions) {
            $pipelineConfigs = array_reduce(
                $etlConfig->$pipelineName,
                function ($carry, $item) {
                    $carry[$item->name] = $item;
                    return $carry;
                },
                array()
            );

            foreach ($actions as $actionName => $action) {
                $actionConfig = $pipelineConfigs[$actionName];
                $configClass = $actionConfig->class;

                $sourceEndpoint = $this->getEndpointData($actionConfig->endpoints->source);
                $destinationEndpoint = $this->getEndPointData($actionConfig->endpoints->destination);

                $source = json_decode(json_encode($sourceEndpoint), true);
                $destination = json_decode(json_encode($destinationEndpoint), true);

                switch ($configClass) {
                    case "DatabaseIngestor":
                    case "JobListAggregator":
                    case "SimpleAggregator":
                    case "ExplodeTransformIngestor":
                        $parsed = $action->parsed_definition_file;

                        $sourceTables = array_reduce(
                            $parsed->source_query->joins,
                            function ($carry, $item) {
                                $carry[] = $item->name;
                                return $carry;
                            },
                            array()
                        );

                        $source['tables'] = $sourceTables;
                        $source['records'] = $parsed->source_query->records;

                        $destination['tables'] = array_keys(get_object_vars($action->etl_destination_table_list));
                        $destination['field_mappings'] = json_decode(json_encode($action->destination_field_mappings), true);
                        break;
                    case "ManageTables":
                        $actionOptions = $etlConfig->getActionOptions($actionName, $pipelineName);
                        $source['definition_file_list'] = $actionOptions->definition_file_list;

                        $destinationTables = array_keys(get_object_vars($action->etl_destination_table_list));
                        $destination['tables'] = $destinationTables;
                        break;
                    case "StructuredFileIngestor":
                        $parsed = $action->parsed_definition_file;
                        $tables = array();

                        $properties = array(
                            'destination_record_map' => function ($source) {
                                return array_keys(get_object_vars($source->destination_record_map));
                            },
                            'table_definition' => function ($source) {
                                if (is_array($source) && count($source) > 0) {
                                    $table = $source[0];
                                    return array($table->name);
                                } elseif (is_object($source)) {
                                    return array($source->name);
                                }
                                /*if (count($source) > 0) {

                                }*/
                                #var_dump($source);
                                return array();
                            }
                        );

                        foreach($properties as $property => $propertyExtractor) {
                            if (property_exists($parsed, $property)) {
                                $tables = $propertyExtractor($parsed->$property);
                                break;
                            }
                        }

                        $destination['tables'] = $tables;
                        break;
                    case "ExecuteSql":
                        $source['sql_file_list'] = $action->options->options->sql_file_list;
                        break;
                    default:
                        break;
                }

                if (!array_key_exists($source['key'], $endpoints)) {
                    if (isset($source['tables']) && count($source['tables']) > 1) {

                    } else {
                        $endpoints[$source['key']] = $source;
                    }

                }

                if (!array_key_exists($destination['key'], $endpoints)) {
                    $endpoints[$destination['key']] = $destination;
                }

                $name = substr($actionName, strlen($pipelineName) + 1);
                $results[$name] = array(
                    'name' => $actionName,
                    'class' => $configClass,
                    'source' => $source,
                    'destination' => $destination
                );
            }
        }

        return array($results, $endpoints);
    }

    private function recursiveSearch(array $source, $term, array $breadcrumbs = array())
    {
        $results = array();

        foreach($source as $key => $value) {
            if (is_array($value)) {
                $breadcrumbs[] = $key;
                return $this->recursiveSearch($value, $term, $breadcrumbs);
            } else if(strpos((string)$value, $term) !== false) {
                $results[implode('.', $breadcrumbs)] = $value;
            }
        }

        return $results;
    }

    private function getEndpointData($endpoint)
    {
        $result = new \stdClass();

        $result->name = $endpoint->name;
        $result->type = $endpoint->type;
        $result->key = $endpoint->key;

        switch ($endpoint->type) {
            case "directoryscanner":
                if (strpos($endpoint->path, DIRECTORY_SEPARATOR) != 0 && !empty($endpoint->paths->data_dir)) {
                    $path = implode(DIRECTORY_SEPARATOR, array($endpoint->paths->data_dir, $endpoint->path));
                } else {
                    $path = $endpoint->path;
                }

                $result->path = $path;
                $result->handlerType = $endpoint->handler->type;
                $result->directoryPattern = $endpoint->directory_pattern;
                $result->filePattern = $endpoint->file_pattern;
                $result->recursionDepth = $endpoint->recursion_depth;
                break;
            case "configurationfile":
            case "file":
            case "jsonconfigfile":
            case "jsonfile":
                if (strpos($endpoint->path, DIRECTORY_SEPARATOR) != 0 && !empty($endpoint->paths->data_dir)) {
                    $path = implode(DIRECTORY_SEPARATOR, array($endpoint->paths->data_dir, $endpoint->path));
                } else {
                    $path = $endpoint->path;
                }
                $result->path = realpath($path);
                break;
            case "mysql":
            case "oracle":
            case "postgres":
                $result->schema = $endpoint->schema;
                break;
            case "rest":
                $result->baseUrl = $endpoint->baseUrl;
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * @param array $typedEndpoints
     *
     * @return array
     */
    private function flattenEndpoints($typedEndpoints)
    {
        $results = array();

        foreach($typedEndpoints as $typedEndpoint) {
            if (in_array($typedEndpoint['name'], array('mysql', 'oracle', 'postgres'))) {
                $tableEndpoints = array();
                $endpoints = $typedEndpoint['endpoints'];
                foreach($endpoints as $endpoint) {
                    $endpointObject =new ArrayObject($endpoint) ;
                    $tableEndpoint = $endpointObject->getArrayCopy();
                    unset($tableEndpoint['tables']);
                    foreach($endpoint['tables'] as $table) {
                        $tableEndpoint['table'] = $table;
                        $tableEndpoints[$table] = $tableEndpoint;
                    }
                }
                $typedEndpoint['endpoints'] = array_values($tableEndpoints);
            }
            $results[] = $typedEndpoint;
        }

        return $results;
    }

}
