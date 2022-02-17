<?php

require_once 'common.php';

use DataWarehouse\Access\MetricExplorer;
use DataWarehouse\Query\TimeAggregationUnit;

// 5 minute exec time max
ini_set('max_execution_time', 300);

$returnData = array();

try {
    if (isset($_REQUEST['config'])) {
        $config = json_decode($_REQUEST['config'], true);
        $_REQUEST = array_merge($config, $_REQUEST);
    }

    $format = \DataWarehouse\ExportBuilder::getFormat(
        $_REQUEST,
        'jsonstore',
        array(
            'jsonstore'
        )
    );

    $user = \xd_security\detectUser(
        array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER)
    );

    $inline = getInline();


    if (!isset($_REQUEST['datasetId']) || !isset($_REQUEST['datapoint'])) {
        throw new Exception('Invalid request content');
    }

    $showContextMenu = getShowContextMenu();

    list($start_date, $end_date, $start_ts, $end_ts) = checkDateParameters();

    if ($start_ts > $end_ts) {
        throw new Exception(
            'End date must be greater than or equal to start date'
        );
    }

    if (getTimeseries()) {
        // For timeseries data the date range is set to be only the data-point that was
        // selected. Therefore we adjust the start and end date appropriately

        $time_period = TimeAggregationUnit::deriveAggregationUnitName(getAggregationUnit(), $start_date, $end_date);
        $time_point = $_REQUEST['datapoint'] / 1000;

        list($start_date, $end_date) = TimeAggregationUnit::getRawTimePeriod($time_point, $time_period);
    }

    $title = getTitle();

    $global_filters = getGlobalFilters();

    $dataset_classname = '\DataWarehouse\Data\SimpleDataset';

    $filename
        = 'xdmod_'
        . ($title != '' ? $title : 'untitled')
        . '_' . $start_date . '_to_' . $end_date;

    $filename = substr($filename, 0, 100);

    $all_data_series = getDataSeries();

    $datasetid = $_REQUEST['datasetId'];

    // used to compare to floats, in this case the datasetId and the data_series->id
    $epsilon = 0.00000000000001;

    // find requested dataset.
    $data_description = null;
    foreach ($all_data_series as $data_description_index => $data_series) {
        if (abs($data_series->id - $datasetid) < $epsilon) {
            $data_description = $data_series;
            break;
        }
    }

    if ($data_description === null) {
        throw new Exception("Internal error");
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
            $start_date,
            $end_date,
            null,
            $data_description->metric,
            array()
        );

        $groupedRoleParameters = array();
        foreach ($global_filters->data as $global_filter) {
            if ($global_filter->checked == 1) {
                if (
                !isset(
                    $groupedRoleParameters[$global_filter->dimension_id]
                )
                ) {
                    $groupedRoleParameters[$global_filter->dimension_id]
                        = array();
                }

                $groupedRoleParameters[$global_filter->dimension_id][]
                    = $global_filter->value_id;
            }
        }

        $query->setMultipleRoleParameters($user->getAllRoles(), $user);

        $query->setRoleParameters($groupedRoleParameters);

        $query->setFilters($data_description->filters);

        $dataset = new $dataset_classname($query);

        // DEFINE: that we're going to be sending back json.
        header('Content-type: application/json');

        $filterOpts = array('options' => array('default' => null, 'min_range' => 0));

        $limit = filter_input(INPUT_POST, 'limit', FILTER_VALIDATE_INT, $filterOpts);
        $offset = filter_input(INPUT_POST, 'start', FILTER_VALIDATE_INT, $filterOpts);

        $totalCount  = $dataset->getTotalPossibleCount();

        $ret = array();

        // As a small optimization only compute the total count the first time (ie when the offset is 0)
        if($offset === null or $offset == 0) {
            $privquery = new $query_classname(
                $data_description->realm,
                'day',
                $start_date,
                $end_date,
                null,
                $data_description->metric,
                array()
            );
            $privquery->setRoleParameters($groupedRoleParameters);
            $privquery->setFilters($data_description->filters);
            $privdataset = new $dataset_classname($privquery);

            $ret['totalAvailable'] = $privdataset->getTotalPossibleCount();
        }

        $ret['data'] = $dataset->getResults($limit, $offset);
        $ret['totalCount'] = $totalCount;

        print json_encode($ret);
        exit(0);

    }
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $ex) {
    \xd_response\presentError($ex);
}
