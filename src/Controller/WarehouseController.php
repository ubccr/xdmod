<?php

declare(strict_types=1);

namespace Access\Controller;

use CCR\Log;
use CCR\Logger;
use DataWarehouse\Access\MetricExplorer;
use DataWarehouse\Data\BatchDataset;
use DataWarehouse\Data\RawDataset;
use DataWarehouse\Export\RealmManager;
use DataWarehouse\Query\Exceptions\AccessDeniedException;
use DataWarehouse\Query\Exceptions\UnavailableTimeAggregationUnitException;
use DataWarehouse\Query\Exceptions\UnknownGroupByException;
use DataWarehouse\Query\RawQuery;
use Exception;
use Models\Services\Acls;
use Models\Services\Parameters;
use Models\Services\Realms;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use UserStorage;
use XDUser;
use function xd_response\buildError;

/**
 *
 * @Route("/warehouse")
 */
class WarehouseController extends BaseController
{
    public const MAX_RECORDS = 50;

    /**
     * The identifier that is used to store the search history data in the user
     * profile.
     *
     * @var string
     */
    private const HISTORY_STORE_KEY = 'searchhistory';

    /**
     * The types of nodes that this ControllerProvider understands how to deal with.
     *
     * @var array
     */
    private $supportedTypes = [
        \DataWarehouse\Query\RawQueryTypes::ACCOUNTING =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::ACCOUNTING,
                'dtype' => 'infoid',
                'text' => 'Accounting data',
                'url' => '/warehouse/search/jobs/accounting',
                'documentation' => 'Shows information about the job that was obtained from the resource manager.
                                  This includes timing information such as the start and end time of the job as
                                  well as administrative information such as the user that submitted the job and
                                  the account that was charged.',
                'type' => 'keyvaluedata',
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT,
                'dtype' => 'infoid',
                'text' => 'Job script',
                'url' => '/warehouse/search/jobs/jobscript',
                'documentation' => 'Shows the job batch script that was passed to the resource manager when the
                                    job was submitted. The script is displayed verbatim.',
                'type' => 'utf8-text',
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::EXECUTABLE =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::EXECUTABLE,
                'dtype' => 'infoid',
                'text' => 'Executable information',
                'url' => '/warehouse/search/jobs/executable',
                'documentation' => 'Shows information about the processes that were run on the compute nodes during
                                    the job. This information includes the names of the various processes and may
                                    contain information about the linked libraries, loaded modules and process
                                    environment.',
                'type' => 'nested',
                'leaf' => true],
        \DataWarehouse\Query\RawQueryTypes::PEERS =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::PEERS,
                'dtype' => 'infoid',
                'text' => 'Peers',
                'url' => '/warehouse/search/jobs/peers',
                'documentation' => 'Shows the list of other HPC jobs that ran concurrently using the same shared hardware resources.',
                'type' => 'ganttchart',
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS,
                'dtype' => 'infoid',
                'text' => 'Summary metrics',
                'url' => '/warehouse/search/jobs/metrics',
                'documentation' => 'shows a table with the performance metrics collected during
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
                ',
                'type' => 'metrics',
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS,
                'dtype' => 'infoid',
                'text' => 'Detailed metrics',
                'url' => '/warehouse/search/jobs/detailedmetrics',
                'documentation' => 'shows the data generated by the job summarization software. Please
                                    consult the relevant job summarization software documentation for details
                                    about these metrics.',
                'type' => 'detailedmetrics',
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::ANALYTICS =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::ANALYTICS,
                'dtype' => 'infoid',
                'text' => 'Job analytics',
                'url' => '/warehouse/search/jobs/analytics',
                'documentation' => 'Click the help icon on each plot to show the description of the analytic',
                'type' => 'analytics',
                'hidden' => true,
                'leaf' => true
            ],
        \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS,
                'dtype' => 'infoid',
                'text' => 'Timeseries',
                'leaf' => false
            ],
        \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE =>
            [
                'infoid' => \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE,
                'dtype' => 'infoid',
                'text' => 'VM State/Events',
                'documentation' => 'Show the lifecycle of a VM. Green signifies when a VM is active and red signifies when a VM is stopped.',
                'url' => '/warehouse/search/cloud/vmstate',
                'type' => 'vmstate',
                'leaf' => true
            ]
    ];

    /**
     * @Route("/quick_filters", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws UnavailableTimeAggregationUnitException
     * @throws UnknownGroupByException
     * @throws Exception if unable to find an XDMoD User by the currently logged in Users username.
     */
    public function getQuickFilters(Request $request): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            $user = XDUser::getPublicUser();
        } else {
            $user = XDUser::getUserByUserName($user->getUserIdentifier());
        }

        // Check whether multiple service providers are supported or not.
        try {
            $multipleProvidersSupported = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
        } catch (Exception $e) {
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

        return $this->json([
            'success' => true,
            'results' => [
                'dimensionNames' => $dimensionIdsToNames,
                'filters' => $filters
            ]
        ]);
    }

    /**
     * @Route("/realms", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getRealms(Request $request): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            $user = XDUser::getPublicUser();
        } else {
            $user = XDUser::getUserByUserName($user->getUserIdentifier());
        }

        // Get the realms for the query group and the user's active role.
        $realms = Realms::getRealmsForUser($user);

        // Return the realms found.
        return $this->json([
            'success' => true,
            'results' => $realms,
        ]);
    }

    /**
     * @Route("/dimensions", methods={"GET"})
     * @throws Exception if a XDMoD user cannot be found for the currently logged in users username.
     */
    public function getDimensions(Request $request): Response
    {
        $user = $this->authorize($request);

        $realm = $this->getStringParam($request, 'realm');

        // Get the dimensions for the query group, realm, and user's active role.
        try {
            $groupBys = Acls::getQueryDescripters($user, $realm);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


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

        return $this->json([
            'success' => true,
            'results' => $dimensionsToReturn
        ]);
    }

    /**
     * @Route("/dimensions/{dimension}", methods={"GET"}, requirements={"dimension": "\w+"})
     *
     * @param Request $request
     * @param string $dimension
     *
     * @return Response
     *
     * @throws Exception
     */
    public function getDimensionValues(Request $request, string $dimension): Response
    {
        $user = $this->authorize($request);

        // Get Parameter values for feeding to MetricExplorer::getDimensionValues
        $offset = $this->getIntParam($request, 'offset', false, 0);
        $limit = $this->getIntParam($request, 'limit');
        $searchText = $this->getStringParam($request, 'search_text');

        $realm = $this->getStringParam($request, 'realm');
        $realms = null;
        if (null !== $realm) {
            $realms = preg_split('/,\s*/', trim($realm), -1, PREG_SPLIT_NO_EMPTY);
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

        return $this->json([
            'success' => true,
            'results' => $dimensionValuesData
        ]);
    }

    /**
     * @Route("/search/history", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function searchHistory(Request $request): Response
    {
        $user = $this->authorize($request);
        $action = 'searchHistory';

        $nodeId = $this->getIntParam($request, 'nodeid');
        $tsId = $this->getStringParam($request, 'tsid');
        $infoId = $this->getIntParam($request, 'infoid');
        $jobId = $this->getIntParam($request, 'jobid');
        $recordId = $this->getIntParam($request, 'recordid');
        $realm = $this->getStringParam($request, 'realm');
        $title = $this->getStringParam($request, 'title');

        if ($nodeId !== null && $tsId !== null && $infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobNodeTimeSeriesRequest($user, $realm, $jobId, $tsId, $nodeId, $infoId, $action);
        } elseif ($tsId !== null && $infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobTimeSeriesRequest($user, $realm, $jobId, $tsId, $infoId, $action);
        } elseif ($infoId !== null && $jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobRequest($user, $realm, $jobId, $infoId, $action);
        } elseif ($jobId !== null && $recordId !== null && $realm !== null) {
            $result = $this->processJobByJobId($user, $realm, $jobId, $action);
        } elseif ($recordId !== null && $realm !== null) {
            $result = $this->getHistoryById($request, $recordId);
        } elseif ($realm !== null && $title !== null) {
            $result = $this->getHistoryByTitle($user, $realm, $title);
        } elseif ($realm !== null) {
            $result = $this->processHistoryRequest($user, $realm, $action);
        } else {
            $result = $this->processHistoryDefaultRealmRequest($user, $action);
        }

        return $result;
    }

    /**
     * @Route("/search/history", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function createHistory(Request $request): Response
    {
        $action = 'createHistory';
        $user = $this->authorize($request);

        $realm = $this->getStringParam($request, 'realm', true);
        $recordId = $this->getIntParam($request, 'recordid');

        $history = $this->getUserStore($user, $realm);
        $decoded = $this->getSearchParams($request);

        $created = is_numeric($recordId)
            ? $history->upsert($recordId, $decoded)
            : $history->insert($decoded);

        if ($created === null) {
            throw new BadRequestHttpException(
                'Create request will exceed record storage restrictions ' .
                '(record count limited to ' . self::MAX_RECORDS . ')'
            );
        }

        if (!isset($created['dtype'])) {
            $created['dtype'] = 'recordid';
        }


        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'total' => count($created),
                'results' => $created
            ]
        );
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $tsId
     * @param int $nodeId
     * @param int $infoId
     * @return Response
     */
    private function processJobNodeTimeSeriesRequest(
        XDUser $user,
        string $realm,
        ?int    $jobId,
        string $tsId,
        int    $nodeId,
        int    $infoId
    ): Response
    {

        if ($infoId != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS) {
            throw new BadRequestHttpException("Node $infoId is a leaf");
        }

        $infoClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoClass();

        $result = [];
        foreach ($info->getJobTimeseriesMetricNodeMeta($user, $jobId, $tsId, $nodeId) as $cpu) {
            $cpu['url'] = '/warehouse/search/jobs/timeseries';
            $cpu['type'] = 'timeseries';
            $cpu['dtype'] = 'cpuid';
            $result[] = $cpu;
        }

        return $this->json(['success' => true, 'results' => $result]);

    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $tsId
     * @param int $infoId
     * @return Response
     */
    private function processJobTimeSeriesRequest(
        XDUser $user,
        string $realm,
        ?int    $jobId,
        string $tsId,
        int    $infoId
    ): Response
    {

        if ($infoId != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS) {
            throw new BadRequestHttpException("Node $infoId is a leaf");
        }

        $infoClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoClass();

        $result = [];
        foreach ($info->getJobTimeseriesMetricMeta($user, $jobId, $tsId) as $node) {
            $node['url'] = '/warehouse/search/jobs/timeseries';
            $node['type'] = 'timeseries';
            $node['dtype'] = 'node';
            $result[] = $node;
        }

        return $this->json(['success' => true, 'results' => $result]);
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $infoId
     * @return Response
     */
    private function processJobRequest(
        XDUser $user,
        string $realm,
        ?int    $jobId,
        int $infoId
    ): Response
    {


        switch ($infoId) {
            case '' . \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE:
                $infoClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
                $info = new $infoClass();

                $result = [];
                foreach ($info->getJobTimeseriesMetaData($user, $jobId) as $tsid) {
                    $tsid['url'] = '/warehouse/search/jobs/vmstate';
                    $tsid['type'] = 'timeseries';
                    $tsid['dtype'] = 'tsid';
                    $result[] = $tsid;
                }
                return $this->json(['success' => true, 'results' => $result]);
            case '' . \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS:
                $infoClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
                $info = new $infoClass();

                $result = [];
                foreach ($info->getJobTimeseriesMetaData($user, $jobId) as $tsid) {
                    $tsid['url'] = '/warehouse/search/jobs/timeseries';
                    $tsid['type'] = 'timeseries';
                    $tsid['dtype'] = 'tsid';
                    $result[] = $tsid;
                }
                return $this->json(['success' => true, 'results' => $result]);
            default:
                throw new BadRequestHttpException('Node is a leaf');
        }
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $action
     * @return Response
     */
    private function processJobByJobId(
        XDUser $user,
        string $realm,
        int    $jobId,
        string $action
    ): Response
    {

        $JobMetaDataClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $JobMetaDataClass();
        $jobMetaData = $info->getJobMetadata($user, $jobId);

        $data = array_intersect_key($this->supportedTypes, $jobMetaData);

        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'results' => array_values($data)
            ]
        );
    }

    /**
     * @Route("/search/history/{id}", methods={"GET"}, requirements={"id": "\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     *
     * @throws Exception
     */
    public function getHistoryById(Request $request, int $id): Response
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

        return $this->json($record);
    }


    /**
     * @param XDUser $user
     * @param string $realm
     * @param string $title
     * @return Response
     */
    private function getHistoryByTitle(XDUser $user, string $realm, string $title): Response
    {
        $action = 'getHistoryByTitle';
        $userHistory = $this->getUserStore($user, $realm);
        $searches = $userHistory->get();
        foreach ($searches as $search) {
            $text = isset($search['text']) ? $search['text'] : null;
            if ($text == $title) {
                if (!isset($search['dtype'])) {
                    $search['dtype'] = 'recordid';
                }
                return $this->json(
                    [
                        'action' => $action,
                        'success' => true,
                        'data' => $search
                    ]
                );
            }
        }

        throw new NotFoundHttpException('Unable to find a history entry with the provided title.');
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param string $action
     * @return Response
     */
    private function processHistoryRequest(XDUser $user, string $realm, string $action): Response
    {
        $history = $this->getUserStore($user, $realm);
        $output = $history->get();

        $results = [];
        foreach ($output as $item) {
            $results[] = [
                'text' => $item['text'],
                'dtype' => 'recordid',
                'recordid' => $item['recordid'],
                'searchterms' => $item['searchterms']
            ];
        }

        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'results' => $results,
                'total' => count($results)
            ]
        );
    }

    /**
     * @param XDUser $user
     * @param string $action
     * @return Response
     */
    private function processHistoryDefaultRealmRequest(XDUser $user, string $action): Response
    {
        $results = [];

        foreach (\DataWarehouse\Access\RawData::getRawDataRealms($user) as $realmConfig) {
            $history = $this->getUserStore($user, $realmConfig['name']);
            $records = $history->get();
            if (!empty($records)) {
                $results[] = [
                    'dtype' => 'realm',
                    'realm' => $realmConfig['name'],
                    'text' => $realmConfig['display']
                ];
            }
        }

        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'results' => $results
            ]
        );
    }


    /**
     * @Route("/search/history/{id}", methods={"POST", "PUT"}, requirements={"id": "\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws Exception
     */
    public function updateHistory(Request $request, int $id): Response
    {

        $action = 'updateHistory';
        $user = $this->authorize($request);
        $data = $this->getSearchParams($request);
        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);

        $result = $history->upsert($id, $data);

        if (!isset($result['dtype'])) {
            $result['dtype'] = 'recordid';
        }

        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'total' => 1,
                'results' => $result
            ]
        );
    }

    /**
     * @Route("/search/history/{id}", methods={"DELETE"}, requirements={"id": "\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws Exception
     */
    public function deleteHistory(Request $request, int $id): Response
    {
        $this->logger->debug('Delete History Called', [$id]);
        $user = $this->authorize($request);
        $this->logger->debug('User Found', [$user->getUserIdentifier()]);
        $action = 'deleteHistory';

        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);
        $deleted = $history->delById($id);

        return $this->json(
            [
                'success' => true,
                'action' => $action,
                'total' => $deleted
            ]
        );
    }

    /**
     * @Route("/search/history", methods={"DELETE"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function deleteAllHistory(Request $request): Response
    {
        $user = $this->authorize($request);

        $action = 'deleteAllHistory';

        $realm = $this->getStringParam($request, 'realm', true);

        $history = $this->getUserStore($user, $realm);
        $history->del();

        return $this->json(
            [
                'success' => true,
                'action' => $action
            ]
        );
    }


    /**
     * @Route("/search/jobs", methods={"GET"})
     * @param Request $request
     * @return void
     * @throws AccessDeniedException if the user executing this request does not have access to the provided realm.
     * @throws Exception if a user record is not found in the database that corresponds to the current user's username.
     */
    public function searchJobs(Request $request): Response
    {

        $user = $this->authorize($request);

        $realm = $this->getStringParam($request, 'realm', true);
        $params = $this->getStringParam($request, 'params', true);

        $params = json_decode($params, true);
        if ((isset($params['resource_id']) && isset($params['local_job_id'])) || isset($params['jobref'])) {
            return $this->getJobByPrimaryKey($user, $realm, $params);
        } else {
            $startDate = $this->getStringParam($request, 'start_date', true);
            $endDate = $this->getStringParam($request, 'end_date', true);

            return $this->processJobSearch($request, $user, $realm, $startDate, $endDate, 'searchJobs');
        }

    }

    /**
     * @Route(
     *     "/search/{realms}/{action}",
     *     methods={"GET", "POST"},
     *     requirements={
     *         "action": "([\w|_|-])+",
     *         "realms": "cloud|jobs"
     *     }
     *)
     * @param Request $request
     * @param string $action
     * @return Response
     * @throws Exception if a user record is not found in the database that corresponds to the current user's username.
     */
    public function searchJobsByAction(Request $request, string $action): Response
    {
        $user = $this->authorize($request);
        $actionName = 'searchJobsByAction';

        $realm = ucfirst($this->getStringParam($request, 'realms'));

        $jobId = $this->getIntParam($request, 'jobid');
        return $this->processJobSearchByAction($request, $user, $action, $realm, $jobId, $actionName);
    }

    /**
     * @Route("/aggregatedata", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws AccessDeniedException
     */
    public function getAggregateData(Request $request): Response
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
            throw new AccessDeniedHttpException('access denied to ' . json_encode($forbiddenStats));
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
        return $this->json(
            [
                'results' => $results,
                'total' => $dataset->getTotalPossibleCount(),
                'success' => true
            ]
        );
    }

    /**
     * @param Request $request
     * @param XDUser $user
     * @param string $action
     * @param string $realm
     * @param int $jobId
     * @param string $actionName
     * @return Response
     * @throws NotFoundException if the provided jobId has no data in the provided realm.
     * @throws AccessDeniedException if the provided user does not have access to the specified realm.
     * @throws Exception
     */
    private function processJobSearchByAction(
        Request $request,
        XDUser  $user,
        string  $action,
        string  $realm,
        ?int    $jobId,
        string  $actionName
    ): Response
    {

        switch ($action) {
            case 'accounting':
            case 'jobscript':
            case 'analysis':
            case 'metrics':
            case 'analytics':
                $realm = $this->getStringParam($request, 'realm', true);
                $results = $this->getJobData($user, $realm, $jobId, $action);
                break;
            case 'peers':
                $start = $this->getIntParam($request, 'start', true);
                $limit = $this->getIntParam($request, 'limit', true);

                if ($jobId === null) {
                    throw new BadRequestHttpException('Invalid value for realm. Must be a(n) string.');
                }

                $realm = $this->getStringParam($request, 'realm', true);

                $results = $this->getJobPeers($user, $realm, $jobId, $start, $limit);
                break;
            case 'executable':
                $realm = $this->getStringParam($request, 'realm', true);
                $results = $this->getJobExecutable($user, $realm, $jobId, $action, $actionName);
                break;
            case 'detailedmetrics':
                $realm = $this->getStringParam($request, 'realm', true);
                $results = $this->getJobSummary($user, $realm, $jobId, $action, $actionName);
                break;
            case 'timeseries':
                $tsId = $this->getStringParam($request, 'tsid', true);
                $nodeId = $this->getIntParam($request, 'nodeid');
                $cpuId = $this->getIntParam($request, 'cpuid');
                $realm = $this->getStringParam($request, 'realm', true);
                $results = $this->getJobTimeSeriesData($request, $user, $realm, $jobId, $tsId, $nodeId, $cpuId);
                break;
            case 'vmstate':
                $realm = $this->getStringParam($request, 'realm', true);
                $results = $this->getJobTimeSeriesData($request, $user, $realm, $jobId, null, null, null);
                break;
            default:
                $results = $this->json(
                    [
                        'success' => false,
                        'action' => $actionName,
                        'message' => "Unable to process the requested operation. Unsupported action $action."
                    ],
                    400
                );
                break;
        }

        return $results;
    }

    /**
     * @param XDUser $user
     * @param $realm
     * @param $jobId
     * @return Response
     * @throws Exception
     */
    private function getJobExecutable(XDUser $user, $realm, $jobId): Response
    {
        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $QueryClass();

        $execInfo = $query->getJobExecutableInfo($user, $jobId);

        if (count($execInfo) === 0) {
            throw new \Exception(
                "Executable information unavailable for $realm $jobId",
                500
            );
        }

        return $this->json(
            $this->arraytostore(
                json_decode(json_encode($execInfo), true)
            )
        );
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @return Response
     */
    private function getJobSummary(XDUser $user, string $realm, $jobId): Response
    {
        $queryClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $queryClass();

        $jobSummary = $query->getJobSummary($user, $jobId);

        $result = [];

        // Really this should be a recursive function!
        foreach ($jobSummary as $key => $val) {
            $name = "$key";
            if (is_array($val)) {
                if (array_key_exists('avg', $val) && !is_array($val['avg'])) {
                    $result[] = array_merge(
                        [
                            'name' => $name,
                            'leaf' => true
                        ],
                        $this->encodeFloatArray($val)
                    );
                } else {
                    $l1data = ['name' => $name, 'avg' => '', 'expanded' => 'true', 'children' => []];
                    foreach ($val as $subkey => $subVal) {
                        $subName = "$subkey";
                        if (is_array($subVal)) {
                            if (array_key_exists('avg', $subVal) && !is_array($subVal['avg'])) {
                                $l1data['children'][] = array_merge(
                                    [
                                        'name' => $subName,
                                        'leaf' => true
                                    ],
                                    $this->encodeFloatArray($subVal)
                                );
                            } else {
                                $l2data = ['name' => $subName, 'avg' => '', 'expanded' => 'true', 'children' => []];

                                foreach ($subVal as $subSubKey => $subSubVal) {
                                    $subSubName = "$subSubKey";
                                    if (is_array($subSubVal)) {
                                        if (array_key_exists('avg', $subSubVal) && !is_array($subSubVal['avg'])) {
                                            $l2data['children'][] = array_merge(
                                                [
                                                    'name' => $subSubName,
                                                    'leaf' => true
                                                ],
                                                $this->encodeFloatArray($subSubVal)
                                            );
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

        return $this->json($result);
    }

    /**
     * @param Request $request
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $tsId
     * @param int $nodeId
     * @param int $cpuId
     * @return Response
     * @throws NotFoundException
     */
    private function getJobTimeSeriesData(
        Request $request,
        XDUser  $user,
        string  $realm,
        ?int     $jobId,
        ?string  $tsId,
        ?int     $nodeId,
        ?int     $cpuId
    ): Response
    {
        $infoClass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoClass();

        $results = $info->getJobTimeseriesData($user, $jobId, $tsId, $nodeId, $cpuId, $this->logger);
        $this->logger->debug('Results from getJobTimeseriesData', [$results, get_class($info), $realm]);
        if (count($results) === 0) {
            throw new NotFoundHttpException('The requested resource does not exist');
        }

        $format = $this->getStringParam($request, 'format', false, 'json');

        if (!in_array($format, ['json', 'png', 'svg', 'pdf', 'csv'])) {
            throw new BadRequestHttpException('Unsupported format type.');
        }
        $subject = $results['schema']['source'] ?? '';
        $title = $results['schema']['description'] ?? '';

        switch ($format) {
            case 'png':
            case 'pdf':
            case 'svg':
                $exportConfig = [
                    'width' => $this->getIntParam($request, 'width', false, 916),
                    'height' => $this->getIntParam($request, 'height', false, 484),
                    'scale' => floatval($this->getStringParam($request, 'scale', false, '1')),
                    'font_size' => $this->getIntParam($request, 'font_size', false, 3),
                    'show_title' => $this->getStringParam($request, 'show_title', false, 'y') === 'y',
                    'fileMetadata' => [
                        'author' => $user->getFormalName(),
                        'subject' => 'Timeseries data for ' . $subject,
                        'title' => $title
                    ]
                ];
                $response = $this->chartImageResponse($results, $format, $exportConfig);
                break;
            case 'csv':
                $response = $this->chartDataResponse($results);
                break;
            case 'json':
            default:
                $response = $this->json(['success' => true, 'data' => [$results]]);
                break;
        }

        return $response;
    }

    /**
     * Encode a chart data series in CSV data and send as an attachment
     *
     * @param array $data
     * @return Response
     */
    private function chartDataResponse(array $data): Response
    {
        $filename = tempnam(sys_get_temp_dir(), 'xdmod');
        $fp = fopen($filename, 'w');

        $columns = ['Time'];
        $numberOfDataPoints = 0;
        foreach ($data['series'] as $series) {
            if (isset($series['dtype'])) {
                $columns[] = $series['name'];
                if ($numberOfDataPoints === 0) {
                    $numberOfDataPoints = count($series['data']);
                }
            }
        }
        fputcsv($fp, $columns);

        for ($i = 0; $i < $numberOfDataPoints; $i++) {
            $outline = [];
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
     * @param array $data
     * @param string $type
     * @param array $settings
     * @return Response
     */
    private function chartImageResponse(array $data, string $type, array $settings): Response
    {
        // Enable plot marker only if a single point is present in the data series' plot data.
        // Otherwise plot the data with a line.

        $markerEnabled = false;

        // check the series array passed in from the overall data array:
        foreach ($data['series'] as $series) {
            // if the series array contains any data array with exactly one element, enable markers
            // (a dot for each series' element) so that the plotted data can be seen:
            if (isset($series['data']) && count($series['data']) == 1) {
                $markerEnabled = true;
                break;
            }
        }

        $axisTitleFontSize = ($settings['font_size'] + 12) . 'px';
        $axisLabelFontSize = ($settings['font_size'] + 11) . 'px';
        $mainTitleFontSize = ($settings['font_size'] + 16) . 'px';

        $lineWidth = 1 + $settings['scale'];

        $timezone = $data['schema']['timezone'] ?? '';
        $units = $data['schema']['units'] ?? '';
        $source = $data['schema']['source'] ?? '';
        $description = $data['schema']['description'] ?? '';

        $chartConfig = [
            'colors' => ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
                '#f28f43', '#77a1e5', '#c42525', '#a6c96a'
            ],
            'series' => $data['series'],
            'xAxis' => [
                'type' => 'datetime',
                'minTickInterval' => 1000,
                'labels' => [
                    'style' => [
                        'fontWeight' => 'normal',
                        'fontSize' => $axisLabelFontSize
                    ],
                ],
                'lineWidth' => $lineWidth,
                'title' => [
                    'style' => [
                        'fontWeight' => 'bold',
                        'fontSize' => $axisTitleFontSize,
                        'color' => '#5078a0'
                    ],
                    'text' => 'Time (' . $timezone . ')'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'style' => [
                        'fontWeight' => 'bold',
                        'fontSize' => $axisTitleFontSize,
                        'color' => '#5078a0'
                    ],
                    'text' => $units
                ],
                'lineWidth' => $lineWidth,
                'labels' => [
                    'style' => [
                        'fontWeight' => 'normal',
                        'fontSize' => $axisLabelFontSize
                    ],
                ],
                'min' => 0.0
            ],
            'legend' => [
                'enabled' => false
            ],
            'plotOptions' => [
                'line' => [
                    'lineWidth' => $lineWidth,
                    'marker' => [
                        'enabled' => $markerEnabled
                    ]
                ]
            ],
            'credits' => [
                'text' => $source . '. Powered by XDMoD/Highcharts',
                'href' => ''
            ],
            'exporting' => [
                'enabled' => false
            ],
            'title' => [
                'style' => [
                    'color' => '#444b6e',
                    'fontSize' => $mainTitleFontSize
                ],

                'text' => $settings['show_title'] ? $description : null
            ]
        ];

        $globalConfig = [
            'timezone' => $timezone
        ];

        $chartImage = \xd_charting\exportHighchart(
            $chartConfig,
            $settings['width'],
            $settings['height'],
            $settings['scale'],
            $type,
            $globalConfig,
            $settings['fileMetadata']
        );

        $chartFilename = $settings['fileMetadata']['title'] . '.' . $type;
        $mimeOverride = $type == 'svg' ? 'image/svg+xml' : null;

        return $this->sendAttachment($chartImage, $chartFilename, $mimeOverride);
    }

    /**
     * Helper function that creates a Response object that will result in a file download on the client.
     *
     * @param string $content
     * @param string $filename
     * @param string|null $mimetype
     * @return Response
     */
    protected function sendAttachment(string $content, string $filename, string $mimetype = null): Response
    {
        if ($mimetype === null) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->buffer($content);
        }

        $response = new Response(
            $content,
            Response::HTTP_OK,
            ['Content-Type' => $mimetype]
        );
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            )
        );

        return $response;
    }


    /**
     * @param array $in
     * @return array
     */
    private function encodeFloatArray(array $in): array
    {
        $out = [];
        foreach ($in as $key => $value) {
            if (is_float($value) && is_nan($value)) {
                $out[$key] = 'NaN';
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * @param array $values
     * @return array[]
     */
    private function arraytostore(array $values): array
    {
        return [['key' => '.', 'value' => '', 'expanded' => true, 'children' => $this->atosrecurse($values)]];
    }

    /**
     * @param array $values
     * @return array
     */
    private function atosrecurse(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                if (count($value) > 0) {
                    $result[] = ['key' => "$key", 'value' => '', 'expanded' => true, 'children' => $this->atosrecurse($value)];
                }
            } else {
                $result[] = ['key' => "$key", 'value' => $value, 'leaf' => true];
            }
        }
        return $result;
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param int $start
     * @param int $limit
     * @return Response
     * @throws AccessDeniedException if the provided user does not have access to the specified realm.
     * @throws NotFoundException if the provided jobId has no data in the provided realm.
     */
    private function getJobPeers(XDUser $user, string $realm, $jobId, int $start, int $limit): Response
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
                    'text' => $thisjob['resource'] . '-' . $thisjob['local_job_id']
                )
            )
        );

        $dataset = $this->getJobDataSet($user, $realm, $jobId, 'peers');
        foreach ($dataset->getResults() as $index => $jobpeer) {
            if (($index >= $start) && ($index < ($start + $limit))) {
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

        return $this->json([
            'success' => true,
            'data' => [$result],
            'total' => count($dataset->getResults())
        ]);
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $action
     * @return Response
     * @throws AccessDeniedException
     */
    private function getJobData(XDUser $user, string $realm, int $jobId, string $action): Response
    {
        $dataSet = $this->getJobDataSet($user, $realm, $jobId, $action);

        return $this->json(['data' => $dataSet->export(), 'success' => true]);
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param int $jobId
     * @param string $action
     * @return RawDataset
     * @throws AccessDeniedException if the provided user does not have access to the specified realm.
     */
    private function getJobDataSet(XDUser $user, string $realm, $jobId, string $action): RawDataset
    {
        if (!\DataWarehouse\Access\RawData::realmExists($user, $realm)) {
            throw new AccessDeniedException();
        }

        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $params = array('primary_key' => $jobId);
        $query = new $QueryClass($params, $action);

        $allRoles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allRoles, $user);

        $dataSet = new RawDataset($query, $user);

        if (!$dataSet->hasResults()) {
            $privilegedQuery = new $QueryClass($params, $action);
            $results = $privilegedQuery->execute(1);
            if ($results['count'] != 0) {
                throw new AccessDeniedException();
            }
        }
        return $dataSet;
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @param array $searchParams
     * @return Response
     * @throws AccessDeniedException if the provided user does not have access to the provided realm.
     */
    private function getJobByPrimaryKey(XDUser $user, string $realm, array $searchParams): Response
    {
        if (!\DataWarehouse\Access\RawData::realmExists($user, $realm)) {
            throw new AccessDeniedException();
        }

        if (isset($searchParams['jobref']) && is_numeric($searchParams['jobref'])) {
            $params = [
                'primary_key' => $searchParams['jobref']
            ];
        } elseif (isset($searchParams['resource_id']) && isset($searchParams['local_job_id'])) {
            $params = [
                'resource_id' => $searchParams['resource_id'],
                'job_identifier' => $searchParams['local_job_id']
            ];
        } else {
            throw new BadRequestHttpException('invalid search parameters');
        }

        $QueryClass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $query = new $QueryClass($params, 'brief');

        $allRoles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allRoles, $user);

        $dataSet = new RawDataset($query, $user);

        $results = array();
        foreach ($dataSet->getResults() as $result) {
            $result['text'] = $result['resource'] . '-' . $result['local_job_id'];
            $result['dtype'] = 'jobid';
            $results[] = $result;
        }

        if (!$dataSet->hasResults()) {
            $privilegedQuery = new $QueryClass($params, 'brief');
            $privilegedResults = $privilegedQuery->execute(1);

            if ($privilegedResults['count'] != 0) {
                throw new AccessDeniedHttpException();
            }
        }

        return $this->json(
            [
                'success' => true,
                'results' => $results,
                'totalCount' => count($results)
            ]
        );
    }

    /**
     * @param Request $request
     * @param XDUser $user
     * @param string $realm
     * @param string $startDate
     * @param string $endDate
     * @param string $action
     * @return Response
     * @throws Exception
     */
    private function processJobSearch(Request $request, XDUser $user, string $realm, string $startDate, string $endDate, string $action): Response
    {
        $queryDescripters = Acls::getQueryDescripters($user, $realm);

        if (empty($queryDescripters)) {
            throw new BadRequestHttpException('Invalid realm', null);
        }

        $offset = $this->getIntParam($request, 'start', true);
        $limit = $this->getIntParam($request, 'limit', true);

        $searchParams = json_decode(
            $this->getStringParam($request, 'params', true),
            true
        );

        if ($searchParams === null || !is_array($searchParams)) {
            throw new BadRequestHttpException('params parameter must be valid JSON');
        }

        $params = array_intersect_key($searchParams, $queryDescripters);

        if (count($params) != count($searchParams)) {
            throw new BadRequestHttpException('Invalid search parameters specified in params object');
        } else {
            $QueryClass = "\\DataWarehouse\\Query\\$realm\\RawData";
            $query = new $QueryClass($realm, 'day', $startDate, $endDate, null, '', []);

            $allRoles = $user->getAllRoles();
            $query->setMultipleRoleParameters($allRoles, $user);

            if (!empty($params)) {
                $query->setRoleParameters($params);
            }

            $dataSet = new \DataWarehouse\Data\SimpleDataset($query);
            $raw = $dataSet->getResults($limit, $offset);

            $data = [];
            foreach ($raw as $row) {
                $resource = $row['resource'];
                $localJobId = $row['local_job_id'];

                $row['text'] = "$resource-$localJobId";
                $row['dtype'] = 'jobid';
                $data[] = $row;
            }

            $total = $dataSet->getTotalPossibleCount();

            $results = $this->json(
                [
                    'success' => true,
                    'action' => $action,
                    'results' => $data,
                    'totalCount' => $total
                ]
            );

            if ($total === 0) {
                // No data returned for the query. This could be because the roleParameters
                // caused the data to be filtered. In this case we will return access-denied.
                // need to rerun the query without the role params to see if any results come back.
                // note the data for the priviledged query is not returned to the user.

                $privQuery = new $QueryClass('day', $startDate, $endDate, null, '', []);
                $privQuery->setRoleParameters($params);

                $privDataSet = new \DataWarehouse\Data\SimpleDataset($privQuery, 1, 0);
                $privResults = $privDataSet->getResults();
                if (count($privResults) != 0) {
                    $results = $this->json(
                        [
                            'success' => false,
                            'action' => $action,
                            'message' => 'Unable to complete the requested operation. Access Denied.'
                        ],
                        401
                    );
                }
            }
        }

        return $results;
    }

    /**
     * @param XDUser $user
     * @param string $realm
     * @return UserStorage
     */
    private function getUserStore(XDUser $user, string $realm): UserStorage
    {
        $container = implode(
            '-',
            array_filter([
                self::HISTORY_STORE_KEY,
                strtoupper($realm)
            ])
        );
        return new UserStorage($user, $container);
    }

    /**
     * retrieve and sanitize the search history parameters for a request
     * throws and exception if the parameters are missing.
     * @param Request $request The request.
     * @return array decoded search parameters.
     * @throws MissingMandatoryParametersException If the required parameters are absent.
     */
    private function getSearchParams(Request $request): array
    {
        $data = $this->getStringParam($request, 'data');
        if (!isset($data)) {
            throw new BadRequestHttpException('missing required data parameter');
        }

        $decoded = json_decode($data, true);

        if ($decoded === null || !isset($decoded['text'])) {
            throw new BadRequestHttpException('Malformed request. Expected \'data.text\' to be present.');
        }

        $decoded['text'] = htmlspecialchars($decoded['text'], ENT_COMPAT | ENT_HTML5);

        return $decoded;
    }

    /**
     * @Route("/raw-data", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getRawData(Request $request): Response
    {
        $user = parent::authenticateToken($request);
        if ($user === null) {
            return $this->json(buildError(new Exception('No Token Provided.')), 401, [
                'WWW-Authenticate' => 'Bearer'
            ]);
        }
        try {
            $params = $this->validateRawDataParams($request, $user);
        } catch (HttpException $e) {
            return $this->json(buildError($e), $e->getStatusCode());
        }

        $query = $this->getRawDataQuery($params);
        $logger = $this->getRawDataLogger();
        $limit = $this->getConfiguredRawDataLimit();
        $dataset = $this->getRawBatchDataset(
            $user,
            $params,
            $query,
            $logger,
            $limit
        );
        $data = $this->parseRawBatchDataset($dataset);
        return $this->json([
            'success' => true,
            'fields' => $dataset->getHeader(),
            'data' => $data
        ]);
    }

    /**
     * Endpoint to get the maximum number of rows that can be returned in a
     * single response from the raw data endpoint (@see getRawData()). Requires
     * API token authorization.
     *
     * No parameters.
     *
     * If successful, the response will include the following keys:
     * - success: true.
     * - data: integer value obtained from the 'rest_raw_row_limit' setting in
     *         the 'datawarehouse' section of the portal_settings.ini
     *         configuration file.
     * @Route("/raw-data/limit", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @throws Exception if there is no setting for 'rest_raw_row_limit' in
     *                   the 'datawarehouse' section of portal_settings.ini.
     */
    public function getRawDataLimit(Request $request): JsonResponse
    {
        parent::authenticateToken($request);

        $limit = $this->getConfiguredRawDataLimit();

        return $this->json([
            'success' => true,
            'data' => $limit
        ]);
    }

    /**
     * Validate the parameters of the request from the given user to the raw
     * data endpoint (@see getRawData()).
     *
     * @param Request $request
     * @param XDUser $user
     * @return array of validated parameter values.
     * @throws BadRequestException if any of the parameters are invalid.
     * @throws Exception if there is a problem retrieving the query descripters.
     */
    private function validateRawDataParams($request, $user): array
    {
        $params = [];
        list(
            $params['start_date'], $params['end_date']
            ) = $this->validateRawDataDateParams($request);

        $params['realm'] = $this->getStringParam($request, 'realm', true);

        $queryDescripters = Acls::getQueryDescripters($user, $params['realm']);
        if (empty($queryDescripters)) {
            throw new BadRequestHttpException('Invalid realm.', null);
        }
        $params['fields'] = $this->getRawDataFieldsArray($request);
        $params['filters'] = $this->validateRawDataFiltersParams(
            $request,
            $queryDescripters
        );
        $params['offset'] = $this->getIntParam($request, 'offset', false, 0);
        if ($params['offset'] < 0) {
            throw new BadRequestHttpException('Offset must be non-negative.', null);
        }
        return $params;
    }

    /**
     * Get the corresponding query for a request to get raw data with the given
     * parameters.
     *
     * @param array $params validated parameters
     *                      (@see validateRawDataParams()).
     * @return RawQuery
     */
    private function getRawDataQuery(array $params): RawQuery
    {
        $realmManager = new RealmManager();
        $className = $realmManager->getRawDataQueryClass($params['realm']);
        $query = new $className(
            [
                'start_date' => $params['start_date'],
                'end_date' => $params['end_date']
            ],
            'batch'
        );
        return $this->setRawDataQueryFilters($query, $params);
    }

    /**
     * Generate a database logger for the raw data queries.
     *
     * @return \CCR\Logger
     * @throws Exception if there's a problem instantiating the Logger
     */
    private function getRawDataLogger(): Logger
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
     * Get the value configured in the portal settings for the maximum number
     * of rows that can be returned in a single response from the raw data
     * endpoint.
     *
     * @return int
     * @throws Exception if the 'datawarehouse' section and/or the
     *                   'rest_raw_row_limit' option have not been set in the
     *                   portal configuration.
     */
    private function getConfiguredRawDataLimit(): int
    {
        return intval(\xd_utilities\getConfiguration(
            'datawarehouse',
            'rest_raw_row_limit'
        ));
    }

    /**
     * Get a raw batch dataset from the warehouse.
     *
     * @param XDUser $user
     * @param array $params validated parameter values.
     * @param RawQuery $query
     * @param \CCR\Logger
     * @param int $limit maximum number of rows to get.
     * @return BatchDataset
     * @throws Exception if the 'fields' parameter contains invalid field
     *                   aliases.
     */
    private function getRawBatchDataset($user, $params, $query, $logger, $limit): BatchDataset {
        try {
            $dataset = new BatchDataset(
                $query,
                $user,
                $logger,
                $params['fields'],
                $limit,
                $params['offset']
            );
            return $dataset;
        } catch (Exception $e) {
            if (preg_match('/Invalid fields specified/', $e->getMessage())) {
                throw new BadRequestHttpException($e->getMessage(), null);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Parse the given dataset into an array of records.
     *
     * @param BatchDataset $dataset
     * @return array of records obtained by iterating over the dataset.
     */
    private function parseRawBatchDataset(BatchDataset $dataset): array
    {
        $data = [];
        foreach ($dataset as $record) {
            $data[] = $record;
        }
        return $data;
    }

    /**
     * Validate the 'start_date' and 'end_date' parameters of the given request
     * to the raw data endpoint (@see getRawData()).
     *
     * @param Request $request
     * @return array containing the validated start and end dates in Y-m-d
     *               format.
     * @throws BadRequestException if the start and/or end dates are not
     *                             provided or are not valid ISO 8601 dates or
     *                             the end date is less than the start date.
     */
    private function validateRawDataDateParams(Request $request): array
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
            throw new BadRequestHttpException('End date cannot be less than start date.', null);
        }
        return [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    }

    /**
     * Get the array of field aliases from the given request to the raw data
     * endpoint (@see getRawData()), e.g., the parameter 'fields=foo,bar,baz'
     * results in ['foo', 'bar', 'baz'].
     *
     * @param Request $request
     * @return array|null containing the field aliases parsed from the request,
     *                    if provided.
     */
    private function getRawDataFieldsArray(Request $request): ?array
    {
        $fields = null;
        $fieldsStr = $this->getStringParam($request, 'fields', false);
        if (!is_null($fieldsStr)) {
            $fields = explode(',', $fieldsStr);
        }
        return $fields;
    }

    /**
     * Validate the optional 'filters' parameter of the given request to the
     * raw data endpoint (@see getRawData()), e.g., the parameter
     * 'filters[foo]=bar,baz' results in ['foo' => ['bar', 'baz']].
     *
     * @param Request $request
     * @param array $queryDescripters the set of dimensions the user is
     *                                authorized to see based on their assigned
     *                                ACLs.
     * @return array|null whose keys are the validated filter keys (they must be
     *               valid dimensions the user is authorized to see) and whose
     *               values are arrays of the provided string values.
     * @throws BadRequestException if any of the filter keys are invalid
     *                             dimension names.
     */
    private function validateRawDataFiltersParams(Request $request, array $queryDescripters): ?array
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
     * @param RawQuery $query
     * @param array $params containing a 'filters' key whose value is an
     *                      associative array of dimensions and dimension
     *                      values.
     * @return RawQuery the query with the filters
     *                                       applied.
     */
    private function setRawDataQueryFilters(RawQuery $query, array $params): RawQuery
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
     * Validate a specific filter from the 'filters' parameter of a request to
     * the raw data endpoint (@see getRawData()), and return the parsed array
     * of values for that filter (e.g., 'foo,bar,baz' becomes ['foo', 'bar',
     * 'baz']).
     *
     * @param array $queryDescripters the set of dimensions the user is
     *                                authorized to see based on their assigned
     *                                ACLs.
     * @param string $filterKey       the label of a dimension.
     * @param string $filterValuesStr a comma-separated string.
     * @return array
     * @throws BadRequestException if the filter key is an invalid dimension
     *                             name.
     */
    private function validateRawDataFilterParam(
        array $queryDescripters,
        string $filterKey,
        string $filterValuesStr
    ): array {
        if (!in_array($filterKey, array_keys($queryDescripters))) {
            throw new BadRequestHttpException('Invalid filter key \'' . $filterKey . '\'.', null);
        }
        return explode(',', $filterValuesStr);
    }

}
