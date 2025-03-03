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
echo "Found $count coverage files\n";
$i = 0;

foreach ($coverageFiles as $coverageFile) {
    $i++;
    $matches = array();
    $codeCoverageData = json_decode(file_get_contents($coverageFile), true);
    if ($codeCoverageData === null) {
        echo "Error reading $coverageFile\n";
        continue;
    }
    $rawCodeCoverageData = RawCodeCoverageData::fromXdebugWithPathCoverage($codeCoverageData);
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

            #TODO exclude vendor directory

            $coverage = new CodeCoverage(
                (new Selector)->forLineCoverage($filter),
                $filter
            );

            $coverageData[$testName] = $coverage;



//            #TODO Change to reflect readme
//            $coverage = new PHP_CodeCoverage();
//            $coverage->filter()->addDirectoryToWhitelist("$installDir/classes");
//            $coverage->filter()->addDirectoryToWhitelist("$installDir/html/controllers");
//            $coverage->filter()->addDirectoryToWhitelist("$installDir/html/internal_dashboard");
//            $coverage->filter()->addDirectoryToWhitelist("$installDir/libraries");
//            $coverageData[$testName] = $coverage;
//            echo "Ending coverage for $testName\n";
        }

        $testCoverage = $coverageData[$testName];

        #Append requires $CodeCoverageData to be RawCodeCoverageData
        $testCoverage->append($rawCodeCoverageData, $testName);

        $coverageData[$testName] = $testCoverage;

    }
}

$phpunitVersion = InstalledVersions::getVersion('phpunit/phpunit');

foreach($coverageData as $testName => $coverage) {
    echo "Writing coverage for $testName\n";
    #TODO Change to reflect readme
    $report = new XmlReport($phpunitVersion);
    $report->process($coverage, $reportDir . DIRECTORY_SEPARATOR . $testName);

    (new HtmlReport)->process($coverage, $reportDir . DIRECTORY_SEPARATOR . $testName);
    echo "Done\n";
}
