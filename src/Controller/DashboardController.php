<?php

declare(strict_types=1);

namespace CCR\Controller;

use CCR\ColumnLayout;
use Configuration\XdmodConfiguration;
use Exception;
use Models\Services\Acls;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use XDUser;
use function xd_response\buildError;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
#[Route('{prefix}/dashboard', requirements: ['prefix' => '.*'])]
class DashboardController extends BaseController
{
    /**
     * The individual dashboard components have a namespace prefix to simplify
     * the implementation of the algorithm that determines which
     * components to display. There are two sources of configuration data for
     * the components. The roles configuration file and the user configuration
     * (in the database). The user configuration only contains chart components.
     * The user configuration is handled via the "Show in Summary tab" checkbox
     * in the metric explorer.
     *
     * Non-chart components and the full-width components are defined in the roles
     * configuration file and are not overrideable.
     *
     * Chart components are handled as follows:
     * - All user charts with "show in summary tab" checked will be displayed
     * - If a user chart has the same name as a chart in the role configuration
     *   then its settings will be used in place of the role chart.
     */
    private const TOP_COMPONENT = 't.';
    private const CHART_COMPONENT = 'c.';
    private const NON_CHART_COMPONENT = 'p.';

    /**
     * @param Request $request
     * @return Response
     * @throws Exception if the user for this request does not have a user id.
     */
    #[Route('/components', methods: ['GET'])]
    public function getComponents(Request $request): Response
    {
        $user = $this->getXDUser($request->getSession());

        $dashboardComponents = [];

        $mostPrivilegedAcl = Acls::getMostPrivilegedAcl($user)->getName();

        $layout = $this->getLayout($user);

        $roleConfig = \Configuration\XdmodConfiguration::assocArrayFactory(
            'roles.json',
            CONFIG_DIR,
            null,
            ['config_variables' => $this->getConfigVariables($user)]
        );

        $presets = $roleConfig['roles'][$mostPrivilegedAcl];

        if (isset($presets['dashboard_components'])) {

            foreach($presets['dashboard_components'] as $component) {

                $componentType = self::NON_CHART_COMPONENT;

                if (isset($component['region']) && $component['region'] === 'top') {
                    $componentType = self::TOP_COMPONENT;
                    $chartLocation = $componentType . $component['name'];
                    $column = -1;
                } else {
                    if ($component['type'] === 'xdmod-dash-chart-cmp') {
                        $componentType = self::CHART_COMPONENT;
                        $component['config']['name'] = $component['name'];
                        $component['config']['chart']['featured'] = true;
                    }

                    $defaultLayout = null;
                    if (isset($component['location']) && isset($component['location']['row']) && isset($component['location']['column'])) {
                        $defaultLayout = array($component['location']['row'], $component['location']['column']);
                    }

                    list($chartLocation, $column) = $layout->getLocation($componentType . $component['name'], $defaultLayout);
                }

                $dashboardComponents[$chartLocation] = array(
                    'name' => $componentType . $component['name'],
                    'type' => $component['type'],
                    'config' => isset($component['config']) ? $component['config'] : array(),
                    'column' => $column
                );
            }
        }

        if ($user->isPublicUser() === false)
        {
            $queryStore = new \UserStorage($user, 'queries_store');
            $queries = $queryStore->get();

            if ($queries != null) {
                foreach ($queries as $query) {
                    if (!isset($query['config']) || !isset($query['name'])) {
                        continue;
                    }

                    $queryConfig = json_decode($query['config']);

                    if (!isset($queryConfig->featured) || !$queryConfig->featured) {
                        continue;
                    }

                    $name = self::CHART_COMPONENT . $query['name'];

                    list($chartLocation, $column) = $layout->getLocation($name);

                    $dashboardComponents[$chartLocation] = [
                        'name' => $name,
                        'type' => 'xdmod-dash-chart-cmp',
                        'config' => [
                            'name' => $query['name'],
                            'chart' => $queryConfig
                        ],
                        'column' => $column
                    ];
                }
            }
        }

        ksort($dashboardComponents);

        return $this->json([
            'success' => true,
            'total' => count($dashboardComponents),
            'portalConfig' => ['columns' => $layout->getColumnCount()],
            'data' => array_values($dashboardComponents)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws BadRequestHttpException if the data parameter is not present and does not contain a layout and columns
     * property.
     * @throws Exception if there is a problem authorizing the current user.
     */
    #[Route('/layout', methods: ['POST'])]
    public function setLayout(Request $request): Response
    {
        $user = $this->authorize($request);

        $content = json_decode($this->getStringParam($request, 'data', true), true);

        if ($content === null || !isset($content['layout']) || !isset($content['columns'])) {
            throw new BadRequestHttpException('Invalid data parameter');
        }

        $storage = new \UserStorage($user, 'summary_layout');

        return $this->json([
            'success' => true,
            'total' => 1,
            'data' => $storage->upsert(0, $content)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem authorizing the current user.
     */
    #[Route('/layout', methods: ['DELETE'])]
    public function resetLayout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->authorize($request);

        $storage = new \UserStorage($user, 'summary_layout');

        $storage->del();

        return $this->json([
            'success' => true,
            'total' => 1
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem authorizing the current user.
     */
    #[Route('/rolereport', methods: ['GET'])]
    public function getRoleReport(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->authorize($request);

        $role = $user->getMostPrivilegedRole()->getName();
        $report_id_suffix = 'autogenerated-' . $role;
        $report_id = $user->getUserID() . '-' . $report_id_suffix;
        $userReport = null;
        $rm = new \XDReportManager($user);
        $reports = $rm->fetchReportTable();
        foreach ($reports as &$report) {
            if ($report['report_id'] === $report_id) {
                $userReport = $report;
            }
        }
        if (is_null($userReport)){
            $availTemplates = $rm::enumerateReportTemplates([$role], 'Dashboard Tab Report');
            if (empty($availTemplates)) {
                throw new NotFoundHttpException("No dashboard tab report template available for $role");
            }

            $template = $rm::retrieveReportTemplate($user, $availTemplates[0]['id']);
            $template->buildReportFromTemplate($_REQUEST, $report_id_suffix);
            $reports = $rm->fetchReportTable();
            foreach ($reports as &$report) {
                if ($report['report_id'] === $report_id) {
                    $userReport = $report;
                }
            }
        }
        $data = $rm->loadReportData($userReport['report_id']);
        $count = 0;
        foreach($data['queue'] as $queue) {
            $chart_id = explode('&', $queue['chart_id']);
            $chart_id_parsed = array();
            foreach($chart_id as $value) {
                list($key, $value) = explode('=', $value);
                $key = urldecode($key);
                $value = urldecode($value);
                $json = json_decode($value, true);

                if ($key === 'timeseries') {
                    $value = $value === 'y' || $value === 'true';
                } elseif ($json !== null) {
                    $value = $json;
                }
                $chart_id_parsed[$key] = $value;
            }
            $data['queue'][$count]['chart_id'] = $chart_id_parsed;
            $count++;
        }
        return $this->json([
            'success' => true,
            'total' => count($data),
            'data' => $data
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem authorizing the current user.
     */
    #[Route('/savedchartsreports', methods: ['GET'])]
    public function getSavedChartReports(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->authorize($request);
        // fetch charts
        $queries = new \UserStorage($user, 'queries_store');
        $data = $queries->get();
        foreach ($data as &$query) {
            $query['name'] = htmlspecialchars($query['name'], ENT_COMPAT, 'UTF-8', false);
            $query['type'] = 'Chart';
        }
        // fetch reports
        $rm = new \XDReportManager($user);
        $reports = $rm->fetchReportTable();
        foreach ($reports as &$report) {
            $tmp = [];
            $tmp['type'] = 'Report';
            $tmp['name'] = $report['report_name'];
            $tmp['chart_count'] = $report['chart_count'];
            $tmp['charts_per_page'] = $report['charts_per_page'];
            $tmp['creation_method'] = $report['creation_method'];
            $tmp['report_delivery'] = $report['report_delivery'];
            $tmp['report_format'] = $report['report_format'];
            $tmp['report_id'] = $report['report_id'];
            $tmp['report_name'] = $report['report_name'];
            $tmp['report_schedule'] = $report['report_schedule'];
            $tmp['report_title'] = $report['report_title'];
            $tmp['ts'] = $report['last_modified'];
            $tmp['config'] = $report['report_id'];
            $data[] = $tmp;
        }
        return $this->json([
            'success' => true,
            'total' => count($data),
            'data' => $data
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/viewedUserTour', methods: ['POST'])]
    public function setViewedUserTour(Request $request): Response
    {
        $user = $this->authorize($request);
        $viewedTour = $this->getIntParam($request, 'viewedTour', true);

        if (!in_array($viewedTour, [0,1])) {
            throw new BadRequestHttpException('Invalid data parameter');
        }

        $storage = new \UserStorage($user, 'viewed_user_tour');

        return $this->json([
            'success' => true,
            'total' => 1,
            'msg' => $storage->upsert(0, ['viewedTour' => $viewedTour])
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/viewedUserTour', methods: ['GET'])]
    public function getViewedUserTour(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->authorize($request);
        $storage = new \UserStorage($user, 'viewed_user_tour');
        return $this->json([
            'success' => true,
            'total' => 1,
            'data' => $storage->get()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/statistics', methods: ['GET'])]
    public function getStatistics(Request $request): Response
    {
        try {
            $user = $this->authorize($request);
        } catch (Exception $e) {
            $user = XDUser::getPublicUser();
        }

        $aggregationUnit = $request->get('aggregation_unit', 'auto');

        $startDate = $this->getStringParam($request, 'start_date', true);
        $endDate = $this->getStringParam($request, 'end_date', true);

        $this->checkDateRange($startDate, $endDate);

        $this->logger->debug('Date Range is Copacetic!');
        // This try/catch block is intended to replace the "Base table or
        // view not found: 1146 Table 'modw_aggregates.jobfact_by_day'
        // doesn't exist" error message with something more informative for
        // Open XDMoD users.
        try {
            $this->logger->debug('Running Aggregate Query!');
            $query = new \DataWarehouse\Query\AggregateQuery(
                'Jobs',
                $aggregationUnit,
                $startDate,
                $endDate,
                'none',
                'all'
            );

            $result = $query->execute();
        } catch (PDOException $e) {
            $this->logger->debug('Exception while running query: %s', buildError($e));
            if ($e->getCode() === '42S02' && strpos($e->getMessage(), 'modw_aggregates.jobfact_by_') !== false) {
                $msg = 'Aggregate table not found, have you ingested your data?';
                throw new Exception($msg);
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->logger->debug('Exception while running query: %s', buildError($e));
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->logger->debug('Successfully ran query!');
        $rawRoles = XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR);

        $mostPrivileged = $user->getMostPrivilegedRole()->getName();
        $formats = $rawRoles['roles'][$mostPrivileged]['statistics_formats'];

        $this->logger->debug('Returning Data');
        return $this->json(
            [
                'totalCount' => 1,
                'success' => true,
                'message' => '',
                'formats' => $formats,
                'data' => [$result]
            ]
        );
    }

    /*
     * Get the column layout manager for the user
     *
     * @return \CCR\ColumnLayout
     */
    /**
     * @param XDUser $user
     * @return ColumnLayout
     */
    private function getLayout(XDUser $user): ColumnLayout
    {
        $defaultLayout = null;
        $defaultColumnCount = 2;

        if ($user->isPublicUser() === false) {
            $layoutStore = new \UserStorage($user, 'summary_layout');
            $record = $layoutStore->getById(0);
            if ($record) {
                $defaultLayout = $record['layout'];
                $defaultColumnCount = $record['columns'];
            }
        }

        return new ColumnLayout($defaultColumnCount, $defaultLayout);
    }

    /**
     * Checks that the `$[start|end]Date` values are valid ( `Y-m-d` ) dates and that `$startDate`
     * is before `$endDate`.
     *
     * @param string $startDate the beginning of the date range.
     * @param string $endDate   the end of the date range.
     * @throws BadRequestHttpException if either start or end dates are not provided in the format
     * `Y-m-d`, or if the start date is after the end date.
     */
    protected function checkDateRange($startDate, $endDate)
    {
        $this->logger->debug('Checking Date Rage');
        $startTimestamp = $this->getTimestamp($startDate, 'start_date');
        $endTimestamp = $this->getTimestamp($endDate, 'end_date');

        $this->logger->debug(sprintf('Start Timestamp: %s', $startTimestamp));
        $this->logger->debug(sprintf('End Timestamp: %s', $endTimestamp));

        if ($startTimestamp > $endTimestamp) {
            throw new BadRequestHttpException('Start Date must not be after End Date');
        }
    }

    /**
     * Attempt to convert the provided string $date value into an equivalent unix timestamp (int).
     *
     * @param string $date              The value to be converted into a DateTime.
     * @param string $paramName 'date', The name of the parameter to be included in the exception
     *                                  message if validation fails.
     * @param string $format 'Y-m-d',   The format that `$date` should be in.
     * @return int created from the provided `$date` value.
     * @throws BadRequestHttpException if the date is not in the form `Y-m-d`.
     */
    protected function getTimestamp($date, $paramName = 'date', $format = 'Y-m-d')
    {
        $this->logger->debug(sprintf('Getting Timestamp for %s %s', $date, $format));

        $parsed = date_parse_from_format($format, $date);
        $this->logger->debug(sprintf('Parsed: %s', var_export($parsed, true)));
        if ($parsed['year'] === false || $parsed['month'] === false || $parsed['day'] === false) {
            $this->logger->debug(sprintf('Unable to parse %s', $paramName));
            throw new BadRequestHttpException("Unable to parse $paramName");
        }
        $date = mktime(
            $parsed['hour'] !== false ? $parsed['hour'] : 0,
            $parsed['minute'] !== false ? $parsed['minute'] : 0,
            $parsed['second'] !== false ? $parsed['second' ] : 0,
            $parsed['month'],
            $parsed['day'],
            $parsed['year']
        );
        $this->logger->debug(sprintf('Date: %s', var_export($date, true)));
        if ($date === false || $parsed['error_count'] > 0) {
            $this->logger->debug('Unable to get timestamp!');
            throw new BadRequestHttpException("Unable to parse $paramName");
        }

        $this->logger->debug('Successfully made timestamp!');
        return $date;
    }

    /**
     * @param XDUser $user
     * @return array
     */
    private function getConfigVariables(XDUser $user): array
    {
        $person_id = $user->getPersonID(true);
        $obj_warehouse = new \XDWarehouse();

        return [
            'PERSON_ID' => $person_id,
            'PERSON_NAME' => $obj_warehouse->resolveName($person_id)
        ];
    }
}
