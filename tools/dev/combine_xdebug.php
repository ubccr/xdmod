<?php

#increase the memory limit
ini_set('memory_limit', -1);

$baseDir = '__BASE_DIR__';
$coverageDir = '__CODE_COVERAGE_DIR__';
$installDir = '__INSTALL_DIR__';
$reportDir = '__REPORT_DIR__';

include_once("$baseDir/vendor/autoload.php");

$final_coverage = new PHP_CodeCoverage();
$final_coverage->filter()->addDirectoryToWhitelist("$installDir/classes");
$final_coverage->filter()->addDirectoryToWhitelist("$installDir/html/controllers");
$final_coverage->filter()->addDirectoryToWhitelist("$installDir/html/internal_dashboard");
$final_coverage->filter()->addDirectoryToWhitelist("$installDir/xdmod/libraries");

$coverages = glob("$coverageDir/*.json");
$count = count($coverages);
$i = 0;

foreach ($coverages as $coverage_file) {
    $i++;
    echo "Processing coverage ($i/$count) from $coverage_file". PHP_EOL;
    $matches = array();
    $codecoverageData = json_decode(file_get_contents($coverage_file), JSON_OBJECT_AS_ARRAY);
    if ($codecoverageData !== null) {
        $test_name = str_ireplace(basename($coverage_file, ".json"), "coverage-", "");
        $final_coverage->append($codecoverageData, $test_name);
    }
}

echo "Generating final report..." . PHP_EOL;
$report = new PHP_CodeCoverage_Report_XML();
$report->process($final_coverage, $reportDir);
echo "Report generated succesfully" . PHP_EOL;
