<?php

namespace Controllers;

class UsageExplorerTestCloud extends UsageExplorerTestBase
{
    /**
     * @dataProvider csvExportProvider
     */
    public function testCsvExport($testName, $input, $expectedFile, $userRole)
    {
        $aggUnit = $input['aggregation_unit'];
        $datasetType = $input['dataset_type'];
        $fullTestName = $testName . $datasetType . '-' . $aggUnit . '-' . $userRole;
        if(in_array($testName, $this->skip) || in_array($testName, $this->aggregationUnits[$aggUnit])) {
            $this->markTestIncomplete($fullTestName . ' intentionally skipped');
        }
        else {
            $response = self::$helper->post('/controllers/user_interface.php', null, $input);
            $csvdata = $response[0];
            $curldata = $response[1];
            /*
             * this temporarliy allows the "failed" tests of the public
             * user to pass, need to figure out a more robust way for
             * public user not having access to pass
             */
            if(gettype($csvdata) === "array") {
                if($csvdata['message'] == 'Session Expired') {
                    $this->markTestIncomplete($fullTestName . ' user session expired...');
                }
                $csvdata = print_r($csvdata, 1);
            }
            $csvdata = preg_replace(self::$replaceRegex, self::$replacements, $csvdata);

            if(!empty($expectedFile)) {
                $expected = file_get_contents($expectedFile);
                $expected = preg_replace(self::$replaceRegex, self::$replacements, $expected);
                if($expected === $csvdata) {
                    $this->assertEquals($expected, $csvdata);
                    return;
                }

                $failures = $this->csvDataDiff($expected, $csvdata, $fullTestName);
                if(empty($failures)) {
                    // This happens because of maths (specifically floating point maths)
                    self::$messages[] = "$fullTestName IS ONLY ==";
                    return;
                }
                elseif(substr($expectedFile, -13) !== 'reference_cloud.csv') {
                    throw new PHPUnit_Framework_ExpectationFailedException(
                        count($failures)." assertions failed:\n\t".implode("\n\t", $failures)
                    );
                }
            }

            $endpoint = parse_url(self::$helper->getSiteUrl());
            $outputDir = self::$baseDir .
                '/expected/' .
                $endpoint['host'] .
                '/' . $testName .
                '/'  ;
            if(!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            $outputDir = realpath($outputDir);

            $outputFile = $outputDir . '/' . $datasetType . '-' . $aggUnit . '-' . (empty($expectedFile) ? 'reference_cloud' : $userRole ) . '.csv';
            file_put_contents(
                $outputFile,
                $csvdata
            );
            $this->markTestSkipped(
                'Created Expected output for ' . $fullTestName
            );
        }
    }
    
    public function csvExportProvider()
    {
        self::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/';

        $this->defaultSetup();

        $statistics = array(
            'num_vms_ended',
            'num_vms_running',
            'num_vms_started',
            'avg_wallduration_hours',
            'core_time',
            'wall_time',
            'avg_num_cores',
            'num_cores',
            'avg_cores_reserved',
            'avg_memory_reserved',
            'avg_disk_reserved',
        );

        $group_bys = array(
            'configuration',
            'memory_buckets',
            'none',
            'project',
            'resource',
            'vm_size',
        );

        $varSettings = array(
            'realm' => array('Cloud'),
            'dataset_type' => array('aggregate', 'timeseries'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'aggregation_unit' => array_keys($this->aggregationUnits)
        );

        return $this->generateTests($varSettings);
    }
}
