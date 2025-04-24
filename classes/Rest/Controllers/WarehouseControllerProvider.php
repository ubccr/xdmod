<?php

namespace Rest\Controllers;

use CCR\DB;
use CCR\Log;
use Configuration\XdmodConfiguration;
use DataWarehouse\Data\BatchDataset;
use DataWarehouse\Export\RealmManager;
use DataWarehouse\Query\Exceptions\AccessDeniedException;
use Exception;
use Models\Services\Acls;
use Models\Services\Parameters;
use Models\Services\Realms;
use Models\Services\Tokens;
use PDO;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Silex\ControllerCollection;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use XDUser;
use DataWarehouse\Access\MetricExplorer;
use DataWarehouse\Access\Usage;
use stdClass;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 **/
class WarehouseControllerProvider extends BaseControllerProvider
{


    /**
     * The maximum number of search records that are allowed.
     *
     * @var int
     */
    const _MAX_RECORDS = 50;

    /**
     * The default error message that is provided when a request cannot be processed.
     *
     * @var string
     */
    const _DEFAULT_ERROR_MSG = 'Unable to execute the requested operation for the provided user.';

    /**
     * The identifier that will be used to 'bind' the [GET] /search/history
     * route. This will allow the use of the url generator service
     * coupled with this name to arrive back at a url.
     *
     * @var string
     */
    const _GET_SEARCH_HISTORY = 'warehouse_get_search_history';

    /**
     * The identifier that will be used to 'bind' the [GET]
     * /search/history/{id} route. This will allow the use of the url generator
     * service coupled with this name to arrive back at a url.
     *
     * @var string
     */
    const _GET_SEARCH_HISTORY_BY_ID = 'warehouse_get_search_history_by_id';

    /**
     * The identifier that is used to store the search history data in the user
     * profile.
     *
     * @var string
     */
    const _HISTORY_STORE = 'searchhistory';

    /**
     * The identifier that's used when retrieving the current value of the
     * route parameter 'api_version'.
     *
     * @var string
     */
    const _API_VERSION = 'api_version';

    /**
     * The default value that will be used if one cannot be retrieved from the
     * Silex Request object.
     *
     * @var int
     */
    const _DEFAULT_API_VERSION = 0;

    /**
     * The default value for the query group parameter in various calls.
     *
     * @var string
     */
    const _DEFAULT_QUERY_GROUP = 'tg_usage';

    /**
     * The types of nodes that this ControllerProvider understands how to deal with.
     *
     * @var array
     */
    private $_supported_types = array(
        \DataWarehouse\Query\RawQueryTypes::ACCOUNTING =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::ACCOUNTING,
                "dtype" => "infoid",
                "text" => "Accounting data",
                "url" => "/rest/v1.0/warehouse/search/jobs/accounting",
                "documentation" => "Shows information about the job that was obtained from the resource manager.
                                  This includes timing information such as the start and end time of the job as
                                  well as administrative information such as the user that submitted the job and
                                  the account that was charged.",
                "type" => "keyvaluedata",
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT,
                "dtype" => "infoid",
                "text" => "Job script",
                "url" => "/rest/v1.0/warehouse/search/jobs/jobscript",
                "documentation" => "Shows the job batch script that was passed to the resource manager when the
                                    job was submitted. The script is displayed verbatim.",
                "type" => "utf8-text",
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::EXECUTABLE =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::EXECUTABLE,
                "dtype" => "infoid",
                "text" => "Executable information",
                "url" => "/rest/v1.0/warehouse/search/jobs/executable",
                "documentation" => "Shows information about the processes that were run on the compute nodes during
                                    the job. This information includes the names of the various processes and may
                                    contain information about the linked libraries, loaded modules and process
                                    environment.",
                "type" => "nested",
                "leaf" => true),
        \DataWarehouse\Query\RawQueryTypes::PEERS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::PEERS,
                "dtype" => "infoid",
                "text" => "Peers",
                'url' => '/rest/v1.0/warehouse/search/jobs/peers',
                'documentation' => 'Shows the list of other HPC jobs that ran concurrently using the same shared hardware resources.',
                'type' => 'ganttchart',
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS,
                "dtype" => "infoid",
                "text" => "Summary metrics",
                "url" => "/rest/v1.0/warehouse/search/jobs/metrics",
                "documentation" => "shows a table with the performance metrics collected during
                                    the job. These are typically average values over the job. The
                                    label for each row has a tooltip that describes the metric. The
                                    data are grouped into the following categories:
                                    <ul>
                                        <li>CPU Statistics: information about the cores on which the job was
                                         assigned, such as CPU usage, FLOPs, CPI</li>
                                        <li>File I/O Statistics: information about the data read from and
                                         written to block devices and file system mount points.  </li>
                                        <li>Memory Statistics: information about the memory usage on the nodes
                                         on which the job ran.</li>
                                        <li>Network I/O Statistics: information about the data transmitted and
                                         received over the network devices.</li>
                                    </ul>
                ",
                "type" => "metrics",
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS,
                "dtype" => "infoid",
                "text" => "Detailed metrics",
                "url" => "/rest/v1.0/warehouse/search/jobs/detailedmetrics",
                "documentation" => "shows the data generated by the job summarization software. Please
                                    consult the relevant job summarization software documentation for details
                                    about these metrics.",
                "type" => "detailedmetrics",
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::ANALYTICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::ANALYTICS,
                "dtype" => "infoid",
                "text" => "Job analytics",
                "url" => "/rest/v1.0/warehouse/search/jobs/analytics",
                "documentation" => "Click the help icon on each plot to show the description of the analytic",
                "type" => "analytics",
                "hidden" => true,
                "leaf" => true
            ),
        \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS,
                "dtype" => "infoid",
                "text" => "Timeseries",
                "leaf" => false
            ),
        \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE,
                "dtype" => "infoid",
                "text" => "VM State/Events",
                "documentation" => "Show the lifecycle of a VM. Green signifies when a VM is active and red signifies when a VM is stopped.",
                "url" => "/rest/v1.0/warehouse/search/cloud/vmstate",
                "type" => "vmstate",
                "leaf" => true
            )
    );

    /**
     * This function is responsible for the setting up of any routes that this
     * ControllerProvider is going to be managing. It *must* be overridden by
     * a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;

        $current = get_class($this);
        $conversions = '\Rest\Utilities\Conversions';
        // Search history routes

        $controller
            ->get("$root/search/history", "$current::searchHistory");

        $controller
            ->post("$root/search/history", "$current::createHistory");

        $controller
            ->get("$root/search/history/{id}", "$current::getHistoryById")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller
            ->post("$root/search/history/{id}", "$current::updateHistory")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller
            ->put("$root/search/history/{id}", "$current::updateHistory")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller
            ->delete("$root/search/history/{id}", "$current::deleteHistory")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller
            ->delete("$root/search/history", "$current::deleteAllHistory");

        // Job search routes

        $controller
            ->get("$root/search/jobs", "$current::searchJobs");

        $controller
            ->get("$root/search/jobs/{action}", "$current::searchJobsByAction")
            ->assert('action', '(\w|_|-])+')
            ->convert('action', "$conversions::toString");
        $controller
            ->post("$root/search/jobs/{action}", "$current::searchJobsByAction")
            ->assert('action', '(\w|_|-])+')
            ->convert('action', "$conversions::toString");

        $controller
            ->get("$root/search/cloud/{action}", "$current::searchJobsByAction")
            ->assert('action', '(\w|_|-])+')
            ->convert('action', "$conversions::toString");
        $controller
            ->post("$root/search/cloud/{action}", "$current::searchJobsByAction")
            ->assert('action', '(\w|_|-])+')
            ->convert('action', "$conversions::toString");

        // Metrics routes
        $controller
            ->get("$root/realms", "$current::getRealms");

        $controller
            ->get("$root/dimensions", "$current::getDimensions");

        $controller
            ->get("$root/dimensions/{dimension}", "$current::getDimensionValues")
            ->assert('dimension', '(\w|_|-])+')
            ->convert('dimension', "$conversions::toString");

        $controller
            ->get("$root/dimensions/{dimensionId}/name", "$current::getDimensionName")
            ->assert('dimensionId', '(\w|_|-])+')
            ->convert('dimensionId', "$conversions::toString");

        $controller
            ->get("$root/dimensions/{dimensionId}/values/{valueId}/name", "$current::getDimensionValueName")
            ->assert('dimension', '(\w|_|-])+')
            ->convert('dimension', "$conversions::toString");

        $controller
            ->get("$root/quick_filters", "$current::getQuickFilters");

        $controller
            ->get("$root/aggregation_units", "$current::getAggregationUnits");

        $controller
            ->get("$root/datasets/types", "$current::getDatasetTypes");

        $controller
            ->get("$root/datasets/output_formats", "$current::getDatasetOutputFormats");

        $controller
            ->get("$root/datasets", "$current::getDatasets");

        $controller->get("$root/aggregatedata", "$current::getAggregateData");

        $controller
            ->get("$root/plots/formats/output", "$current::getPlotOutputFormats");

        $controller
            ->get("$root/plots/types/display", "$current::getPlotDisplayTypes");

        $controller
            ->get("$root/plots/types/combine", "$current::getPlotCombineTypes");

        $controller
            ->get("$root/plots", "$current::getPlots");

        $controller
            ->get("$root/raw-data", "$current::getRawData");
    }

    /**
     * Retrieves the Search History for the user making the request.
     * If the user was authenticated but no user object could be obtained
     * then a 401 response will be returned. Else, the 'history' value of
     * the users profile will be retrieved and returned along with a count
     * of the number of history records that were retrieved.
     * Example Results:
     *
     * {
     *   success: true,
     *   action: 'searchHistory',
     *   data: [
     *     {
     *       ... history record data ...
     *     },
     *     ...
     *   ],
     *   total: ... number of records in 'data' ...
     * }
     *
     * @param Request $request
     * @param Application $app
     * @return array in the format array( boolean success, string message)
     * @throws AccessDeniedException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function searchHistory(Request $request, Application $app)
    {

        $action = 'searchHistory';
        $user = $this->authorize($request);

        $nodeId = $this->getIntParam($request, 'nodeid');
        $tsId = $this->getStringParam($request, 'tsid');
        $infoId = $this->getIntParam($request, 'infoid');
        $jobId = $this->getIntParam($request, 'jobid');
        $recordId = $this->getIntParam($request, 'recordid');
        $realm = $this->getStringParam($request, 'realm');
        $title = $this->getStringParam($request, 'title');

        if ($nodeId !== null && $tsId !== null && $infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobNodeTimeSeriesRequest($app, $user, $realm, $jobId, $tsId, $nodeId, $infoId, $action);
        } elseif ($tsId !== null && $infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobTimeSeriesRequest($app, $user, $realm, $jobId, $tsId, $infoId, $action);
        } elseif ($infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobRequest($app, $user, $realm, $jobId, $infoId, $action);
        } elseif ($jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobByJobId($app, $user, $realm, $jobId, $action);
        } elseif ($recordId !== null && $realm !== null) {
            $result = $this->getHistoryById($request, $app, $recordId);
        } elseif ($realm !== null && $title !== null) {
            $result = $this->getHistoryByTitle($request, $app, $realm, $title);
        } elseif ($realm !== null) {
            $result = $this->processHistoryRequest($app, $user, $realm, $action);
        } else {
            $result = $this->processHistoryDefaultRealmRequest($app, $user, $action);
        }

        return $result;
    }

    /**
     * Attempts to retrieve the Search History record identified by the
     * provided 'id'
     *
     * Example Response:
     * {
     *   'success': <true|false>,
     *   'action' : 'getHistoryById',
     *   'results': [
     *     {
     *       ... search history data ...
     *     }
     *   ],
     * }
     *
     * @param Request $request that will be used to complete the operation.
     * @param Application $app that will be used to complete the operation.
     * @param int $id of the Search History record to be retrieved.
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws AccessDeniedException
     */
    public function getHistoryById(Request $request, Application $app, $id)
    {
        $action = 'getHistoryById';

        $user = $this->authorize($request);

        $realm = $this->getStringParam($request, 'realm', true);

        $searchHistory = $this->getUserStore($user, $realm);

        $record = $searchHistory->getById($id);
        if (isset($record)) {
            foreach ($record['results'] as &$result) {
                if (isset($result)) {
                    $result['dtype'] = 'jobid';
                }
            }
        }

        // the following two lines are here to make the results match the old
        // output.
        $record['success'] = true;
        $record['action'] = $action;

        $results = $app->json($record);

        return $results;
    }

    public function getHistoryByTitle(Request $request, Application $app, $realm, $title)
    {
        $action = 'getHistoryByTitle';

        $user = $this->getUserFromRequest($request);

        $userHistory = $this->getUserStore($user, $realm);
        $searches = $userHistory->get();
        foreach ($searches as $search) {
            $text = isset($search['text']) ? $search['text'] : null;
            if ($text == $title) {
                if (!isset($search['dtype'])) {
                    $search['dtype'] = 'recordid';
                }
                return $app->json(
                    array(
                        'action' => $action,
                        'success' => true,
                        'data' => $search
                    ),
                    200
                );
                break;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * retrieve and sanitize the search history parameters for a request
     * throws and exception if the parameters are missing.
     * @param Request $request The request.
     * @return array decoded search parameters.
     * @throws BadRequestHttpException If the required 'data' parameter is
     *                                 absent.
     */
    private function getSearchParams(Request $request)
    {
        $data = $this->getStringParam($request, 'data', true);

        $decoded = json_decode($data, true);

        if ($decoded === null || !isset($decoded['text']) ) {
            throw new BadRequestHttpException(
                'Malformed request. Expected \'data.text\' to be present.'
            );
        }

        $decoded['text'] = htmlspecialchars($decoded['text'], ENT_COMPAT | ENT_HTML5);

        return $decoded;
    }

    /**
     * Attempt to create a new Search History record with the provided 'data'
     * form parameter.
     *
     * @param Request $request that will be used to complete the requested operation
     * @param Application $app that will be used to complete the requested operation
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws AccessDeniedException
     * @throws BadRequestHttpException
     */
    public function createHistory(Request $request, Application $app)
    {
        $action = 'createHistory';


        $user = $this->authorize($request);


        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);

        $decoded = $this->getSearchParams($request);

        $recordId = $this->getIntParam($request, 'recordid');

        $created = is_numeric($recordId)
            ? $history->upsert($recordId, $decoded)
            : $history->insert($decoded);

        if ($created == null) {
            throw new BadRequestHttpException(
                "Create request will exceed record storage restrictions " .
                "(record count limited to " .
                WarehouseControllerProvider::_MAX_RECORDS . ")"
            );
        }

        if (!isset($created['dtype'])) {
            $created['dtype'] = 'recordid';
        }


        return $app->json(
            array(
                'success' => true,
                'action' => $action,
                'total' => count($created),
                'results' => $created
            )
        );
    }

    /**
     * Attempt to update the Search History Record identified by the provided
     * 'id' with the contents of the form parameter 'data'.
     *
     * @param Request $request that will be used to complete the requested operation
     * @param Application $app that will be used to complete the requested operation
     * @param int $id of the Search History Record to be updated.
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws AccessDeniedException
     */
    public function updateHistory(Request $request, Application $app, $id)
    {
        $user = $this->authorize($request);

        $action = 'updateHistory';

        $data = $this->getSearchParams($request);

        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);

        $result = $history->upsert($id, $data);

        if (!isset($result['dtype'])) {
            $result['dtype'] = 'recordid';
        }

        $results = $app->json(
            array(
                'success' => true,
                'action' => $action,
                'results' => $result
            ),
            200
        );

        return $results;
    }

    /**
     * Attempt to delete the Search History Record identified by the
     * provided 'id'.
     *
     * @param Request $request that will be used to complete the requested operation
     * @param Application $app that will be used to complete the requested operation
     * @param int $id of the Search History Record to be removed.
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws AccessDeniedException
     */
    public function deleteHistory(Request $request, Application $app, $id)
    {
        $user = $this->authorize($request);
        $action = 'deleteHistory';

        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);
        $deleted = $history->delById($id);

        return $app->json(
            array(
                'success' => true,
                'action' => $action,
                'total' => $deleted
            )
        );
    }

    /**
     * Attempt to remove all of the Search History Records for the currently logged in
     * user making the request.
     *
     * @param Request $request that will be used to complete the requested operation
     * @param Application $app that will be used to complete the requested operation
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws AccessDeniedException
     */
    public function deleteAllHistory(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $action = 'deleteAllHistory';

        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);
        $history->del();

        return $app->json(
            array(
                'success' => true,
                'action' => $action
            )
        );
    }

    /**
     * Attempt to perform a search of the jobs realm with the criteria provided in the
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws AccessDeniedException
     */
    public function searchJobs(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $realm = $this->getStringParam($request, 'realm', true);
        $params = $this->getStringParam($request, 'params', true);

        $params = json_decode($params, true);

        if($params === null) {
            throw new BadRequestHttpException('params parameter must be valid JSON');
        }

        if ( (isset($params['resource_id']) && isset($params['local_job_id'])) || isset($params['jobref']) ) {
            return $this->getJobByPrimaryKey($app, $user, $realm, $params);
        } else {
            $startDate = $this->getStringParam($request, 'start_date', true);
            $endDate = $this->getStringParam($request, 'end_date', true);

            return $this->processJobSearch($request, $app, $user, $realm, $startDate, $endDate, 'searchJobs');
        }
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws AccessDeniedException
     */
    public function searchJobsByAction(Request $request, Application $app, $action)
    {
        $user = $this->authorize($request);

        $name = 'searchJobsByAction';

        $realm = $this->getStringParam($request, 'realm');
        $jobId = $this->getIntParam($request, 'jobid');

        $results = $this->processJobSearchByAction($request, $app, $user, $action, $realm, $jobId, $name);

        return $results;

    }

    /**
     * Get the realms available for the user's active role.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the realms retrieved.
     */
    public function getRealms(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // Get the realms for the user's active role.
        $realms = Realms::getRealmsForUser($user);

        // Return the realms found.
        return $app->json(array(
            'success' => true,
            'results' => $realms,
        ));
    }

    /**
     * Return aggregate data from the datawarehouse
     *
     * @param Request     $request The request used to make this call.
     * @param Application $app     The router application.
     *
     * @return json object
     */
    public function getAggregateData(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $json_config = $this->getStringParam($request, 'config', true);
        $start = $this->getIntParam($request, 'start', true);
        $limit = $this->getIntParam($request, 'limit', true);

        $config = json_decode($json_config);

        if ($config === null) {
            throw new BadRequestHttpException('syntax error in config parameter');
        }

        $mandatory = array('realm', 'group_by', 'statistics', 'aggregation_unit', 'start_date', 'end_date', 'order_by');
        foreach ($mandatory as $required_property) {
            if (!property_exists($config, $required_property)) {
                throw new BadRequestHttpException('Missing mandatory config property ' . $required_property);
            }
        }

        $permittedStats = Acls::getPermittedStatistics($user, $config->realm, $config->group_by);
        $forbiddenStats = array_diff($config->statistics, $permittedStats);

        if (!empty($forbiddenStats) ) {
            throw new AccessDeniedException('access denied to ' . json_encode($forbiddenStats));
        }

        $query = new \DataWarehouse\Query\AggregateQuery(
            $config->realm,
            $config->aggregation_unit,
            $config->start_date,
            $config->end_date,
            $config->group_by
        );

        $allRoles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allRoles, $user);

        foreach ($config->statistics as $stat) {
            $query->addStat($stat);
        }

        if (property_exists($config, 'filters')) {
            $query->setRoleParameters($config->filters);
        }

        if (!property_exists($config->order_by, 'field') || !property_exists($config->order_by, 'dirn')) {
            throw new BadRequestHttpException('Malformed config property order_by');
        }
        $dirn = $config->order_by->dirn === 'asc' ? 'ASC' : 'DESC';

        $query->addOrderBy($config->order_by->field, $dirn);

        $dataset = new \DataWarehouse\Data\SimpleDataset($query);
        $results = $dataset->getResults($limit, $start);
        foreach($results as &$val){
            $val['name'] = $val[$config->group_by . '_name'];
            $val['id'] = $val[$config->group_by . '_id'];
            $val['short_name'] = $val[$config->group_by . '_short_name'];
            $val['order_id'] = $val[$config->group_by . '_order_id'];
            unset($val[$config->group_by . '_id']);
            unset($val[$config->group_by . '_name']);
            unset($val[$config->group_by . '_short_name']);
            unset($val[$config->group_by . '_order_id']);
        }
        return $app->json(
            array(
                'results' => $results,
                'total' => $dataset->getTotalPossibleCount(),
                'success' => true
            )
        );
    }

    /**
     * Get the dimensions available for the user's active role.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the dimensions retrieved.
     */
    public function getDimensions(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // Get parameters.
        $realmParam = $this->getStringParam($request, 'realm');

        // Get the dimensions for the query group, realm, and user's active role.
        $groupBys = Acls::getQueryDescripters(
            $user,
            $realmParam
        );

        $dimensionsToReturn = array();
        foreach($groupBys as $groupByName => $queryDescriptors) {
            foreach($queryDescriptors as $queryDescriptor) {
                if ($groupByName !== 'none') {
                    $dimensionsToReturn[] = array(
                        'id' => $queryDescriptor->getGroupByName(),
                        'name' => $queryDescriptor->getGroupByLabel(),
                        // NOTE: 'Category' is capitalized for historical reasons.
                        'Category' => $queryDescriptor->getGroupByCategory(),
                        'description' => $queryDescriptor->getGroupByDescription()
                    );
                }
            }
        }

        // Return the dimensions found.
        return $app->json(array(
            'success' => true,
            'results' => $dimensionsToReturn,
        ));
    }

    /**
     * Get the dimension values available for the user's active role.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the dimension values retrieved.
     */
    public function getDimensionValues(Request $request, Application $app, $dimension)
    {
        $user = $this->authorize($request);

        // Get parameters.
        $offset = $this->getIntParam($request, 'offset', false, 0);
        $limit = $this->getIntParam($request, 'limit');
        $searchText = $this->getStringParam($request, 'search_text');

        $realmParameter = $this->getStringParam($request, 'realm');
        $realms = null;
        if ($realmParameter !== null) {
            $realms = preg_split('/,\s*/', trim($realmParameter), null, PREG_SPLIT_NO_EMPTY);
        }

        // Get the dimension values.
        $dimensionValues = MetricExplorer::getDimensionValues(
            $user,
            $dimension,
            $realms,
            $offset,
            $limit,
            $searchText
        );

        // Change the name key for each dimension value to "long_name".
        $dimensionValuesData = $dimensionValues['data'];
        foreach ($dimensionValuesData as &$dimensionValue) {
            $dimensionValue['long_name'] = html_entity_decode($dimensionValue['name']);
            $dimensionValue['name'] = $dimensionValue['long_name'];
            $dimensionValue['short_name'] = html_entity_decode($dimensionValue['short_name']);
        }

        // Return the found dimension values.
        return $app->json(array(
            'success' => true,
            'results' => $dimensionValuesData,
        ));
    }

    /**
     * Get a set of quick filters tailored to the current user.
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the metrics retrieved.
     */
    public function getQuickFilters(Request $request, Application $app)
    {
        // Get the user.
        $user = $this->getUserFromRequest($request);

        // Check whether multiple service providers are supported or not.
        try {
            $multipleProvidersSupported = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
        }
        catch(Exception $e){
            $multipleProvidersSupported = false;
        }

        // Generate generic quick filters for all users.
        $filters = array();
        $filtersByFilterId = array();
        $dimensionIdsToNames = array();

        $serviceProviderDimensionId = 'provider';
        if ($multipleProvidersSupported) {
            $jobsRealm = \Realm\Realm::factory('Jobs');
            $serviceProviderGroupBy = $jobsRealm->getGroupByObject($serviceProviderDimensionId);
            $serviceProviderDimensionName = $serviceProviderGroupBy->getName();
            $dimensionIdsToNames[$serviceProviderDimensionId] = $serviceProviderDimensionName;
            $serviceProviders = $serviceProviderGroupBy->getAttributeValues();
            foreach ($serviceProviders as $serviceProvider) {
                $filtersByFilterId[$serviceProviderDimensionId][$serviceProvider['id']] = array(
                    'valueName' => $serviceProvider['short_name'],
                    'valueId' => $serviceProvider['id'],
                    'isUserSpecificFilter' => false,
                    'isMostPrivilegedRoleFilter' => false,
                );
                $filters[$serviceProviderDimensionId][] = &$filtersByFilterId[$serviceProviderDimensionId][$serviceProvider['id']];
            }
        }

        // Generate user-specific quick filters if logged in.
        if (!$user->isPublicUser()) {
            $personId = (int)$user->getPersonID();
            $acls = $user->getAcls(true);
            $mostPrivilegedAcl = $user->getMostPrivilegedRole()->getName();
            foreach ($acls as $acl) {
                $isMostPrivilegedRole = ($acl === $mostPrivilegedAcl) && $personId !== -1;
                $parameters = Parameters::getParameters($user, $acl);

                foreach ($parameters as $dimensionId => $valueId) {
                    if (!$multipleProvidersSupported && $dimensionId === $serviceProviderDimensionId) {
                        continue;
                    }

                    if (isset($filtersByFilterId[$dimensionId][$valueId])) {
                        $filtersByFilterId[$dimensionId][$valueId]['isUserSpecificFilter'] = true;
                        if ($isMostPrivilegedRole) {
                            $filtersByFilterId[$dimensionId][$valueId]['isMostPrivilegedRoleFilter'] = true;
                        }
                        continue;
                    }

                    $valueName = MetricExplorer::getDimensionValueName($user, $dimensionId, $valueId);
                    if ($valueName === null) {
                        continue;
                    }

                    $filtersByFilterId[$dimensionId][$valueId] = array(
                        'valueName' => $valueName,
                        'valueId' => $valueId,
                        'isUserSpecificFilter' => true,
                        'isMostPrivilegedRoleFilter' => $isMostPrivilegedRole,
                    );
                    $filters[$dimensionId][] = &$filtersByFilterId[$dimensionId][$valueId];

                    if (!isset($dimensionIdsToNames[$dimensionId])) {
                        $dimensionIdsToNames[$dimensionId] = MetricExplorer::getDimensionName($user, $dimensionId);
                    }
                }

            }
        }

        // Return the quick filters.
        return $app->json(array(
            'success' => true,
            'results' => array(
                'dimensionNames' => $dimensionIdsToNames,
                'filters' => $filters,
            ),
        ));
    }

        /**
     * Attempt to retrieve the the name for the provided dimensionId.
     *
     * @param Request     $request
     * @param Application $app
     * @param string      $dimensionId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDimensionName(Request $request, Application $app, $dimensionId)
    {
        $user = $this->getUserFromRequest($request);
        $dimensionName = MetricExplorer::getDimensionName($user, $dimensionId);
        $success = !empty($dimensionName);

        $status = $success ? 200 : 404;
        $payload = $success
                 ? array(
                     'success' => $success,
                     'results' => array(
                         'name' => $dimensionName
                     ))
                 : array(
                         'success' => false,
                         'message' => "Unable to find a name for dimension: $dimensionId"
                 );

        return $app->json(
            $payload,
            $status
        );
    }

    /**
     * Attempt to retrieve the the name for the provided dimensionId and
     * valueId.
     *
     * @param Request     $request
     * @param Application $app
     * @param string      $dimensionId
     * @param string      $valueId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDimensionValueName(Request $request, Application $app, $dimensionId, $valueId)
    {
        $user = $this->getUserFromRequest($request);
        $valueName = MetricExplorer::getDimensionValueName($user, $dimensionId, $valueId);
        $success = !empty($valueName);

        $status = $success ? 200 : 404;
        $payload = $success
                 ? array(
                     'success' => $success,
                     'results' => array(
                         'name' => $valueName
                     )
                 )
                 : array(
                     'success' => $success,
                     'message' => "Unable to find a name for dimesion: $dimensionId | value: $valueId"
                 );

        return $app->json(
            $payload,
            $status
        );
    }

    /**
     * Get the aggregation units available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available aggregation units.
     */
    public function getAggregationUnits(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available aggregation units.
        $aggregation_units = \DataWarehouse\QueryBuilder::getAggregationUnits();
        return $app->json(array(
            'success' => true,
            'results' => array_keys($aggregation_units),
        ));
    }

    /**
     * Get the dataset types available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available dataset types.
     */
    public function getDatasetTypes(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available dataset types.
        $datasetTypes = \DataWarehouse\QueryBuilder::getDatasetTypes();
        return $app->json(array(
            'success' => true,
            'results' => $datasetTypes,
        ));
    }

    /**
     * Get the dataset output formats available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available dataset output formats.
     */
    public function getDatasetOutputFormats(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available dataset output formats.
        return $app->json(array(
            'success' => true,
            'results' => \DataWarehouse\ExportBuilder::$dataset_action_formats,
        ));
    }

    /**
     * Generate a dataset using the given parameters.
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response
     */
    public function getDatasets(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);

        // Get parameters.
        $params = $request->query->all();

        // Send the parameters and user to the Usage-to-Metric Explorer adapter.
        $usageAdapter = new Usage($params);
        $chartResponse = $usageAdapter->getCharts($user);

        // Return the response.
        return new Response(
            $chartResponse['results'],
            200,
            $chartResponse['headers']
        );
    }

    /**
     * Get the plot output formats available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available plot output formats.
     */
    public function getPlotOutputFormats(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available plot output formats.
        return $app->json(array(
            'success' => true,
            'results' => \DataWarehouse\VisualizationBuilder::$plot_action_formats,
        ));
    }

    /**
     * Get the plot display types available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available plot display types.
     */
    public function getPlotDisplayTypes(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available plot display types.
        return $app->json(array(
            'success' => true,
            'results' => \DataWarehouse\VisualizationBuilder::$display_types,
        ));
    }

    /**
     * Get the plot combine types available for use.
     *
     * Ported from: classes/REST/DataWarehouse/Explorer.php
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the available plot combine types.
     */
    public function getPlotCombineTypes(Request $request, Application $app)
    {
        $this->authorize($request);

        // Return the available plot combine types.
        return $app->json(array(
            'success' => true,
            'results' => \DataWarehouse\VisualizationBuilder::$combine_types,
        ));
    }

    /**
     * Generate a plot using the given parameters.
     *
     * @param  Request $request The request used to make this call.
     * @param  Application $app The router application.
     * @return Response             A response containing the following info
     *                              if JSON was requested:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the plot.
     *
     *                              If another format was requested, the
     *                              response will contain file data.
     */
    public function getPlots(Request $request, Application $app)
    {

        $this->authorize($request);

        return $this->getDatasets($request, $app);
    }

    public function processJobSearch(Request $request, Application $app, XDUser $user, $realm, $startDate, $endDate, $action)
    {
        $queryDescripters = Acls::getQueryDescripters($user, $realm);

        if (empty($queryDescripters)) {
            throw new BadRequestHttpException('Invalid realm');
        }

        $offset = $this->getIntParam($request, 'start', true);
        $limit = $this->getIntParam($request, 'limit', true);

        $searchParameterStr = $this->getStringParam($request, 'params', true);

        $searchParams = json_decode($searchParameterStr, true);

        if ($searchParams === null || !is_array($searchParams)) {
            throw new BadRequestHttpException('The params parameter must be a json object');
        }

        $params = array_intersect_key($searchParams, $queryDescripters);

        if (count($params) != count($searchParams)) {
            throw new BadRequestHttpException('Invalid search parameters specified in params object');
        } else {
            $QueryClass = "\\DataWarehouse\\Query\\$realm\\RawData";
            $query = new $QueryClass($realm, "day", $startDate, $endDate, null, "", array());

            $allRoles = $user->getAllRoles();
            $query->setMultipleRoleParameters($allRoles, $user);

            if (!empty($params)) {
                $query->setRoleParameters($params);
            }

            $dataSet = new \DataWarehouse\Data\SimpleDataset($query);
            $raw = $dataSet->getResults($limit, $offset);

            $data = array();
            foreach ($raw as $row) {
                $resource = $row['resource'];
                $localJobId = $row['local_job_id'];

                $row['text'] = "$resource-$localJobId";
                $row['dtype'] = 'jobid';
                array_push($data, $row);
            }

            $total = $dataSet->getTotalPossibleCount();

            $results = $app->json(
                array(
                    'success' => true,
                    'action' => $action,
                    'results' => $data,
                    'totalCount' => $total
                )
            );

            if ($total === 0) {
                // No data returned for the query. This could be because the roleParameters
                // caused the data to be filtered. In this case we will return access-denied.
                // need to rerun the query without the role params to see if any results come back.
                // note the data for the priviledged query is not returned to the user.

                $privQuery = new $QueryClass("day", $startDate, $endDate, null, "", array());
                $privQuery->setRoleParameters($params);

                $privDataSet = new \DataWarehouse\Data\SimpleDataset($privQuery, 1, 0);
                $privResults = $privDataSet->getResults();
                if (count($privResults) != 0) {
                    $results = $app->json(
                        array(
                            'success' => false,
                            'action' => $action,
                            'message' => 'Unable to complete the requested operation. Access Denied.'
                        ),
                        401
                    );
                }
            }
        }

        return $results;
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param XDUser $user
     * @param $action
     * @param $realm
     * @param $jobId
     * @param $actionName
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws AccessDeniedException
     */
    public function processJobSearchByAction(Request $request, Application $app, XDUser $user, $action, $realm, $jobId, $actionName)
    {
        switch ($action) {
            case 'accounting':
            case 'jobscript':
            case 'analysis':
            case 'metrics':
            case 'analytics':
                $results = $this->getJobData($app, $user, $realm, $jobId, $action, $actionName);
                break;
            case 'peers':
                $start = $this->getIntParam($request, 'start', true);
                $limit = $this->getIntParam($request, 'limit', true);
                $results = $this->getJobPeers($app, $user, $realm, $jobId, $start, $limit);
                break;
            case 'executable':
                $results = $this->getJobExecutable($app, $user, $realm, $jobId, $action, $actionName);
                break;
            case 'detailedmetrics':
                $results = $this->getJobSummary($app, $user, $realm, $jobId, $action, $actionName);
                break;
            case 'timeseries':
                $tsId = $this->getStringParam($request, 'tsid', true);
                $nodeId = $this->getIntParam($request, 'nodeid', false);
                $cpuId = $this->getIntParam($request, 'cpuid', false);

                $results = $this->getJobTimeSeriesData($app, $request, $user, $realm, $jobId, $tsId, $nodeId, $cpuId);
                break;
            case 'vmstate':
                $results = $this->getJobTimeSeriesData($app, $request, $user, $realm, $jobId, null, null, null);
                break;
            default:
                $results = $app->json(
                    array(
                        'success' => false,
                        'action' => $actionName,
                        'message' => "Unable to process the requested operation. Unsupported action $action."
                    ),
                    400
                );
                break;
        }

        return $results;
    }

    /**
     * Return data about a job's peers.
     *
     * @param Application $app The router application.
     * @param XDUser $user the logged in user.
     * @param $realm data realm.
     * @param $jobId the unique identifier for the job.
     * @param $start the start offset (for store paging).
     * @param $limit the number of records to return (for store paging).
     * @return json in Extjs.store parsable format.
     * @throws NotFoundHttpException
     */
    protected function getJobPeers(Application $app, XDUser $user, $realm, $jobId, $start, $limit)
    {
        $jobdata = $this->getJobDataSet($user, $realm, $jobId, 'internal');
        if (!$jobdata->hasResults()) {
            throw new NotFoundHttpException('The requested resource does not exist');
        }
        $jobresults = $jobdata->getResults();
        $thisjob = $jobresults[0];

        $i = 0;

        $result = array(
            'series' => array(
                array(
                    'name' => 'Walltime',
                    'data' => array(
                        array(
                            'x' => $i++,
                            'low' => $thisjob['start_time_ts'] * 1000.0,
                            'high' => $thisjob['end_time_ts'] * 1000.0
                        )
                    )
                ),
                array(
                    'name' => 'Walltime',
                    'data' => array()
                )
            ),
            'categories' => array(
                'Current'
            ),
            'schema' => array(
                'timezone' => $thisjob['timezone'],
                'ref' => array(
                    'realm' => $realm,
                    'jobid' => $jobId,
                    "text" => $thisjob['resource'] . '-' . $thisjob['local_job_id']
                )
            )
        );

        $dataset = $this->getJobDataSet($user, $realm, $jobId, 'peers');
        foreach ($dataset->getResults() as $index => $jobpeer) {
            if ( ($index >= $start) && ($index < ($start + $limit))) {
                $result['series'][1]['data'][] = array(
                    'x' => $i++,
                    'low' => $jobpeer['start_time_ts'] * 1000.0,
                    'high' => $jobpeer['end_time_ts'] * 1000.0,
                    'ref' => array(
                        'realm' => $realm,
                        'jobid' => $jobpeer['jobid'],
                        'local_job_id' => $jobpeer['local_job_id'],
                        'resource' => $jobpeer['resource']
                    )
                );
                $result['categories'][] = $jobpeer['resource'] . '-' . $jobpeer['local_job_id'];
            }
        }

        return  $app->json(array(
            'success' => true,
            'data' => array($result),
            'total' => count($dataset->getResults())
        ));
    }

    /**
     * @param Application $app
     * @param XDUser $user
     * @param $realm
     * @param $jobId
     * @param $action
     * @param $actionName
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \DataWarehouse\Query\Exceptions\AccessDeniedException
     */
    private function getJobData(Application $app, XDUser $user, $realm, $jobId, $action, $actionName)
    {
        $dataSet = $this->getJobDataSet($user, $realm, $jobId, $action);

        return $app->json(
            array(
                'data' => $dataSet->export(),
                'success' => true
            ),
            200
        );
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $action
     * @return \DataWarehouse\Data\RawDataset
     * @throws \DataWarehouse\Query\Exceptions\AccessDeniedException
     */
    private function getJobDataSet(XDUser $user, $realm, $jobId, $action)
    {
        if (!\DataWarehouse\Access\RawData::realmExists($user, $realm)) {
            throw new \DataWarehouse\Query\Exceptions\AccessDeniedException;
        }

        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $params = array('primary_key' => $jobId);
        $query = new $QueryClass($params, $action);

        $allRoles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allRoles, $user);

        $dataSet = new \DataWarehouse\Data\RawDataset($query, $user);

        if (!$dataSet->hasResults()) {
            $privilegedQuery = new $QueryClass($params, $action);
            $results = $privilegedQuery->execute(1);
            if ($results['count'] != 0) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException;
            }
        }
        return $dataSet;
    }

    /**
     * Retrieves the executable information for a given job.
     *
     * @param Application $app the Application instance used.
     * @param \XDUser $user the user that made this particular request.
     * @param string $realm the data realm in which this request was made.
     * @param string $jobId the unique identifier for the job.
     * @param string $action the parent action that called this function.
     * @param string $actionName the child action that called this function.
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws Exception
     */
    private function getJobExecutable(Application $app, \XDUser $user, $realm, $jobId, $action, $actionName)
    {
        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $QueryClass();

        $execInfo = $query->getJobExecutableInfo($user, $jobId);

        if (count($execInfo) === 0) {
            throw new Exception(
                "Executable information unavailable for $realm $jobId",
                500
            );
        }

        return $app->json(
            $this->arraytostore(json_decode(json_encode($execInfo), true)),
            200
        );
    }

    private function arraytostore(array $values)
    {
            return array(array("key" => ".", "value" => "", "expanded" => true, "children" => $this->atosrecurse($values, false) ));
    }

    private function atosrecurse(array $values)
    {
        $result = array();
        foreach($values as $key => $value) {
            if( is_array($value) ) {
                if(count($value) > 0 ) {
                    $result[] = array("key" => "$key", "value" => "", "expanded" => true, "children" => $this->atosrecurse($value) );
                }
            } else {
                $result[] = array("key" => "$key", "value" => $value, "leaf" => true);
            }
        }
        return $result;
    }


    /**
     * @param Application $app
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $tsId
     * @param int $nodeId
     * @param int $infoId
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws Exception
     */
    private function processJobNodeTimeSeriesRequest(
        Application $app,
        XDUser $user,
        $realm,
        $jobId,
        $tsId,
        $nodeId,
        $infoId,
        $action
    ) {

        if ($infoId != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS) {
            throw new BadRequestHttpException("Node $infoId is a leaf");
        }

        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();

        $result = array();
        foreach ($info->getJobTimeseriesMetricNodeMeta($user, $jobId, $tsId, $nodeId) as $cpu) {
            $cpu['url'] = "/rest/v0.1/warehouse/search/jobs/timeseries";
            $cpu['type'] = "timeseries";
            $cpu['dtype'] = "cpuid";
            $result[] = $cpu;
        }

        return $app->json(array("success" => true, "results" => $result));

    }

    /**
     * @param Application $app
     * @param XDUser $user
     * @param $realm
     * @param int $jobId
     * @param $tsId
     * @param int $infoId
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     */
    private function processJobTimeSeriesRequest(
        Application $app,
        XDUser $user,
        $realm,
        $jobId,
        $tsId,
        $infoId,
        $action
    ) {

        if ($infoId != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS) {
            throw new BadRequestHttpException("Node $infoId is a leaf");
        }

        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();

        $result = array();
        foreach ($info->getJobTimeseriesMetricMeta($user, $jobId, $tsId) as $node) {
            $node['url'] = "/rest/v0.1/warehouse/search/jobs/timeseries";
            $node['type'] = "timeseries";
            $node['dtype'] = "nodeid";
            $result[] = $node;
        }

        return $app->json(array("success" => true, "results" => $result));
    }

    /**
     * @param Application $app
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param int $infoId
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     */
    private function processJobRequest(
        Application $app,
        XDUser $user,
        $realm,
        $jobId,
        $infoId,
        $action
    ) {


        switch ($infoId) {
            case "" . \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE:
                $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
                $info = new $infoclass();

                $result = array();
                foreach ($info->getJobTimeseriesMetaData($user, $jobId) as $tsid) {
                    $tsid['url'] = "/rest/v0.1/warehouse/search/jobs/vmstate";
                    $tsid['type'] = "timeseries";
                    $tsid['dtype'] = "tsid";
                    $result[] = $tsid;
                }
                return $app->json(array('success' => true, "results" => $result));
                break;
            case "" . \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS:
                $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
                $info = new $infoclass();

                $result = array();
                foreach ($info->getJobTimeseriesMetaData($user, $jobId) as $tsid) {
                    $tsid['url'] = "/rest/v0.1/warehouse/search/jobs/timeseries";
                    $tsid['type'] = "timeseries";
                    $tsid['dtype'] = "tsid";
                    $result[] = $tsid;
                }
                return $app->json(array('success' => true, "results" => $result));
                break;
            default:
                throw new BadRequestHttpException("Node is a leaf");
        }
    }

    /**
     * @param Application $app
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function processJobByJobId(
        Application $app,
        XDUser $user,
        $realm,
        $jobId,
        $action
    ) {

        $JobMetaDataClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $JobMetaDataClass();
        $jobMetaData = $info->getJobMetadata($user, $jobId);

        $data = array_intersect_key($this->_supported_types, $jobMetaData);

        return $app->json(
            array(
                'success' => true,
                'action' => $action,
                'results' => array_values($data)
            )
        );
    }

    /**
     * @param Application $app
     * @param XDUser $user
     * @param string $realm
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function processHistoryRequest(Application $app, XDUser $user, $realm, $action)
    {
        $history = $this->getUserStore($user, $realm);
        $output = $history->get();
        $results = array();
        foreach ($output as $item) {
            $results[] = array(
                'text' => $item['text'],
                'dtype' => 'recordid',
                'recordid' => $item['recordid'],
                'searchterms' => $item['searchterms']
            );
        }

        return $app->json(
            array(
                'success' => true,
                'action' => $action,
                'results' => $results,
                'total' => count($results)
            )
        );
    }

    /**
     * @param Application $app
     * @param $action
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function processHistoryDefaultRealmRequest(Application $app, XDUser $user, $action)
    {
        $results = array();

        foreach(\DataWarehouse\Access\RawData::getRawDataRealms($user) as $realmconfig) {
            $history = $this->getUserStore($user, $realmconfig['name']);
            $records = $history->get();
            if (!empty($records)) {
                $results[] = array(
                    'dtype' => 'realm',
                    'realm' => $realmconfig['name'],
                    'text' => $realmconfig['display']
                );
            }
        }

        return $app->json(
            array(
                'success' => true,
                'action' => $action,
                'results' => $results
            )
        );
    }

    private function encodeFloatArray(array $in)
    {
        $out = array();
        foreach ($in as $key => $value) {
            if (is_float($value) && is_nan($value)) {
                $out[$key] = 'NaN';
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    private function getJobSummary(Application $app, \XDUser $user, $realm, $jobId, $action, $actionName)
    {
        $queryclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $queryclass();

        $jobsummary = $query->getJobSummary($user, $jobId);

        $result = array();

        // Really this should be a recursive function!
        foreach ($jobsummary as $key => $val) {
            $name = "$key";
            if (is_array($val)) {
                if (array_key_exists('avg', $val) && !is_array($val['avg'])) {
                    $result[] = array_merge(array("name" => $name, "leaf" => true), $this->encodeFloatArray($val));
                } else {
                    $l1data = array("name" => $name, "avg" => "", "expanded" => "true", "children" => array());
                    foreach ($val as $subkey => $subval) {
                        $subName = "$subkey";
                        if (is_array($subval)) {
                            if (array_key_exists('avg', $subval) && !is_array($subval['avg'])) {
                                $l1data['children'][] = array_merge(array("name" => $subName, "leaf" => true), $this->encodeFloatArray($subval));
                            } else {
                                $l2data = array("name" => $subName, "avg" => "", "expanded" => "true", "children" => array());

                                foreach ($subval as $subsubkey => $subsubval) {
                                    $subSubName = "$subsubkey";
                                    if (is_array($subsubval)) {
                                        if (array_key_exists('avg', $subsubval) && !is_array($subsubval['avg'])) {
                                            $l2data['children'][] = array_merge(array("name" => $subSubName, "leaf" => true), $this->encodeFloatArray($subsubval));
                                        }
                                    }
                                }

                                if (count($l2data['children']) > 0) {
                                    $l1data['children'][] = $l2data;
                                }
                            }
                        }
                    }
                    if (count($l1data['children']) > 0) {
                        $result[] = $l1data;
                    }
                }
            }
        }

        return $app->json(
            $result
        );
    }

    /**
     * Encode a chart data series in CSV data and send as an attachment
     * @param $data the data series information
     * @return Response the data in a CSV file attachment
     */
    private function chartDataResponse($data)
    {
        $filename = tempnam(sys_get_temp_dir(), 'xdmod');
        $fp = fopen($filename, 'w');

        $columns = array('Time');
        $ndatapoints = 0;
        foreach ($data['series'] as $series) {
            if (isset($series['dtype'])) {
                $columns[] = $series['name'];
                if ($ndatapoints === 0) {
                    $ndatapoints = count($series['data']);
                }
            }
        }
        fputcsv($fp, $columns);

        for ($i = 0; $i < $ndatapoints; $i++) {
            $outline = array();
            foreach ($data['series'] as $series) {
                if (isset($series['dtype'])) {
                    if (count($outline) === 0) {
                        $outline[] = isset($series['data'][$i]['x']) ? $series['data'][$i]['x'] : $series['data'][$i][0];
                    }
                    $outline[] = isset($series['data'][$i]['y']) ? $series['data'][$i]['y'] : $series['data'][$i][1];
                }
            }
            fputcsv($fp, $outline);
        }
        fclose($fp);

        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($filename);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(
            \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $data['schema']['description'] . '.csv'
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Render the chart series data as an image
     * This function is used for exporting *Job Viewer Timeseries* plots only.
     * It repeats chart config performed for browser in job viewer's ChartPanel.js.
     *
     * @param $data the data
     * @param $type the type of image to generate
     * @return Response the image as an attachment
     */
    private function chartImageResponse($data, $type, $settings)
    {
        $axisTitleFontSize = ($settings['font_size'] + 12) . 'px';
        $axisLabelFontSize = ($settings['font_size'] + 11) . 'px';
        $mainTitleFontSize = ($settings['font_size'] + 16) . 'px';

        $lineWidth = 1 + $settings['scale'];

        $chartConfig = array(
           'data' => $data,
           'axisTickSize' => $axisLabelFontSize,
           'axisTitleSize' => $axisTitleFontSize,
           'lineWidth' => $lineWidth,
           'chartTitleSize' => $mainTitleFontSize
        );

        $globalConfig = array(
            'timezone' => $data['schema']['timezone']
        );

        $chartImage = \xd_charting\exportChart($chartConfig, $settings['width'], $settings['height'], $settings['scale'], $type, $globalConfig, $settings['fileMetadata']);
        $chartFilename = $settings['fileMetadata']['title'] . '.' . $type;
        $mimeOverride = $type == 'svg' ? 'image/svg+xml' : null;

        return $this->sendAttachment($chartImage, $chartFilename, $mimeOverride);
    }

    private function getJobTimeSeriesData(Application $app, Request $request, \XDUser $user, $realm, $jobId, $tsId, $nodeId, $cpuId)
    {
        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();
        $results = $info->getJobTimeseriesData($user, $jobId, $tsId, $nodeId, $cpuId);

        if (count($results) === 0) {
            throw new NotFoundHttpException('The requested resource does not exist');
        }

        $format = $this->getStringParam($request, 'format', false, 'json');

        if (!in_array($format, array('json', 'png', 'svg', 'pdf', 'csv'))) {
            throw new BadRequestHttpException('Unsupported format type.');
        }

        switch ($format) {
            case 'png':
            case 'pdf':
            case 'svg':
                $exportConfig = array(
                    'width' => $this->getIntParam($request, 'width', false, 916),
                    'height' => $this->getIntParam($request, 'height', false, 484),
                    'scale' => floatval($this->getStringParam($request, 'scale', false, '1')),
                    'font_size' => $this->getIntParam($request, 'font_size', false, 3),
                    'show_title' => $this->getStringParam($request, 'show_title', false, 'y') === 'y' ? true : false,
                    'fileMetadata' => array(
                        'author' => $user->getFormalName(),
                        'subject' => 'Timeseries data for ' . $results['schema']['source'],
                        'title' => $results['schema']['description']
                    )
                );
                $response = $this->chartImageResponse($results, $format, $exportConfig);
                break;
            case 'csv':
                $response = $this->chartDataResponse($results);
                break;
            case 'json':
            default:
                $response = $app->json(array("success" => true, "data" => array($results)));
                break;
        }

        return $response;
    }

    /**
     * Attempts to retrieve job information given the provided resource &
     * localjob id or by the db primary key (called jobref here to avoid end user
     * confusion between this internal identifier and the job id provided
     * by the resource-manager).
     *
     * @param Application $app
     * @param \XDUser $user
     * @param string $realm
     * @param array $searchparams
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \DataWarehouse\Query\Exceptions\AccessDeniedException
     * @throws BadRequestHttpException
     */
    private function getJobByPrimaryKey(Application $app, \XDUser $user, $realm, $searchparams)
    {
        if (!\DataWarehouse\Access\RawData::realmExists($user, $realm)) {
            throw new \DataWarehouse\Query\Exceptions\AccessDeniedException;
        }

        if (isset($searchparams['jobref']) && is_numeric($searchparams['jobref'])) {
            $params = array(
                'primary_key' => $searchparams['jobref']
            );
        } elseif (isset($searchparams['resource_id']) && isset($searchparams['local_job_id'])) {
            $params = array(
                'resource_id' => $searchparams['resource_id'],
                'job_identifier' => $searchparams['local_job_id']
            );
        } else {
            throw new BadRequestHttpException('invalid search parameters');
        }

        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $query = new $QueryClass($params, "brief");

        $allRoles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allRoles, $user);

        $dataSet = new \DataWarehouse\Data\RawDataset($query, $user);

        $results = array();
        foreach ($dataSet->getResults() as $result) {
            $result['text'] = $result['resource'] . "-" . $result['local_job_id'];
            $result['dtype'] = 'jobid';
            array_push($results, $result);
        }

        if (!$dataSet->hasResults()) {
            $privilegedQuery = new $QueryClass($params, "brief");
            $privilegedResults = $privilegedQuery->execute(1);

            if ($privilegedResults['count'] != 0) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException();
            }
        }

        return $app->json(
            array(
                'success' => true,
                "results" => $results,
                "totalCount" => count($results)
            )
        );
    }

    private function getUserStore(\XDUser $user, $realm)
    {
        $container = implode('-', array_filter(array(self::_HISTORY_STORE, strtoupper($realm))));
        return new \UserStorage($user, $container);
    }

    /**
     * Endpoint to get rows of raw data from the data warehouse. Requires API
     * token authorization.
     *
     * The request should contain the following parameters:
     * - start_date: start of date range for which to get data.
     * - end_date: end of date range for which to get data.
     * - realm: data realm for which to get data.
     *
     * It can also contain the following optional parameters:
     * - fields: list of aliases of fields to get (if not provided, all
     *           fields are obtained).
     * - filters: mapping of dimension names to their possible values.
     *            Results will only be included whose values for each of the
     *            given dimensions match one of the corresponding given values.
     * - offset: starting row index of data to get.
     *
     * If successful, the response will be a stream of chunks of data of type
     * `text/plain`. The beginning of each chunk is a string of hex digits
     * indicating the size of the chunk data in octets, followed by `\\r\\n`,
     * followed by the chunk data, followed by another `\\r\\n`. The first
     * chunk contains an array that contains the `display` property of each
     * obtained field. Each subsequent chunk contains an array that contains
     * the obtained field values for the next row of raw data. The final chunk
     * is of length zero to indicate the end of the stream.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws BadRequestHttpException if any of the required parameters are
     *                                 not included; if an invalid start date,
     *                                 end date, realm, field alias, or filter
     *                                 key is provided; if the end date is
     *                                 before the start date; or if the offset
     *                                 is negative.
     * @throws AccessDeniedException if the user does not have permission to
     *                               get raw data from the requested realm.
     */
    public function getRawData(Request $request, Application $app)
    {
        $user = Tokens::authenticate($request);
        $params = $this->validateRawDataParams($request, $user);
        $realmManager = new RealmManager();
        $queryClass = $realmManager->getRawDataQueryClass($params['realm']);
        $logger = $this->getRawDataLogger();
        $streamCallback = function () use (
            $user,
            $params,
            $queryClass,
            $logger
        ) {
            $reachedOffset = false;
            $i = 1;
            $offset = $params['offset'];
            // Jobs realm has a performance improvement by querying one day at
            // a time.
            if ('Jobs' === $params['realm']) {
                $currentDate = $params['start_date'];
                while ($currentDate <= $params['end_date']) {
                    self::echoRawData(
                        $queryClass,
                        $currentDate,
                        $currentDate,
                        $currentDate === $params['start_date'],
                        $currentDate === $params['end_date'],
                        $params,
                        $user,
                        $logger,
                        $reachedOffset,
                        $i,
                        $offset
                    );
                    $currentDate = date(
                        'Y-m-d',
                        strtotime("$currentDate + 1 day")
                    );
                }
            } else {
                // All other realms query the entire date range in a single
                // query.
                self::echoRawData(
                    $queryClass,
                    $params['start_date'],
                    $params['end_date'],
                    true,
                    true,
                    $params,
                    $user,
                    $logger,
                    $reachedOffset,
                    $i,
                    $offset
                );
            }
        };
        return $app->stream(
            $streamCallback,
            200,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Validate the parameters of the request from the given user to the raw
     * data endpoint (@see getRawData()).
     *
     * @param Request $request
     * @param XDUser $user
     * @return array of validated parameter values.
     * @throws BadRequestHttpException if any of the parameters are invalid.
     * @throws AccessDeniedException if the user does not have permission to
     *                               get raw data from the requested realm.
     */
    private function validateRawDataParams($request, $user)
    {
        $params = [];
        list(
            $params['start_date'], $params['end_date']
        ) = $this->validateRawDataDateParams($request);
        $params['realm'] = $this->getStringParam($request, 'realm', true);
        $allRealmNames = self::getRealmNames(Realms::getRealms());
        if (!in_array($params['realm'], $allRealmNames)) {
            throw new BadRequestHttpException(
                'No realm exists with the requested name.'
            );
        }
        $realmManager = new RealmManager();
        $allBatchExportRealms = self::getRealmNames(
            $realmManager->getRealms()
        );
        if (!in_array($params['realm'], $allBatchExportRealms)) {
            throw new BadRequestHttpException(
                'The requested realm is not configured to provide raw data.'
            );
        }
        $queryDescripters = Acls::getQueryDescripters($user, $params['realm']);
        if (empty($queryDescripters)) {
            throw new AccessDeniedException(
                'Your user account does not have permission to get raw data'
                . ' from the requested realm.'
            );
        }
        $params['fields'] = $this->getRawDataFieldsArray($request);
        $params['filters'] = $this->validateRawDataFiltersParams(
            $request,
            $queryDescripters
        );
        $params['offset'] = $this->getIntParam($request, 'offset', false, 0);
        if ($params['offset'] < 0) {
            throw new BadRequestHttpException('Offset must be non-negative.');
        }
        return $params;
    }

    /**
     * Generate a database logger for the raw data queries.
     *
     * @return \CCR\Logger
     */
    private function getRawDataLogger()
    {
        return Log::factory(
            'data-warehouse-raw-data-rest',
            [
                'console' => false,
                'file' => false,
                'mail' => false
            ]
        );
    }

    /**
     * Perform an unbuffered database query and echo the result using chunked
     * transfer encoding, flushing every 10000 rows.
     *
     * @param string $queryClass the fully qualified name of the query class.
     * @param string $startDate the start date of the query in ISO 8601 format.
     * @param string $endDate the end date of the query in ISO 8601 format.
     * @param bool $isFirstQueryInSeries if true, echo an array with the `display` header of each field before
     *                                   echoing the data.
     * @param bool $isLastQueryInSeries if true, switch back to MySQL buffered query mode after echoing the last row.
     * @param array $params validated parameter values from @see validateRawDataParams().
     * @param XDUser $user the user making the request.
     * @param \CCR\Logger $logger used to log the database request.
     * @param bool $reachedOffset if true, the requested offset row has been already been reached so don't keep
     *                            checking for it, instead just echo all rows. Otherwise, keep checking for the
     *                            offset row and only start echoing rows once it is reached.
     * @param int $i the number of rows iterated so far plus one — used to keep track of whether the offset has been
     *               reached and when to flush.
     * @param int $offset the number of rows to ignore before echoing.
     * @return null
     * @throws Exception if $startDate or $endDate are invalid ISO 8601 dates, if there is an error connecting to
     *                   or querying the database, or if invalid fields have been specified in the query parameters.
     */
    private static function echoRawData(
        $queryClass,
        $startDate,
        $endDate,
        $isFirstQueryInSeries,
        $isLastQueryInSeries,
        $params,
        $user,
        $logger,
        &$reachedOffset,
        &$i,
        &$offset
    ) {
        $query = new $queryClass(
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'batch'
        );
        $query = self::setRawDataQueryFilters($query, $params);
        $dataset = self::getRawBatchDataset(
            $user,
            $params,
            $query,
            $logger
        );
        $pdo = DB::factory($query->_db_profile)->handle();
        if ($isFirstQueryInSeries) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            self::echoRawDataRow($dataset->getHeader());
        }
        foreach ($dataset as $row) {
            if ($reachedOffset || $i > $offset) {
                $reachedOffset = true;
                self::echoRawDataRow($row);
            }
            if (10000 === $i) {
                ob_flush();
                flush();
                $i = 0;
                if (!$reachedOffset) {
                    $offset -= 10000;
                }
            }
            $i++;
        }
        if ($isLastQueryInSeries) {
            // Output the last chunk.
            echo "0\r\n\r\n";
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * Echo a row of raw data using chunked transfer encoding.
     *
     * @param mixed $row
     * @return null
     */
    private static function echoRawDataRow($row) {
        $chunk = json_encode($row);
        echo dechex(strlen($chunk)) . "\r\n$chunk\r\n";
    }

    /**
     * Get a raw batch dataset from the warehouse.
     *
     * @param XDUser $user
     * @param array $params validated parameter values.
     * @param \DataWarehouse\Query\RawQuery $query
     * @param \CCR\Logger
     * @return BatchDataset
     * @throws Exception if the `fields` parameter contains invalid field
     *                   aliases.
     */
    private static function getRawBatchDataset(
        $user,
        $params,
        $query,
        $logger
    ) {
        try {
            $dataset = new BatchDataset(
                $query,
                $user,
                $logger,
                $params['fields']
            );
            return $dataset;
        } catch (Exception $e) {
            if (preg_match('/Invalid fields specified/', $e->getMessage())) {
                throw new BadRequestHttpException($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Validate the `start_date` and `end_date` parameters of the given request
     * to the raw data endpoint (@see getRawData()).
     *
     * @param Request $request
     * @return array containing the validated start and end dates in Y-m-d
     *               format.
     * @throws BadRequestHttpException if the start and/or end dates are not
     *                                 provided or are not valid ISO 8601 dates
     *                                 or the end date is less than the start
     *                                 date.
     */
    private function validateRawDataDateParams($request)
    {
        $startDate = $this->getDateFromISO8601Param(
            $request,
            'start_date',
            true
        );
        $endDate = $this->getDateFromISO8601Param(
            $request,
            'end_date',
            true
        );
        if ($endDate < $startDate) {
            throw new BadRequestHttpException(
                'End date cannot be less than start date.'
            );
        }
        return [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    }

    /**
     * Given an array of realms, return an array of just the names of the
     * realms. Used for request parameter validation.
     *
     * @param array $realms array of Realm\Realm objects.
     * @return array of string realm names.
     */
    private static function getRealmNames(array $realms) {
        return array_map(
            function ($realm) {
                return $realm->getName();
            },
            $realms
        );
    }

    /**
     * Get the array of field aliases from the given request to the raw data
     * endpoint (@see getRawData()), e.g., the parameter `fields=foo,bar,baz`
     * results in `['foo', 'bar', 'baz']`.
     *
     * @param Request $request
     * @return array|null containing the field aliases parsed from the request,
     *                    if provided.
     */
    private function getRawDataFieldsArray($request)
    {
        $fields = null;
        $fieldsStr = $this->getStringParam($request, 'fields', false);
        if (!is_null($fieldsStr)) {
            $fields = explode(',', $fieldsStr);
        }
        return $fields;
    }

    /**
     * Validate the optional `filters` parameter of the given request to the
     * raw data endpoint (@see getRawData()), e.g., the parameter
     * `filters[foo]=bar,baz` results in `['foo' => ['bar', 'baz']]`.
     *
     * @param Request $request
     * @param array $queryDescripters the set of dimensions the user is
     *                                authorized to see based on their assigned
     *                                ACLs.
     * @return array whose keys are the validated filter keys (they must be
     *               valid dimensions the user is authorized to see) and whose
     *               values are arrays of the provided string values.
     * @throws BadRequestHttpException if any of the filter keys are invalid
     *                                 dimension names.
     */
    private function validateRawDataFiltersParams($request, $queryDescripters)
    {
        $filters = null;
        $filtersParam = $request->get('filters');
        if (!is_null($filtersParam)) {
            $filters = [];
            foreach ($filtersParam as $filterKey => $filterValuesStr) {
                $filters[$filterKey] = $this->validateRawDataFilterParam(
                    $queryDescripters,
                    $filterKey,
                    $filterValuesStr
                );
            }
        }
        return $filters;
    }

    /**
     * Given a raw data query and a mapping of dimension names to possible
     * values, set the query to filter out records whose value for the given
     * dimension does not match any of the provided values.
     *
     * @param \DataWarehouse\Query\RawQuery $query
     * @param array $params containing a `filters` key whose value is an
     *                      associative array of dimensions and dimension
     *                      values.
     * @return \DataWarehouse\Query\RawQuery the query with the filters
     *                                       applied.
     */
    private static function setRawDataQueryFilters($query, $params)
    {
        if (is_array($params['filters']) && count($params['filters']) > 0) {
            $f = new stdClass();
            $f->{'data'} = [];
            foreach ($params['filters'] as $dimension => $values) {
                foreach ($values as $value) {
                    $f->{'data'}[] = (object) [
                        'id' => "$dimension=$value",
                        'value_id' => $value,
                        'dimension_id' => $dimension,
                        'checked' => 1,
                    ];
                }
            }
            $query->setFilters($f);
        }
        return $query;
    }

    /**
     * Validate a specific filter from the `filters` parameter of a request to
     * the raw data endpoint (@see getRawData()), and return the parsed array
     * of values for that filter (e.g., `foo,bar,baz` becomes `['foo', 'bar',
     * 'baz']`).
     *
     * @param Request $request
     * @param array $queryDescripters the set of dimensions the user is
     *                                authorized to see based on their assigned
     *                                ACLs.
     * @param string $filterKey the label of a dimension.
     * @param string $filerValuesStr a comma-separated string.
     * @return array
     * @throws BadRequestHttpException if the filter key is an invalid
     *                                 dimension name.
     */
    private function validateRawDataFilterParam(
        $queryDescripters,
        $filterKey,
        $filterValuesStr
    ) {
        if (!in_array($filterKey, array_keys($queryDescripters))) {
            throw new BadRequestHttpException(
                'Invalid filter key \'' . $filterKey . '\'.'
            );
        }
        $filterValuesArray = explode(',', $filterValuesStr);
        return $filterValuesArray;
    }
}
