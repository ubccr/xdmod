<?php

use \DataWarehouse\Access\ReportGenerator;

$filters = array(
    'format' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_FORMATS_REGEX)
    ),
    'report_loc' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_TMPDIR_REGEX)
    )
);

\xd_security\assertParametersSet(array(
    'report_loc',
    'format'
));

try {

    $get = filter_input_array(INPUT_GET, $filters);

    if (!XDReportManager::isValidFormat($get['format'])) {
        print "Invalid format specified";
        exit;
    }

    $output_format = $get['format'];

    $user = \xd_security\getLoggedInUser();

    $rm = new XDReportManager($user);

    // --------------------------------------------

    // Resolve absolute path to report document on backend

    $report_id = preg_replace('/(.+)-(.+)-(.+)/', '$1-$2', $get['report_loc']);

    $working_directory = sys_get_temp_dir() . '/' . $get['report_loc'];

    $report_file = $working_directory.'/'.$report_id.'.'.$output_format;

    // --------------------------------------------

    if (!file_exists($report_file)) {
         print "The report you are referring to does not exist.";
         exit;
    }

    // --------------------------------------------

    // Build filename for attachment

    $report_name = $rm->getReportName($report_id, true).'.'.$output_format;

    // --------------------------------------------

    header("Content-type: " . XDReportManager::resolveContentType($output_format));

    header("Content-Disposition:inline;filename=\"$report_name\"");

    readfile($report_file);

    // Cleanup old temp working directories (over a day old) ========
    $tmp = sys_get_temp_dir();
    exec('find ' . escapeshellarg($tmp) . ' -type d -mtime +1', $o);

    $tmpQuoted = preg_quote($tmp, '/');
    foreach ($o as $e) {
        if ((preg_match('/^' . $tmpQuoted . '\/\d{2}-\d{10}/', $e) == 1) ||
            (preg_match('/^' . $tmpQuoted . '\/monthly_compliance_report(.+)$/', $e) == 1)
        ) {
            exec("rm -rf $e");
        }
    }//foreach
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}
