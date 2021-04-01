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

                        $destination['tables'] = array_keys(get_object_vars($parsed->destination_record_map));
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
