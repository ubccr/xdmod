<?php

namespace DataWarehouse;

/**
 * Singleton class for helping guide the creation of a chart.
 *
 * @author Amin Ghadersohi
 */
class VisualizationBuilder
{
    private static $_self = NULL;

    public static $plot_action_formats = array(
        'session_variable',
        'png',
        'png_inline',
        'img_tag',
        'svg'
    );

    public static $display_types = array(
        'bar',
        'h_bar',
        'line',
        'pie',
        'area'
    );

    public static $combine_types = array(
        'side',
        'percentage',
        'stack',
        'overlay'
    );

    public static function getInstance()
    {
        if (self::$_self == NULL) {
            self::$_self = new VisualizationBuilder();
        }

        return self::$_self;
    }

    private function __construct()
    {
    }

    public static function getLimit(array &$request)
    {
        $limit = 20;

        if (isset($request['limit'])) {
            $limit = intval($request['limit']);

            // Use default auto value of the request is not a number,
            // this includes 'auto'.
            if ($limit == 0 && $request['limit'] != '0') {
                $limit = 20;
            }
        }

        return $limit;
    }

    public static function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }
}

