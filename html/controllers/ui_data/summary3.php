<?php

use Models\Services\Acls;
use User\Roles;

@session_start();
@set_time_limit(0);

@require_once dirname(__FILE__).'/../../../configuration/linker.php';

try {
    $logged_in_user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    $debug_level = 0;

    if (isset($_REQUEST['debug_level'])) {
        $debug_level = abs(intval($_REQUEST['debug_level']));
    }

    if (!isset($_REQUEST['start_date'])) {
        throw new Exception("start_date parameter is not set");
    }

    if (!isset($_REQUEST['end_date'])) {
        throw new Exception("end_date parameter is not set");
    }

    $start_date = $_REQUEST['start_date'];
    $end_date = $_REQUEST['end_date'];

    $aggregation_unit = 'auto';

    if (isset($_REQUEST['aggregation_unit'])) {
        $aggregation_unit = lcfirst($_REQUEST['aggregation_unit']);
    }

    $raw_parameters = array();
    if (isset($_REQUEST['filters'])) {
        $filtersObject = json_decode($_REQUEST['filters']);
        foreach ($filtersObject->data as $filter) {
            $filterDimensionKey = $filter->dimension_id . '_filter';
            $filterValueId = $filter->value_id;
            if (isset($raw_parameters[$filterDimensionKey])) {
                $raw_parameters[$filterDimensionKey] .= ',' . $filterValueId;
            } else {
                $raw_parameters[$filterDimensionKey] = $filterValueId;
            }
        }
    }

    $query_descripter = new \User\Elements\QueryDescripter('tg_summary', 'Jobs', 'none');

    $query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date, $end_date, 'none', 'all', $query_descripter->pullQueryParameters($raw_parameters));

    // This try/catch block is intended to replace the "Base table or
    // view not found: 1146 Table 'modw_aggregates.jobfact_by_day'
    // doesn't exist" error message with something more informative for
    // Open XDMoD users.

    try {
        $result = $query->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02' && strpos($e->getMessage(), 'modw_aggregates.jobfact_by_') !== false) {
            $msg = 'Aggregate table not found, have you ingested your data?';
            throw new Exception($msg);
        } else {
            throw $e;
        }
    }
    $mostPrivilegedAcl = Acls::getMostPrivilegedAcl($logged_in_user);

    $rolesConfig = \Configuration\XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR);
    $roles = $rolesConfig['roles'];

    $mostPrivilegedAclName = $mostPrivilegedAcl->getName();
    $mostPrivilegedAclSummaryCharts = $roles['default']['summary_charts'];

    if (isset($roles[$mostPrivilegedAclName]['summary_charts'])) {
        $mostPrivilegedAclSummaryCharts = $roles[$mostPrivilegedAclName]['summary_charts'];
    }

    $summaryCharts = array_map(
        function ($chart) {
            if (!isset($chart['preset'])) {
                $chart['preset'] = true;
            }
            return json_encode($chart);
        },
        $mostPrivilegedAclSummaryCharts
    );

    foreach ($summaryCharts as $i => $summaryChart) {
        $summaryChartObject = json_decode($summaryChart);
        $summaryChartObject->preset = true;
        $summaryCharts[$i] = json_encode($summaryChartObject);
    }

    if (!isset($_REQUEST['public_user']) || $_REQUEST['public_user'] != 'true')
    {
        $queryStore = new \UserStorage($logged_in_user, 'queries_store');
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
    //print_r($summaryCharts);
    $result['charts'] = json_encode(array_values($summaryCharts));

    echo json_encode(array('totalCount' => 1, 'success' => true, 'message' => '', 'data' => array($result) ));

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $ex) {
    echo json_encode(array('totalCount' => 0,
                           'message' => $ex->getMessage()."<hr>".$ex->getTraceAsString(),
                           'data' => array(),
                           'success' => false));
}

