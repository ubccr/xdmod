<?php

namespace Rest\Controllers;

use CCR\Json;
use ETL\Configuration\EtlConfiguration;
use ETL\DataEndpoint\File;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\Utilities;
use Exception;
use RecursiveRegexIterator;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ETLControllerProvider extends BaseControllerProvider
{

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

        $controller->get("$root/pipelines", "$class::getPipelines");
        $controller->post("$root/pipelines", "$class::getPipelines");

        $controller->get("$root/files", "$class::getFileNames");
        $controller->post("$root/files", "$class::getFileNames");
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     * @throws Exception
     */
    public function getPipelines(Request $request, Application $app)
    {
        $this->authorize($request, array(ROLE_ID_MANAGER));

        $etlConfig = $this->retrieveETLConfig();

        $pipelineNames = $etlConfig->getSectionNames();
        sort($pipelineNames);

        $results = array();
        foreach ($pipelineNames as $pipelineName) {
            $pipeline = array(
                'name' => $pipelineName
            );

            $actions = $etlConfig->getConfiguredActionNames($pipelineName);
            if (!empty($actions)) {
                $pipeline['children'] = array();
            }

            foreach ($actions as $actionName) {
                $action = array(
                    'name' => $actionName
                );

                $options = $etlConfig->getActionOptions($actionName, $pipelineName);
                if (!empty($options)) {
                    $action['children'] = array();
                }

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
        $query = $request->get('query');

        // Make sure that if they send an empty string then we still set query to null.
        $query = !empty($query) ? $query : null;

        $etlDir = implode(DIRECTORY_SEPARATOR, array(CONFIG_DIR, 'etl'));

        $jsonFiles = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($etlDir)
            ),
            '/^.+\.json$/',
            RecursiveRegexIterator::GET_MATCH
        );

        $results = array();
        foreach ($jsonFiles as $jsonFile) {
            $rawName = $jsonFile[0];
            $startPos = strpos($rawName, $etlDir) !== false ? strlen($etlDir) + 1 : 0;
            $fileName = substr($rawName, $startPos);
            if ($query === null ||
                ($query !== null && preg_match(".*$query.*", $fileName) !== false)
            ) {
                $results[] = array('name' => $fileName);
            }
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
            $keys = get_object_vars($source);
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
}
