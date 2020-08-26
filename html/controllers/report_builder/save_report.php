<?php

use \DataWarehouse\Access\ReportGenerator;

$filters = array(
    'phase' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => '/^create|update$/')
    ),
    'report_id' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_ID_REGEX)
    ),
    'report_format' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_FORMATS_REGEX . 'i')
    ),
    'charts_per_page' => array(
        'filter' => FILTER_VALIDATE_INT,
        'options' => array('min_range' => 1)
    ),
    'report_schedule' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_SCHEDULE_REGEX)
    ),
    'report_delivery' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_DELIVERY_REGEX)
    )
);

try {
    $user = \xd_security\getLoggedInUser();
    $rm = new XDReportManager($user);
    $base_path = \xd_utilities\getConfiguration('reporting', 'base_path');
    $post = filter_input_array(INPUT_POST, $filters);
    $map = array();

    \xd_security\assertParameterSet('phase');

    switch ($post['phase']) {
        case 'create':
            $report_id = $user->getUserID()."-".time();
            break;
        case 'update':
            \xd_security\assertParameterSet('report_id');
            $report_id = $post['report_id'];

            // Cache the blobs so they can be re-introduced as necessary during the report update process
            $rm->buildBlobMap($report_id, $map);
            $rm->removeReportCharts($report_id);
            break;
    }

    $report_name = mb_convert_encoding($_POST['report_name'], ReportGenerator::REPORT_CHAR_ENCODING, 'UTF-8');
    $report_title = mb_convert_encoding($_POST['report_title'], ReportGenerator::REPORT_CHAR_ENCODING, 'UTF-8');
    $report_header = mb_convert_encoding($_POST['report_header'], ReportGenerator::REPORT_CHAR_ENCODING, 'UTF-8');
    $report_footer = mb_convert_encoding($_POST['report_footer'], ReportGenerator::REPORT_CHAR_ENCODING, 'UTF-8');

    $rm->configureSelectedReport(
        $report_id,
        $report_name,
        $report_title,
        $report_header,
        $report_footer,
        $post['report_format'],
        $post['charts_per_page'],
        $post['report_schedule'],
        $post['report_delivery']
    );

    if ($rm->isUniqueName($report_name, $report_id) == false) {
        \xd_response\presentError('Another report you have created is already using this name.');
    }

    switch ($post['phase']) {
        case 'create':
            $rm->insertThisReport("Manual");
            break;
        case 'update':
            $rm->saveThisReport();
            break;
    }

    foreach ($_POST as $k => $v) {
        if (preg_match('/chart_data_(\d+)/', $k, $m) > 0) {
            $order = $m[1];

            list($chart_id, $chart_title, $chart_drill_details, $chart_date_description, $timeframe_type, $entry_type) = explode(';', $v);

            $chart_title = str_replace('%3B', ';', $chart_title);
            $chart_drill_details = str_replace('%3B', ';', $chart_drill_details);

            $cache_ref_variable = 'chart_cacheref_'.$order;

            // Transfer blobs residing in the directory used for temporary
            // files into the database as necessary for each chart which
            // comprises the report.

            if (isset($_POST[$cache_ref_variable])) {
                $cache_ref = filter_var(
                    $_POST[$cache_ref_variable],
                    FILTER_VALIDATE_REGEXP,
                    array('options' => array('regexp' => ReportGenerator::CHART_CACHEREF_REGEX))
                );

                list($start_date, $end_date, $ref, $rank) = explode(';', $cache_ref);

                $location = sys_get_temp_dir() . "/{$ref}_{$rank}_{$start_date}_{$end_date}.png";

                // Generate chart blob if it doesn't exist.  This file
                // should have already been created by
                // report_image_renderer.php, but is not in Firefox.
                // See Mantis 0001336
                if (!is_file($location)) {
                    $insertion_rank = array(
                        'rank' => $rank,
                        'did'  => '',
                    );
                    $cached_blob = $start_date . ',' . $end_date . ';'
                        .  $rm->generateChartBlob('volatile', $insertion_rank, $start_date, $end_date);
                } else {
                    $cached_blob = $start_date.','.$end_date.';'.file_get_contents($location);
                }

                // TODO: consider refactoring !!!

                $chart_id_found = false;

                foreach ($map as &$e) {
                    if ($e['chart_id'] == $chart_id) {
                        $e['image_data'] = $cached_blob;
                        $chart_id_found = true;
                    }
                }

                if ($chart_id_found == false) {
                    $map[] = array(
                        'chart_id' => $chart_id,
                        'image_data' => $cached_blob
                    );
                }
            }

            $rm->saveCharttoReport($report_id, $chart_id, $chart_title, $chart_drill_details, $chart_date_description, $order, $timeframe_type, $entry_type, $map);
        }
    }

    $returnData['action'] = 'save_report';
    $returnData['phase'] = $post['phase'];
    $returnData['report_id'] = $report_id;
    $returnData['success'] = true;
    $returnData['status'] = 'success';

    \xd_controller\returnJSON($returnData);
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {
    \xd_response\presentError($e->getMessage());
}
