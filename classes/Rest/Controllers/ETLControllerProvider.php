<?php

namespace Rest\Controllers;

use CCR\Json;
use ETL\Configuration\EtlConfiguration;
use ETL\DataEndpoint\File;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\DataEndpoint\JsonFile;
use ETL\Utilities;
use Exception;
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
                'name' => $pipelineName,
                'actions' => array()
            );

            $actions = $etlConfig->getConfiguredActionNames($pipelineName);
            foreach ($actions as $actionName) {
                $action = array(
                    'name' => $actionName
                );

                $options = $etlConfig->getActionOptions($actionName, $pipelineName);

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
                        $translated = Json::loadFile($definitionPath);
                    }

                    $action[$key] = $translated;
                }
                $pipeline['actions'][] = $action;
            }

            $results[] = $pipeline;
        }

        return $app->json($results);
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
}
