<?php
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as XmlReport;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use Composer\InstalledVersions;

#increase the memory limit
ini_set('memory_limit', -1);

$baseDir = '__BASE_DIR__';
$coverageDir = '__CODE_COVERAGE_DIR__';
$installDir = '__INSTALL_DIR__';
$reportDir = '__REPORT_DIR__';

include_once("$baseDir/vendor/autoload.php");


echo "base dir " . $baseDir . "\n";
echo "coverage dir " . $coverageDir . "\n";
echo "install dir " . $installDir . "\n";
echo "report dir ". $reportDir . "\n";


$coverageData = array();

$coverageFiles = glob("$coverageDir/*.json");

$count = count($coverageFiles);
$i = 0;
foreach ($coverageFiles as $coverageFile) {
    $i++;
    $codeCoverageData = json_decode(file_get_contents($coverageFile), true);
    if ($codeCoverageData === null) {
        echo "Error reading $coverageFile\n";
        continue;
    }
    $rawCodeCoverageData = RawCodeCoverageData::fromXdebugWithoutPathCoverage($codeCoverageData);
    if ($codeCoverageData !== null) {
        $testName = str_ireplace("coverage-", "", basename($coverageFile, ".json"));
        $testName = substr($testName, 0, strrpos($testName, '-'));

        if (!array_key_exists($testName, $coverageData)) {

            $filter = new Filter;

            $filter->includeDirectory($installDir . '/classes');
            $filter->includeDirectory($installDir . '/html/controllers');
            $filter->includeDirectory($installDir . '/html/internal_dashboard');
            $filter->includeDirectory($installDir . '/libraries');
            $filter->excludeDirectory($installDir . '/vendor');


            $coverage = new CodeCoverage(
                (new Selector)->forLineCoverage($filter),
                $filter
            );

            $coverageData[$testName] = $coverage;
        }

        $testCoverage = $coverageData[$testName];

        $testCoverage->append($rawCodeCoverageData, $testName);

        $coverageData[$testName] = $testCoverage;

    }
}


$phpunitVersion = InstalledVersions::getVersion('phpunit/phpunit');

foreach($coverageData as $testName => $coverage) {
    $report = new XmlReport($phpunitVersion);
    $report->process($coverage, $reportDir . DIRECTORY_SEPARATOR . $testName);

    (new HtmlReport)->process($coverage, $reportDir . DIRECTORY_SEPARATOR . $testName);
}
