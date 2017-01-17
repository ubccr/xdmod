<?php

function checkDateParameters()
{
    $start_date_parsed = date_parse_from_format(
        'Y-m-d',
        $_REQUEST['start_date']
    );

    if ($start_date_parsed['error_count'] !== 0) {
        throw new Exception(
            'start_date param is not in the correct format of Y-m-d.'
        );
    }

    $end_date_parsed = date_parse_from_format('Y-m-d', $_REQUEST['end_date']);

    if ($end_date_parsed['error_count'] !== 0) {
        throw new Exception(
            'end_date param is not in the correct format of Y-m-d.'
        );
    }

    return array(
        $_REQUEST['start_date'],
        $_REQUEST['end_date'],
        mktime(
            $start_date_parsed['hour'],
            $start_date_parsed['minute'],
            $start_date_parsed['second'],
            $start_date_parsed['month'],
            $start_date_parsed['day'],
            $start_date_parsed['year']
        ),
        mktime(
            23,
            59,
            59,
            $end_date_parsed['month'],
            $end_date_parsed['day'],
            $end_date_parsed['year']
        )
    );
}

function getShowTitle()
{
    return (
        isset($_REQUEST['show_title'])
        ? $_REQUEST['show_title'] === 'y'
        : false
    );
}

function getWidth()
{
    return (
        isset($_REQUEST['width']) && is_numeric($_REQUEST['width'])
        ? $_REQUEST['width']
        : 740
    );
}

function getHeight()
{
    return (
        isset($_REQUEST['height']) && is_numeric($_REQUEST['height'])
        ? $_REQUEST['height']
        : 345
    );
}

function getScale()
{
    return (
        isset($_REQUEST['scale']) && is_numeric($_REQUEST['scale'])
        ? $_REQUEST['scale']
        : 1.0
    );
}

function getShowGuideLines()
{
    $show_guide_lines = true;

    if (isset($_REQUEST['show_guide_lines'])) {
        return $_REQUEST['show_guide_lines'] == 'true'
            || $_REQUEST['show_guide_lines'] === 'y';
    }
}

function getRealm()
{
    if (!isset($_REQUEST['realm'])) {
        throw new \Exception('Parameter realm is not set');
    }

    return $_REQUEST['realm'];
}

function getSwapXY()
{
    return
        isset($_REQUEST['swap_xy'])
        ? $_REQUEST['swap_xy'] == 'true' || $_REQUEST['swap_xy'] === 'y'
        : false;
}

function getShareYAxis()
{
    return
        isset($_REQUEST['share_y_axis'])
        ? $_REQUEST['share_y_axis'] == 'true'
            || $_REQUEST['share_y_axis'] === 'y'
        : false;
}

function getHideTooltip()
{
    return
        isset($_REQUEST['hide_tooltip'])
        ? $_REQUEST['hide_tooltip'] == 'true'
            || $_REQUEST['hide_tooltip'] === 'y'
        : false;
}

function getLegendLocation()
{
    return
        isset($_REQUEST['legend_type']) && $_REQUEST['legend_type'] != ''
        ? $_REQUEST['legend_type']
        : 'bottom_center';
}

function getFontSize()
{
    return
        isset($_REQUEST['font_size']) && $_REQUEST['font_size'] != ''
        ? $_REQUEST['font_size']
        : 'default';
}

function getTitle()
{
    return
        isset($_REQUEST['title']) && $_REQUEST['title'] != ''
        ? $_REQUEST['title']
        : null;
}

function getLimit()
{
    if (!isset($_REQUEST['limit']) || empty($_REQUEST['limit'])) {
        $limit = 20;
    } else {
        $limit = $_REQUEST['limit'];
    }

    return $limit;
}

function getOffset()
{
    if (!isset($_REQUEST['start']) || empty($_REQUEST['start'])) {
        $offset = 0;
    } else {
        $offset = $_REQUEST['start'];
    }

    return $offset;
}

function getSortInfo()
{
    $sortInfo = array();

    if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') {
        $sortRec = array();

        $sortRec['column_name'] = $_REQUEST['sort'];

        $sortRec['direction']
            = isset($_REQUEST['dir'])
            ? $_REQUEST['dir']
            : 'asc';

        $sortInfo[] = $sortRec;
    }

    return $sortInfo;
}

function getSearchText()
{
    return
        isset($_REQUEST['search_text']) && $_REQUEST['search_text'] != ''
        ? trim($_REQUEST['search_text'])
        : null;
}
