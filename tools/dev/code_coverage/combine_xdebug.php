<?php

#increase the memory limit
ini_set('memory_limit', -1);

$baseDir = '__BASE_DIR__';
$coverageDir = '__CODE_COVERAGE_DIR__';
$installDir = '__INSTALL_DIR__';
$reportDir = '__REPORT_DIR__';

include_once("$baseDir/vendor/autoload.php");

$coverageData = array();

$coverageFiles = glob("$coverageDir/*.json");
$count = count($coverageFiles);
$i = 0;

foreach ($coverageFiles as $coverageFile) {
    $i++;
    $matches = array();
    $codeCoverageData = json_decode(file_get_contents($coverageFile), JSON_OBJECT_AS_ARRAY);
    if ($codeCoverageData !== null) {
        $testName = str_ireplace("coverage-", "", basename($coverageFile, ".json"));
        $testName = substr($testName, 0, strrpos($testName, '-'));
        if (!array_key_exists($testName, $coverageData)) {
            $coverage = new PHP_CodeCoverage();
            $coverage->filter()->addDirectoryToWhitelist("$installDir/classes");
            $coverage->filter()->addDirectoryToWhitelist("$installDir/html/controllers");
            $coverage->filter()->addDirectoryToWhitelist("$installDir/html/internal_dashboard");
            $coverage->filter()->addDirectoryToWhitelist("$installDir/libraries");
            $coverageData[$testName] = $coverage;
        }
        $testCoverage = $coverageData[$testName];
        $testCoverage->append($codeCoverageData, $testName);

        $coverageData[$testName] = $testCoverage;
    }
}

foreach($coverageData as $testName => $coverage) {
    $report = new PHP_CodeCoverage_Report_XML();
    $report->process($coverage, $reportDir . DIRECTORY_SEPARATOR . $testName);
}
