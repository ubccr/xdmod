<?php

declare(strict_types=1);

namespace Access\Controller\InternalDashboard;

use Access\Controller\BaseController;
use Configuration\XdmodConfiguration;
use Exception;
use Log\Summary;
use Models\Services\Acls;
use Models\Services\Realms;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;

/**
 *
 */
class SummaryController extends BaseController
{

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/ui_data/summary3.php', methods: ['GET'])]
    public function summary3(Request $request): Response
    {
        return $this->getCharts($request);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard/controllers/summary.php')]
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'get_config':
                return $this->getConfig($request);
            case 'get_portlets':
                return $this->getPortlets($request);
            default:
                throw new NotFoundHttpException('Unknown Operation Provided');
        }
    }

    /**
     * @throws Exception
     */
    #[Route('{prefix}/summary/configs', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getConfig(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $config = XdmodConfiguration::assocArrayFactory(
            'internal_dashboard.json',
            CONFIG_DIR
        );

        $summaries = [];

        foreach ($config['summary'] as $summary) {

            // Add an empty config if none is found.
            if (!isset($summary['config'])) {
                $summary['config'] = [];
            }

            // Add log config.
            if ($summary['class'] === 'XDMoD.Log.TabPanel') {
                $logList = [];

                foreach ($config['logs'] as $log) {
                    $logSummary = Summary::factory($log['ident']);

                    if ($logSummary->getProcessStartRowId() === null) {
                        continue;
                    }

                    $logList[] = [
                        'id' => $log['ident'] . '-log-panel',
                        'ident' => $log['ident'],
                        'title' => $log['title'],
                    ];
                }

                $summary['config']['logConfigList'] = $logList;
            }

            $summaries[] = $summary;
        }

        return $this->json([
            'success' => true,
            'response' => $summaries,
            'count' => count($summaries)
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('{prefix}/summary/portlets', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getPortlets(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $config = XdmodConfiguration::assocArrayFactory(
            'internal_dashboard.json',
            CONFIG_DIR
        );

        $portlets = [];

        foreach ($config['portlets'] as $portlet) {

            // Add an empty config if none is found.
            if (!isset($portlet['config'])) {
                $portlet['config'] = [];
            }

            $portlets[] = $portlet;
        }

        // Add log portlets.
        foreach ($config['logs'] as $log) {
            $logSummary = Summary::factory($log['ident'], true);

            if ($logSummary->getProcessStartRowId() === null) {
                continue;
            }

            $portlets[] = [
                'class' => 'XDMoD.Log.SummaryPortlet',
                'config' => [
                    'ident' => $log['ident'],
                    'title' => $log['title'],
                    'linkPath' => [
                        'log-tab-panel',
                        $log['ident'] . '-log-panel',
                    ],
                ],
            ];
        }

        return $this->json([
            'success' => true,
            'response' => $portlets,
            'count' => count($portlets)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/summary/charts', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function getCharts(Request $request): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            $user = XDUser::getPublicUser();
        } else {
            $user = XDUser::getUserByUserName($user->getUserIdentifier());
        }

        $debugLevel = abs($this->getIntParam($request, 'debug_level', false, 0));
        $startDate = $this->getStringParam($request, 'start_date', true);
        $endDate = $this->getStringParam($request, 'end_date', true);
        $aggregationUnit = lcfirst($this->getStringParam($request, 'aggregation_unit', false, 'auto'));
        $rawFilters = $this->getStringParam($request, 'filters');
        $publicUser = $this->getBooleanParam($request, 'public_user');

        $rawParameters = [];
        if (isset($rawFilters)) {
            $filters = json_decode($rawFilters);
            foreach ($filters->data as $filter) {
                $key = sprintf('%s_filter', $filter->dimension_id);
                $valueId = $filter->value_id;
                if (!isset($rawParameters[$key])) {
                    $rawParameters[$key] = $valueId;
                } else {
                    $rawParameters[$key] .= ',' . $valueId;
                }
            }
        }

        $enabledRealms = Realms::getEnabledRealms();
        if (in_array('Jobs', $enabledRealms)) {
            $query_descripter = new \User\Elements\QueryDescripter('Jobs', 'none');

            // This try/catch block is intended to replace the "Base table or
            // view not found: 1146 Table 'modw_aggregates.jobfact_by_day'
            // doesn't exist" error message with something more informative for
            // Open XDMoD users.

            try {
                $query = new \DataWarehouse\Query\AggregateQuery(
                    'Jobs',
                    $aggregationUnit,
                    $startDate,
                    $endDate,
                    'none',
                    'all',
                    $query_descripter->pullQueryParameters($rawParameters)
                );

                // this is used later on down the function.
                $result = $query->execute();
            } catch (PDOException $e) {
                if ($e->getCode() === '42S02' && strpos($e->getMessage(), 'modw_aggregates.jobfact_by_') !== false) {
                    $msg = 'Aggregate table not found, have you ingested your data?';
                    throw new Exception($msg);
                } else {
                    throw $e;
                }
            }
        }

        $mostPrivilegedAcl = Acls::getMostPrivilegedAcl($user);

        $rolesConfig = \Configuration\XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR);
        $roles = $rolesConfig['roles'];

        $mostPrivilegedAclName = $mostPrivilegedAcl->getName();
        $mostPrivilegedAclSummaryCharts = $roles['default']['summary_charts'];

        if (isset($roles[$mostPrivilegedAclName]['summary_charts'])) {
            $mostPrivilegedAclSummaryCharts = $roles[$mostPrivilegedAclName]['summary_charts'];
        }

        $summaryCharts = [];
        foreach ($mostPrivilegedAclSummaryCharts as $chart) {
            $realm = $chart['data_series']['data'][0]['realm'];
            if (!in_array($realm, $enabledRealms)) {
                continue;
            }
            $chart['preset'] = true;

            $summaryCharts[] = json_encode($chart);
        }

        if (!isset($publicUser) || !$publicUser) {
            $queryStore = new \UserStorage($user, 'queries_store');
            $queries = $queryStore->get();

            if ($queries != NULL) {
                foreach ($queries as $i => $query) {
                    if (isset($query['config'])) {

                        $queryConfig = json_decode($query['config']);

                        $name = isset($query['name']) ? $query['name'] : null;

                        if (isset($name)) {
                            if (preg_match('/summary_(?P<index>\S+)/', $query['name'], $matches) > 0) {
                                $queryConfig->summary_index = $matches['index'];
                            } else {
                                $queryConfig->summary_index = $query['name'];
                            }
                        }

                        if (property_exists($queryConfig, 'summary_index')
                            && isset($queryConfig->summary_index)
                            && isset($queryConfig->featured)
                            && $queryConfig->featured
                        ) {
                            if (isset($summaryCharts[$queryConfig->summary_index])) {
                                $queryConfig->preset = true;
                            }
                            $summaryCharts[$queryConfig->summary_index] = json_encode($queryConfig);
                        }
                    }
                }
            }
        }

        foreach ($summaryCharts as $i => $summaryChart) {
            $summaryChartObject = json_decode($summaryChart);
            $summaryChartObject->index = $i;
            $summaryCharts[$i] = json_encode($summaryChartObject);
        }
        ksort($summaryCharts, SORT_STRING);

        $result['charts'] = json_encode(array_values($summaryCharts));

        return $this->json([
            'totalCount' => 1,
            'success' => true,
            'message' => '',
            'data' => [$result]
        ]);
    }
}
