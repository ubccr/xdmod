<?php

namespace Rest\Controllers;

use Configuration\XdmodConfiguration;
use Exception;
use PDOException;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

use Models\Services\Acls;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DashboardControllerProvider extends BaseControllerProvider
{
    /**
     * @see BaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $class = get_class($this);

        $controller->get("$root/components", "$class::getComponents");

        $controller->post("$root/layout", "$class::setLayout");
        $controller->delete("$root/layout", "$class::resetLayout");

        $controller->get("$root/rolereport", "$class::getRoleReport");
        $controller->get("$root/savedchartsreports", "$class::getSavedChartsReports");

        $controller->post("$root/viewedUserTour", "$class::setViewedUserTour");
        $controller->get("$root/viewedUserTour", "$class::getViewedUserTour");

        $controller->get("$root/statistics", "$class::getStatistics");

    }

    /*
     * Get the column layout manager for the user
     *
     * @return \CCR\ColumnLayout
     */
    private function getLayout($user)
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

        return new \CCR\ColumnLayout($defaultColumnCount, $defaultLayout);
    }

    private function getConfigVariables($user)
    {
        $person_id = $user->getPersonID(true);
        $obj_warehouse = new \XDWarehouse();

        return array(
            'PERSON_ID' => $person_id,
            'PERSON_NAME' => $obj_warehouse->resolveName($person_id)
        );
    }

    /**
     */
    public function getComponents(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);

        $dashboardComponents = array();

        $mostPrivilegedAcl = Acls::getMostPrivilegedAcl($user)->getName();

        $layout = $this->getLayout($user);

        $roleConfig = \Configuration\XdmodConfiguration::assocArrayFactory(
            'roles.json',
            CONFIG_DIR,
            null,
            array('config_variables' => $this->getConfigVariables($user))
        );

        $presets = $roleConfig['roles'][$mostPrivilegedAcl];

        if (isset($presets['dashboard_components'])) {

            foreach($presets['dashboard_components'] as $component) {
                if (isset($component['region']) && $component['region'] === 'top') {
                    $chartLocation = 'FW' . $component['name'];
                    $column = -1;
                } else {
                    $defaultLayout = null;
                    if (isset($component['location']) && isset($component['location']['row']) && isset($component['location']['column'])) {
                        $defaultLayout = array($component['location']['row'], $component['location']['column']);
                    }

                    list($chartLocation, $column) = $layout->getLocation('PP' . $component['name'], $defaultLayout);
                }

                $dashboardComponents[$chartLocation] = array(
                        'name' => 'PP' . $component['name'],
                        'type' => $component['type'],
                        'config' => isset($component['config']) ? $component['config'] : array(),
                        'column' => $column
                );
            }
        }

        $presetCharts = isset($presets['summary_charts']) ? $presets['summary_charts'] : $roleConfig['roles']['default']['summary_charts'];

        foreach ($presetCharts as $index => $presetChart)
        {
            $presetChart['featured'] = true;
            $presetChart['aggregation_unit'] = 'Auto';
            $presetChart['timeframe_label'] = 'Previous month';

            list($chartLocation, $column) = $layout->getLocation('PC' . $index);
            $dashboardComponents[$chartLocation] = array(
                'name' => 'PC' . $index,
                'type' => 'xdmod-dash-chart-cmp',
                'config' => array(
                    'name' => 'summary_' . $index,
                    'chart' => $presetChart
                ),
                'column' => $column
            );
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

                    $name = 'UC' . $query['name'];

                    if (preg_match('/summary_(?P<index>\S+)/', $query['name'], $matches) > 0) {
                        if ($layout->hasLayout('PC' . $matches['index'])) {
                            $name = 'PC' . $matches['index'];
                        }
                    }

                    list($chartLocation, $column) = $layout->getLocation($name);

                    $dashboardComponents[$chartLocation] = array(
                        'name' => $name,
                        'type' => 'xdmod-dash-chart-cmp',
                        'config' => array(
                            'name' => $query['name'],
                            'chart' => $queryConfig
                        ),
                        'column' => $column
                    );
                }
            }
        }

        ksort($dashboardComponents);

        return $app->json(array(
            'success' => true,
            'total' => count($dashboardComponents),
            'portalConfig' => array('columns' => $layout->getColumnCount()),
            'data' => array_values($dashboardComponents)
        ));
    }

    /**
     * set the layout metadata
     *
     */
    public function setLayout(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $content = json_decode($this->getStringParam($request, 'data', true), true);

        if ($content === null || !isset($content['layout']) || !isset($content['columns'])) {
            throw new BadRequestException('Invalid data parameter');
        }

        $storage = new \UserStorage($user, 'summary_layout');

        return $app->json(array(
            'success' => true,
            'total' => 1,
            'data' => $storage->upsert(0, $content)
        ));
    }

    /**
     * clear the layout metadata
     *
     */
    public function resetLayout(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $storage = new \UserStorage($user, 'summary_layout');

        $storage->del();

        return $app->json(array(
            'success' => true,
            'total' => 1
        ));
    }

    /*
    * Set value for if a user should view the help tour or not
    */
    public function setViewedUserTour(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $viewedTour = $this->getIntParam($request, 'viewedTour', true);

        if (!in_array($viewedTour, [0,1])) {
            throw new BadRequestException('Invalid data parameter');
        }

        $storage = new \UserStorage($user, 'viewed_user_tour');

        return $app->json(array(
            'success' => true,
            'total' => 1,
            'msg' => $storage->upsert(0, ['viewedTour' => $viewedTour])
        ));
    }

    /**
     * Get charts based on role.
     **/
    public function getRoleReport(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $role = $user->getMostPrivilegedRole()->getName();
        if ($role == "cd") {
            $template_id = 2;
        } elseif ($role == "cs") {
            $template_id = 3;
        } else {
            $template_id = 3;
        }
        $report_id_suffix = 'autogenerated-' . $role;
        $report_id = $user->getUserID() . '-' . $report_id_suffix;
        if (isset($user)) {
            $userReport = null;
            $rm = new \XDReportManager($user);
            $reports = $rm->fetchReportTable();
            foreach ($reports as &$report) {
                if ($report['report_id'] === $report_id) {
                    $userReport = $report;
                }
            }
            if (is_null($userReport)){
                $template = $rm->retrieveReportTemplate($user, $template_id);
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
                $chart_id = urldecode($queue['chart_id']);
                $chart_id = explode("&", $chart_id);
                $chart_id_parsed = array();
                foreach($chart_id as $value) {
                    list($key, $value) = explode("=", $value);
                    $json = json_decode($value, true);
                    if ($json === null) {
                        $chart_id_parsed[$key] = $value;
                    } else {
                        $chart_id_parsed[$key] = $json;
                    }
                }
                $data['queue'][$count]['chart_id'] = $chart_id_parsed;
                $count++;
            }
            return $app->json(array(
                'success' => true,
                'total' => count($data),
                'data' => $data
            ));
        }
    }
    /*
    * Get stored value for if a user should view the help tour or not
    */
    public function getViewedUserTour(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $storage = new \UserStorage($user, 'viewed_user_tour');
        return $app->json(array(
            'success' => true,
            'total' => 1,
            'data' => $storage->get()
        ));
    }
    /**
     * Get saved charts and reports.
     **/
    public function getSavedChartsReports(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        if (isset($user)) {
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
                $tmp = array();
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
            return $app->json(array(
                'success' => true,
                'total' => count($data),
                'data' => $data
            ));
        }
    }

    /*
     * Retrieve summary statistics
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws Exception
     */
    public function getStatistics(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);

        $aggregationUnit = $request->get('aggregation_unit', 'auto');

        $startDate = $this->getStringParam($request, 'start_date', true);
        $endDate = $this->getStringParam($request, 'end_date', true);

        $this->checkDateRange($startDate, $endDate);

        // This try/catch block is intended to replace the "Base table or
        // view not found: 1146 Table 'modw_aggregates.jobfact_by_day'
        // doesn't exist" error message with something more informative for
        // Open XDMoD users.
        try {
            $query = new \DataWarehouse\Query\Jobs\Aggregate($aggregationUnit, $startDate, $endDate, 'none', 'all');

            $result = $query->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02' && strpos($e->getMessage(), 'modw_aggregates.jobfact_by_') !== false) {
                $msg = 'Aggregate table not found, have you ingested your data?';
                throw new Exception($msg);
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $rawRoles = XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR);

        $mostPrivileged = $user->getMostPrivilegedRole()->getName();
        $formats = $rawRoles['roles'][$mostPrivileged]['statistics_formats'];

        return $app->json(
            array(
                'totalCount' => 1,
                'success' => true,
                'message' => '',
                'formats' => $formats,
                'data' => array($result)
            )
        );
    }
}
