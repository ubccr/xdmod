<?php

require_once __DIR__ . '/../common_params.php';

function getSelectedDimensionIds()
{
    return
        isset($_REQUEST['selectedDimensionIds'])
            && $_REQUEST['selectedDimensionIds'] != ''
        ? explode(',', $_REQUEST['selectedDimensionIds'])
        : array();
}

function getSelectedMetricIds()
{
    return
        isset($_REQUEST['selectedMetricIds'])
            && $_REQUEST['selectedMetricIds'] != ''
        ? explode(',', $_REQUEST['selectedMetricIds'])
        : array();
}

function getAggregationUnit()
{
    return
        isset($_REQUEST['aggregation_unit'])
        ? $_REQUEST['aggregation_unit']
        : 'auto';
}

function getTimeseries()
{
    return
        isset($_REQUEST['timeseries'])
        ? $_REQUEST['timeseries'] == 'true' || $_REQUEST['timeseries'] === 'y'
        : false;
}

function getInline()
{
    return
        isset($_REQUEST['inline'])
        ? $_REQUEST['inline'] == 'true' || $_REQUEST['inline'] === 'y'
        : true;
}

function getDataSeries()
{
    if (!isset($_REQUEST['data_series']) || empty($_REQUEST['data_series'])) {
        return json_decode(0);
    }

    if (is_array($_REQUEST['data_series'])
        && is_array($_REQUEST['data_series']['data'])
    ) {
        $v = $_REQUEST['data_series']['data'];

        $ret = array();
        foreach ($v as $x) {
            $y = (object)$x;

            for ($i = 0, $b = count($y->filters['data']); $i < $b; $i++) {
                $y->filters['data'][$i] = (object)$y->filters['data'][$i];
            }

            $y->filters = (object)$y->filters;

            // Set values of new attribs for backward compatibility.
            if (!isset($y->line_type) || empty($y->line_type)) {
                $y->line_type = 'Solid';
            }

            if (!isset($y->line_width)
                || empty($y->line_width)
                || !is_numeric($y->line_width)
            ) {
                $y->line_width = 2;
            }

            if (!isset($y->color) || empty($y->color)) {
                $y->color = 'auto';
            }

            if (!isset($y->shadow) || empty($y->shadow)) {
                $y->shadow = false;
            }

            $ret[] = $y;
        }

        return $ret;
    }
    $ret =  urldecode($_REQUEST['data_series']);

    $jret = json_decode($ret);

    foreach ($jret as &$y) {
        // Set values of new attribs for backward compatibility.
        if (!isset($y->line_type) || empty($y->line_type)) {
            $y->line_type = 'Solid';
        }

        if (!isset($y->line_width)
            || empty($y->line_width)
            || !is_numeric($y->line_width)
        ) {
            $y->line_width = 2;
        }

        if (!isset($y->color) || empty($y->color)) {
            $y->color = 'auto';
        }

        if (!isset($y->shadow) || empty($y->shadow)) {
            $y->shadow = false;
        }
    }

    return $jret;
}

function getSelectedFilterIds()
{
    return
        isset($_REQUEST['selectedFilterIds'])
            && $_REQUEST['selectedFilterIds'] != ''
        ? explode(',', $_REQUEST['selectedFilterIds'])
        : array();
}

function getGlobalFilters()
{
    if (!isset($_REQUEST['global_filters'])
        || empty($_REQUEST['global_filters'])
    ) {
        return (object)array('data' => array(), 'total' => 0);
    }

    if (is_array($_REQUEST['global_filters'])) {
        $v = $_REQUEST['global_filters']['data'];

        $ret = (object)array('data' => array(), 'total' => 0);

        foreach ($v as $x) {
            $ret->data[] = (object)$x;
            $ret->total++;
        }

        return $ret;
    }

    $ret =  urldecode($_REQUEST['global_filters']);

    return json_decode($ret);
}

function getShowContextMenu()
{
    return
        isset($_REQUEST['showContextMenu'])
        ? $_REQUEST['showContextMenu'] == 'true'
            || $_REQUEST['showContextMenu'] === 'y'
        : false;
}

function getXAxis()
{
    if (!isset($_REQUEST['x_axis']) || empty($_REQUEST['x_axis'])) {
        return array();
    }

    if (is_array($_REQUEST['x_axis'])) {
        $ret = new stdClass;

        foreach ($_REQUEST['x_axis'] as $k => $x) {
            if (is_array($x)) {
                $ret->{$k} = (object)$x;
            } else {
                $ret->{$k} = $x;
            }
        }

        return  $ret;
    }

    return json_decode(urldecode($_REQUEST['x_axis']));
}

function getYAxis()
{
    if (!isset($_REQUEST['y_axis']) || empty($_REQUEST['y_axis'])) {
        return array();
    }

    if (is_array($_REQUEST['y_axis'])) {
        $ret = new stdClass;

        foreach ($_REQUEST['y_axis'] as $k => $x) {
            if (is_array($x)) {
                $ret->{$k} = (object)$x;
            } else {
                $ret->{$k} = $x;
            }
        }

        return $ret;
    }

    return json_decode(urldecode($_REQUEST['y_axis']));
}

function getLegend()
{
    if (!isset($_REQUEST['legend']) || empty($_REQUEST['legend'])) {
        return array();
    }

    if (is_array($_REQUEST['legend'])) {
        $ret = new stdClass;

        foreach ($_REQUEST['legend'] as $k => $x) {
            if (is_array($x)) {
                $ret->{$k} = (object)$x;
            } else {
                $ret->{$k} = $x;
            }
        }

        return  $ret;
    }

    return json_decode(urldecode($_REQUEST['legend']));
}

function getShowFilters()
{
    return
        isset($_REQUEST['show_filters'])
        ? $_REQUEST['show_filters'] == 'y'
            || $_REQUEST['show_filters'] == 'true'
        : true;
}
