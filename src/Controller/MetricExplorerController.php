<?php

namespace CCR\Controller;

use DataWarehouse;
use DataWarehouse\Access\MetricExplorer;
use DataWarehouse\Query\Exceptions\AccessDeniedException;
use DataWarehouse\Query\Exceptions\UnknownGroupByException;
use DataWarehouse\Query\TimeAggregationUnit;
use Exception;
use Models\Services\Acls;
use Models\Services\Realms;
use SessionExpiredException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;
use function xd_response\buildError;

/**
 *
 */
class MetricExplorerController extends BaseController
{
    /**
     * The identifier that is used to store 'queries' in the user profile.
     *
     * @var string
     */
    private const QUERIES_STORE = 'queries_store';

    private const DEFAULT_ERROR_MESSAGE = 'An error was encountered while attempting to process the requested authorization procedure.';



    /**
     * Retrieve all of the queries that the requesting user has currently saved.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/metrics/explorer/queries', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function getQueries(Request $request): Response
    {
        $action = 'getQueries';
        $payload = [
            'success' => false,
            'action' => $action
        ];
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $data = $queries->get();

                foreach ($data as &$query) {
                    $this->removeRoleFromQuery($user, $query);
                    $query['name'] = htmlspecialchars($query['name'], ENT_COMPAT, 'UTF-8', false);
                }

                $payload['data'] = $data;
                $payload['success'] = true;
                $statusCode = 200;
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException|HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch(Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * Retrieve a query's information by unique id for the requesting user.
     *
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    #[Route('{prefix}/metrics/explorer/queries/{queryId}', requirements: ["queryId"=>"\w+", 'prefix' => '.*'], methods: ['GET'])]
    public function getQueryByid(Request $request, string $queryId): Response
    {
        $action = 'getQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);

                $query = $queries->getById($queryId);

                if (isset($query)) {
                    $payload['data'] = $query;
                    $payload['data']['name'] = htmlspecialchars($query['name'], ENT_COMPAT, 'UTF-8', false);
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'Unable to find the query identified by the provided id: ' . $queryId;
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException|HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch(Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * Create a new query to be stored in the requesting users User Profile.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('{prefix}/metrics/explorer/queries', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function createQuery(Request $request): Response
    {
        $action = 'creatQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;
        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $data = $request->get('data');
                if ($data === null) {
                    throw new BadRequestHttpException('data is a required parameter.');
                }
                if (!is_string($data)) {
                    throw new BadRequestHttpException('Invalid value for data. Must be a(n) string.');
                }
                $data = json_decode($data, true);
                $success = $queries->insert($data) != null;
                $payload['success'] = $success;
                if ($success) {
                    $payload['success'] = true;
                    $payload['data'] = $data;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'Error creating chart. User is over the chart limit.';
                    $statusCode = 500;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException|HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * Update the query identified by the provided 'id' parameter with the
     * values of the following form params ( if provided ):
     *    - name
     *    - config
     *    - timestamp
     *
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    #[Route('{prefix}/metrics/explorer/queries/{queryId}', requirements: ["queryId"=> "\w+", 'prefix' => '.*'], methods: ['PUT', "POST"])]
    public function updateQueryById(Request $request, string $queryId): Response
    {
        $action = 'updateQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);

                $query = $queries->getById($queryId);
                if (isset($query)) {
                    $data = $request->get('data');
                    if (isset($data)) {
                        if (!is_string($data)) {
                            throw new BadRequestHttpException('Invalid value for data. Must be a(n) string.');
                        }
                        $jsonData = json_decode($data, true);
                        $name = isset($jsonData['name']) ? $jsonData['name'] : null;
                        $config = isset($jsonData['config']) ? $jsonData['config'] : null;
                        $ts = isset($jsonData['ts']) ? $jsonData['ts'] : microtime(true);
                    } else {
                        $name = $this->getStringParam($request, 'name');
                        $config = $this->getStringParam($request, 'config');
                        $ts = $this->getDateTimeFromUnixParam($request, 'ts');
                    }

                    if (isset($name)) {
                        $query['name'] = $name;
                    }
                    if (isset($config)) {
                        $query['config'] = $config;
                    }
                    if (isset($ts)) {
                        $query['ts'] = $ts;
                    }

                    $queries->upsert($queryId, $query);

                    // required for the UI to do it's thing.
                    $total = count($queries->get());

                    // make sure everything is in place for returning to the
                    // front end.
                    $payload['total'] = $total;
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException|HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch(\Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $this->json($payload, $statusCode);
    }

    /**
     *
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    #[Route('{prefix}/metrics/explorer/queries/{queryId}', requirements: ["queryId"=> "\w+", 'prefix' => '.*'], methods: ['DELETE'])]
    public function deleteQueryById(Request $request, string $queryId): Response
    {
        $action = 'deleteQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = $this->authorize($request);
            if (isset($user)) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $query = $queries->getById($queryId);

                if (isset($query)) {

                    $before = count($queries->get());
                    $after = $queries->delById($queryId);
                    $success = $before > $after;

                    // make sure everything is in place for returning to the
                    // front end.
                    $payload['success'] = $success;
                    $payload['message'] = $success ? $payload['message'] : 'There was an error removing the query identified by: ' . $queryId;

                    $statusCode = $success ? 200 : 500;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestHttpException|HttpException $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = $e->getStatusCode();
        } catch (Exception $e) {
            $payload['message'] = $e->getMessage();
            $statusCode = 500;
        }

        return $this->json($payload, $statusCode);
    }

    private function removeRoleFromQuery(XDUser $user, array &$query)
    {
        // If the query doesn't have a config, stop.
        if (!array_key_exists('config', $query)) {
            return;
        }

        // If the query config doesn't have an active role, stop.
        $queryConfig = json_decode($query['config']);
        if (!property_exists($queryConfig, 'active_role')) {
            return;
        }

        // Remove the active role from the query config.
        $activeRoleId = $queryConfig->active_role;
        unset($queryConfig->active_role);

        // Check whether or not $activeRoleId is an acl name or acl display value.
        // ( Old queries may utilize the `display` property).
        $activeRole = Acls::getAclByName($activeRoleId);
        if ($activeRole === null) {
            $activeRole = Acls::getAclByDisplay($activeRoleId);
            if ($activeRole !== null) {
                $activeRoleId = $activeRole->getName();
            }
        }
        // Convert the active role into global filters.
        MetricExplorer::convertActiveRoleToGlobalFilters($user, $activeRoleId, $queryConfig->global_filters);

        // Store the updated config in the query.
        $query['config'] = json_encode($queryConfig);
    }

    /**
     * This function is just here to allow us to support the original metric explorer html controller urls. Which
     * functioned by referencing the same url `/controllers/metric_explorer.php` and including an `operation` parameter
     * to differentiate which file to load. Specifically, this function replicates the following controller operations
     * ( including the file that this endpoint was ported from ):
     *   - `get_data`:          `html/controllers/metric_explorer/get_data.php`
     *   - `get_dimension`:     `html/controllers/metric_explorer/get_dimension.php`
     *   - `get_dw_descripter`: `html/controllers/metric_explorer/get_dw_descripter.php`
     *   - `get_filters`:       `html/controllers/metric_explorer/get_filters.php`
     *
     * @param Request $request
     * @return Response
     * @throws AccessDeniedException
     * @throws SessionExpiredException
     * @throws UnknownGroupByException
     * @throws Exception
     */
    #[Route('/controllers/metric_explorer.php', methods: ['POST', 'GET'])]
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'get_data':
                return $this->getData($request);
            case 'get_dimension':
                return $this->getDimensionValues($request);
            case 'get_dw_descripter':
                return $this->getDwDescriptors($request);
            case 'get_filters':
                return $this->getFilters($request);
            case 'get_rawdata':
                return $this->getRawData($request);
        }

        return $this->json([
            'success' => false,
            'message' => 'Unknown Operation provided.'
        ]);
    }


    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem with the processing of the get_data function.
     */
    #[Route('{prefix}/metrics/explorer/data', requirements: ['prefix' => '.*'], methods: ['POST', 'GET'])]
    public function getData(Request $request): Response
    {
        $user = $this->detectUser($request, [XDUser::INTERNAL_USER, XDUser::PUBLIC_USER]);

        $m = new \DataWarehouse\Access\MetricExplorer($_REQUEST);
        try {
            $result = $m->get_data($user);
            return new Response($result['results'], 200, $result['headers']);
        } catch (Exception $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ],
                400
            );
        }
    }


    /**
     *
     * @param Request $request
     * @return Response
     * @throws SessionExpiredException
     * @throws AccessDeniedException
     * @throws UnknownGroupByException
     */
    #[Route('{prefix}/metrics/explorer/dimension/values', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getDimensionValues(Request $request): Response
    {
        try {
            $user = $this->tokenHelper->authenticate($request, false);

            // If token authentication failed then fallback to the standard session based authentication method.
            if ($user === null) {
                $user = $this->detectUser($request, array(\XDUser::PUBLIC_USER));
            }
        } catch (Exception $e) {
            return $this->json(
                buildError(new Exception('Session Expired', 2)),
                401
            );
        }
        $this->logger->warning('User retrieved ', [$user->getUserIdentifier()]);

        $dimensionId = $this->getStringParam($request, 'dimension_id', true);
        $offset = $this->getStringParam($request ,'start');
        if (empty($offset)) {
            $offset = 0;
        }
        $limit = $this->getIntParam($request, 'limit');
        $searchText = $this->getStringParam($request, 'search_text');

        $selectedFilterIds = $this->getStringParam($request, 'selectedFilterIds', false, []);
        if (!is_array($selectedFilterIds)) {
            $selectedFilterIds = explode(',', $selectedFilterIds);
        }

        $realms = $this->getStringParam($request, 'realm', false);
        if ($realms !== null) {
            $realms = preg_split('/,\s*/', trim($realms), null, PREG_SPLIT_NO_EMPTY);
        }

        return $this->json(MetricExplorer::getDimensionValues(
            $user,
            $dimensionId,
            $realms,
            $offset,
            $limit,
            $searchText,
            $selectedFilterIds
        ));
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if unable to get the currently logged in user.
     */
    #[Route('{prefix}/metrics/explorer/get_dw_descripter',requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getDwDescriptors(Request $request): Response
    {
        try {
            $user = $this->tokenHelper->authenticate($request, false);

            // If token authentication failed then fallback to the standard session based authentication method.
            if ($user === null) {
                $user = $this->getLoggedInUser($request->getSession());
            }
        } catch (Exception $e) {
            return $this->json(
                buildError(new Exception('Session Expired', 2)),
                401
            );
        }


        $roles = $user->getAllRoles(true);

        $roleDescriptors = array();
        foreach ($roles as $activeRole) {
            $shortRole = $activeRole;
            $us_pos = strpos($shortRole, '_');
            if ($us_pos > 0) {
                $shortRole = substr($shortRole, 0, $us_pos);
            }

            if (array_key_exists($shortRole, $roleDescriptors)) {
                continue;
            }

            // If enabled, try to lookup answer in cache first.
            $cache_enabled = \xd_utilities\getConfiguration('internal', 'dw_desc_cache') === 'on';
            $cache_data_found = false;
            if ($cache_enabled) {
                $db = \CCR\DB::factory('database');
                $db->execute('create table if not exists dw_desc_cache (role char(5), response mediumtext, index (role) ) ');
                $cachedResults = $db->query('select response from dw_desc_cache where role=:role', array('role' => $shortRole));
                if (count($cachedResults) > 0) {
                    $roleDescriptors[$shortRole] = unserialize($cachedResults[0]['response']);
                    $cache_data_found = true;
                }
            }

            // If the cache was not used or was not useful, get descriptors from code.
            if (!$cache_data_found) {
                $realms = [];
                // NOTE: this variable is never utilized after being updated. can probably be removed.
                $groupByObjects = [];

                $realmObjects = Realms::getRealmObjectsForUser($user);
                $query_descriptor_realms = Acls::getQueryDescripters($user);

                foreach ($query_descriptor_realms as $query_descriptor_realm => $query_descriptor_groups) {
                    $category = DataWarehouse::getCategoryForRealm($query_descriptor_realm);
                    if ($category === null) {
                        continue;
                    }
                    $seenStats = [];

                    $realmObject = $realmObjects[$query_descriptor_realm];
                    $realmDisplay = $realmObject->getDisplay();
                    $realms[$query_descriptor_realm] = [
                        'text' => $query_descriptor_realm,
                        'category' => $realmDisplay,
                        'dimensions' => [],
                        'metrics' => [],
                    ];
                    foreach ($query_descriptor_groups as $query_descriptor_group) {
                        foreach ($query_descriptor_group as $query_descriptor) {
                            if ($query_descriptor->getDisableMenu()) {
                                continue;
                            }

                            $groupByName = $query_descriptor->getGroupByName();
                            $group_by_object = $query_descriptor->getGroupByInstance();
                            $permittedStatistics = $group_by_object->getRealm()->getStatisticIds();

                            $groupByObjects[$query_descriptor_realm . '_' . $groupByName] = [
                                'object' => $group_by_object,
                                'permittedStats' => $permittedStatistics
                            ];
                            $realms[$query_descriptor_realm]['dimensions'][$groupByName] = [
                                'text' => $groupByName == 'none' ? 'None' : $group_by_object->getName(),
                                'info' => $group_by_object->getHtmlDescription()
                            ];

                            $stats = array_diff($permittedStatistics, $seenStats);
                            if (empty($stats)) {
                                continue;
                            }

                            $statsObjects = $query_descriptor->getStatisticsClasses($stats);
                            foreach ($statsObjects as $realm_group_by_statistic => $statistic_object) {

                                if (!$statistic_object->showInMetricCatalog()) {
                                    continue;
                                }

                                $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
                                    $realm_group_by_statistic
                                );
                                $realms[$query_descriptor_realm]['metrics'][$realm_group_by_statistic] =
                                    [
                                        'text' => $statistic_object->getName(),
                                        'info' => $statistic_object->getHtmlDescription(),
                                        'std_err' => in_array($semStatId, $permittedStatistics),
                                        'hidden_groupbys' => $statistic_object->getHiddenGroupBys()
                                    ];
                                $seenStats[] = $realm_group_by_statistic;
                            }
                        }
                    }
                    $texts = [];
                    foreach ($realms[$query_descriptor_realm]['metrics'] as $key => $row) {
                        $texts[$key] = $row['text'];
                    }
                    array_multisort($texts, SORT_ASC, $realms[$query_descriptor_realm]['metrics']);
                }
                $texts = [];
                foreach ($realms as $key => $row) {
                    $texts[$key] = $row['text'];
                }
                array_multisort($texts, SORT_ASC, $realms);

                $roleDescriptors[$shortRole] = ['totalCount' => 1, 'data' => [['realms' => $realms]]];

                // Cache the results if the cache is enabled.
                if ($cache_enabled) {
                    $db->execute('insert into dw_desc_cache (role, response) values (:role, :response)', [
                        'role' => $shortRole,
                        'response' => serialize($roleDescriptors[$shortRole])
                    ]);
                }
            }
        }

        $combinedRealmDescriptors = [];
        foreach ($roleDescriptors as $roleDescriptor) {
            foreach ($roleDescriptor['data'][0]['realms'] as $realm => $realmDescriptor) {
                if (!isset($combinedRealmDescriptors[$realm])) {
                    $combinedRealmDescriptors[$realm] = [
                        'metrics' => [],
                        'dimensions' => [],
                        'text' => $realmDescriptor['text'],
                        'category' => $realmDescriptor['category'],
                    ];
                }

                $combinedRealmDescriptors[$realm]['metrics'] += $realmDescriptor['metrics'];
                $combinedRealmDescriptors[$realm]['dimensions'] += $realmDescriptor['dimensions'];
            }
        }

        return $this->json([
            'totalCount' => 1,
            'data' => [
                [
                    'realms' => $combinedRealmDescriptors
                ]
            ]
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if unable to retrieve the currently logged in user.
     */
    #[Route('{prefix}/metrics/explorer/filters', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getFilters(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $returnData = [];

        try {
            $user = $this->getLoggedInUser($request->getSession());

            $userProfile = $user->getProfile();
            $filters = $userProfile->fetchValue('filters');
            if ($filters != null) {
                $filtersArray = json_decode($filters);
                $returnData = [
                    'totalCount' => count($filtersArray),
                    'message' => 'success',
                    'data' => $filtersArray,
                    'success' => true
                ];
            } else {
                $returnData = [
                    'totalCount' => 0,
                    'message' => 'success',
                    'data' => [],
                    'success' => true
                ];
            }

        } catch (SessionExpiredException $see) {
            // TODO: Refactor generic catch block below to handle specific exceptions,
            //       which would allow this block to be removed.
            throw $see;
        } catch (Exception $ex) {
            $returnData = [
                'totalCount' => 0,
                'message' => $ex->getMessage(),
                'data' => [],
                'success' => false
            ];
        }

        return $this->json($returnData);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem retrieving a user for the request.
     */
    #[Route('{prefix}/metrics/explorer/raw_data', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getRawData(Request $request): Response
    {
        $user = $this->detectUser($request, array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER));

        try {
            $config = [];
            foreach ($request->request->all() as $key => $value) {
                $config[$key] = $value;
            }

            $configParam = $this->getStringParam($request, 'config');
            if (!empty($configParam)) {
                $configJson = json_decode($configParam, true);
                $config = array_merge($config, $configJson);
            }

            $requestedFormat = $this->getStringParam($request, 'format');
            $format = DataWarehouse\ExportBuilder::validateFormat($requestedFormat, 'jsonstore', ['jsonstore']);
            $inline = $this->getBooleanParam($request, 'inline', false, true);
            $dataSetId = $this->getStringParam($request, 'datasetId', true);
            $datapoint = $this->getStringParam($request, 'datapoint', true);
            $showContextMenu = $this->getBooleanParam($request, 'showContextMenu', false, false);
            $requestedStartDate = $this->getDateFromISO8601Param($request, 'start_date', true);
            $requestedStartDateTs = date_timestamp_get($requestedStartDate);

            $requestedEndDate = $this->getDateFromISO8601Param($request, 'end_date', true);
            $requestedEndDateTs = date_timestamp_get($requestedEndDate);


            if ($requestedStartDateTs > $requestedEndDateTs) {
                throw new BadRequestHttpException('End date must be greater than or equal to start date');
            }

            $startDate = $requestedStartDate->format('Y-m-d');
            $endDate = $requestedEndDate->format('Y-m-d');
            $isTimeseries = $this->getBooleanParam($request, 'timeseries', false, false);

            if ($isTimeseries) {
                // For timeseries data the date range is set to be only the data-point that was
                // selected. Therefore we adjust the start and end date appropriately
                $aggregationUnit = $this->getStringParam($request, 'aggregation_unit', false, 'auto');
                $time_period = TimeAggregationUnit::deriveAggregationUnitName($aggregationUnit, $startDate, $endDate);
                $time_point = $datapoint / 1000;

                list($startDate, $endDate) = TimeAggregationUnit::getRawTimePeriod($time_point, $time_period);
            }

            $title = $this->getStringParam($request, 'title');

            $requestedGlobalFilters = $this->getStringParam($request, 'global_filters');

            $globalFilters = (object)['data' => [], 'total' => 0];
            if (!empty($requestedGlobalFilters)) {
                $globalFiltersDecoded = urldecode($requestedGlobalFilters);
                $globalFiltersJson = json_decode($globalFiltersDecoded, true);
                $this->logger->warning('Global Filters Decoded', [var_export($globalFiltersDecoded, true)]);
                $this->logger->warning('Global FIlters Json', [json_encode($globalFiltersJson)]);

                if (!empty($globalFiltersJson) && isset($globalFiltersJson['data']) && is_array($globalFiltersJson['data'])) {
                    foreach ($globalFiltersJson['data'] as $datum) {
                        $globalFilters->data[] = (object)$datum;
                        $globalFilters->total++;
                    }
                }
            }

            $dataset_classname = '\DataWarehouse\Data\SimpleDataset';

            try {
                $all_data_series = $this->getDataSeries($request);
            } catch (Exception $e) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $e->getMessage()
                    ],
                    400
                );
            }

            // find requested dataset.
            $data_description = null;
            foreach ($all_data_series as $data_description_index => $data_series) {
                // NOTE: this only works if the id's are not floats.
                if ("{$data_series->id}" == "$dataSetId") {
                    $data_description = $data_series;
                    break;
                }
            }

            if ($data_description === null) {
                return $this->json(
                    [
                        'success'=> false,
                        'message' => 'Invalid data_series provided.'
                    ],
                    400
                );
            }

            // Check that the user has at least one role authorized to view this data.
            MetricExplorer::checkDataAccess(
                $user,
                $data_description->realm,
                'none',
                $data_description->metric
            );

            if ($format === 'jsonstore') {

                $query_classname = '\\DataWarehouse\\Query\\' . $data_description->realm . '\\RawData';

                $query = new $query_classname(
                    $data_description->realm,
                    'day',
                    $startDate,
                    $endDate,
                    null,
                    $data_description->metric,
                    []
                );

                $groupedRoleParameters = [];
                foreach ($globalFilters->data as $global_filter) {
                    if ($global_filter->checked == 1) {
                        if (
                            !isset(
                                $groupedRoleParameters[$global_filter->dimension_id]
                            )
                        ) {
                            $groupedRoleParameters[$global_filter->dimension_id]
                                = [];
                        }

                        $groupedRoleParameters[$global_filter->dimension_id][]
                            = $global_filter->value_id;
                    }
                }

                $query->setMultipleRoleParameters($user->getAllRoles(), $user);

                $query->setRoleParameters($groupedRoleParameters);

                $query->setFilters($data_description->filters);

                $dataset = new $dataset_classname($query);

                $filterOpts = array('options' => array('default' => null, 'min_range' => 0));

                $limit = null;
                $limitParam = $this->getStringParam($request, 'limit');
                if (!empty($limitParam)) {
                    try {
                        $limit = $this->getIntParam($request, 'limit');
                        if ($limit < 0) {
                            $limit = null;
                        }
                    } catch (Exception $e) {
                        // NOOP
                    }
                }

                $offset = 0;
                $offsetParam = $this->getStringParam($request, 'start');
                if (!empty($offsetParam)) {
                    try {
                        $offset = intval($offsetParam);
                    } catch (Exception $e) {
                        // NOOP
                    }
                }
                $offset = max($offset, 0);
                $totalCount = $dataset->getTotalPossibleCount();

                $ret = array();

                // As a small optimization only compute the total count the first time (ie when the offset is 0)
                if ($offset === null or $offset == 0) {
                    $privquery = new $query_classname(
                        $data_description->realm,
                        'day',
                        $startDate,
                        $endDate,
                        null,
                        $data_description->metric,
                        array()
                    );
                    $privquery->setRoleParameters($groupedRoleParameters);
                    $privquery->setFilters($data_description->filters);

                    $query = $privquery->getQueryString();

                    $privdataset = new $dataset_classname($privquery);

                    $ret['totalAvailable'] = $privdataset->getTotalPossibleCount();
                }
                // This is so that the behavior of this endpoint matches get_rawdata.php
                if ($offsetParam === null && !empty($limit)) {
                    $offset = null;
                }
                $ret['data'] = $dataset->getResults($limit, $offset,false, false, null, null, $this->logger);
                $ret['totalCount'] = $totalCount;

                return $this->json($ret);
            }
        } catch (SessionExpiredException $see) {
            // TODO: Refactor generic catch block below to handle specific exceptions,
            //       which would allow this block to be removed.
            return $this->json(buildError($see));
        } catch (Exception $ex) {
            return $this->json(buildError($ex));
        }

        return $this->json([
            'success' => false,
            'message' => 'An unexpected error has occurred. Please contact support.'
        ]);
    }


    private function getDataSeries(Request $request): array
    {
        $requestedDataSeries = null;
        try {
            $dataSeriesParam = $this->getStringParam($request, 'data_series', false, '[]');
            $requestedDataSeries = json_decode(urldecode($dataSeriesParam), true);
        } catch (Exception $e) {
            // NOOP
        }
        if (is_array($requestedDataSeries) && isset($requestedDataSeries['data']) && is_array($requestedDataSeries['data'])) {
            return $this->getDataSeriesFromArray($requestedDataSeries);
        } else {
            return $this->getDataSeriesFromJsonString($this->getStringParam($request, 'data_series'));
        }
    }

    private function getDataSeriesFromArray(array $dataSeries): array
    {
        $results = [];
        foreach ($dataSeries['data'] as $datum) {
            $y = (object)$datum;

            for ($i = 0, $b = count($y->filters['data']); $i < $b; $i++) {
                $y->filters['data'][$i] = (object)$y->filters['data'][$i];
            }

            $y->filters = (object)$y->filters;

            // Set values of new attribs for backward compatibility.
            if (empty($y->line_type)) {
                $y->line_type = 'Solid';
            }

            if (
                empty($y->line_width)
                || !is_numeric($y->line_width)
            ) {
                $y->line_width = 2;
            }

            if (empty($y->color)) {
                $y->color = 'auto';
            }

            if (empty($y->shadow)) {
                $y->shadow = false;
            }

            $results[] = $y;
        }
        return $results;
    }

    /**
     *
     * @param string $dataSeries
     * @return array
     */
    private function getDataSeriesFromJsonString(string $dataSeries): array
    {
        $jsonDataSeries = json_decode(urldecode($dataSeries));
        if (null === $jsonDataSeries) {
            throw new BadRequestHttpException('Invalid data_series specified');
        }
        foreach ($jsonDataSeries as &$y) {
            // Set values of new attribs for backward compatibility.
            if (empty($y->line_type)) {
                $y->line_type = 'Solid';
            }

            if (empty($y->line_width) || !is_numeric($y->line_width)) {
                $y->line_width = 2;
            }

            if (empty($y->color)) {
                $y->color = 'auto';
            }

            if (empty($y->shadow)) {
                $y->shadow = false;
            }
        }

        return $jsonDataSeries;
    }
}
