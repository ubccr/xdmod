<?php

namespace Rest\Controllers;

use CCR\Json;
use ETL\Configuration\EtlConfiguration;
use ETL\DataEndpoint\File;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\Utilities;
use Exception;
use http\Exception\InvalidArgumentException;
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

        $pipeline = $this->getStringParam($request, 'pipeline');
        $action = $this->getStringParam($request, 'action');

        $results = array();
        $etlConfig = $this->retrieveETLConfig();

        if (empty($pipeline) && !empty($action)){
            throw new InvalidArgumentException("");
        } elseif (empty($pipeline) && empty($action)) {
            $pipelineNames = $etlConfig->getSectionNames();
            sort($pipelineNames);
            $results = array_reduce(
                $pipelineNames,
                function ($carry, $item) {
                    $carry[] = array(
                        'text' => $item,
                        'dtype' => 'pipeline',
                        'pipeline' => $item,
                    );
                    return $carry;
                },
                $results
            );
        } elseif (!empty($pipeline)) {
            $actions = $etlConfig->getConfiguredActionNames($pipeline);
            sort($actions);
            $results = array_reduce(
                $actions,
                function ($carry, $item) {
                    $carry[] = array(
                        'text' => $item,
                        'dtype' => 'action',
                        'action'=> $item,
                        'leaf' => true
                    );
                    return $carry;
                },
                $results
            );
        } else {
            $action = array(
                'text' => $action
            );
            $options = $etlConfig->getActionOptions($action, $pipeline);

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
        }

        return $app->json(
            array(
                'success' => true,
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
}
