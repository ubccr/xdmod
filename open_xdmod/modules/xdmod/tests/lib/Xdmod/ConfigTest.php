<?php

namespace UnitTesting\Xdmod;

require_once __DIR__ . '/../../bootstrap.php';

use Xdmod\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @dataProvider moduleSectionProvider
     *
     * @param string $section
     * @param array $testCases
     */
    public function testGetModuleSection($section, array $testCases)
    {
        $this->assertNotNull($section);
        $this->assertNotEmpty($testCases);

        echo "Section: $section\n";

        $config = Config::factory();
        $this->assertNotNull($config);

        foreach($testCases as $testCase) {
            if (!isset($testCase['expected'])) {
                continue;
            }

            $metaData = isset($testCase['meta_data'])
                ? $testCase['meta_data']
                : null;
            $expected = $testCase['expected'];

            $actual = $metaData !== null
                ? $config->filterByMetaData(
                    $config->getModuleSection($section),
                    $metaData
                )
                : $config->getModuleSection($section);

            echo "Expected: " . $expected . "\n";
            echo "Actual  : " . json_encode($actual) . "\n";
            $this->assertEquals($expected, json_encode($actual));
        }

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Configuration file 'supa.json' not found
     */
    public function testInvalidSection()
    {
        $invalidSection = 'supa';

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($invalidSection);
    }

    public function testValidSectionWithNoDFolder()
    {
        $validSection = 'linker';

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($validSection);
    }

    public function testMissingParentPlus()
    {
        $section = 'baz';
        $sectionFilePath = CONFIG_DIR . "/$section.json";
        $sectionDir = CONFIG_DIR . "/$section.d";
        $sectionChildFile = "$sectionDir/xdmod.json";


        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, "{}");
        fflush($fh);
        fclose($fh);

        // If the section directory doesn't exist then create it.
        if (!is_dir($sectionDir)) {
            $this->assertTrue(mkdir($sectionDir), "Unable to create: $sectionDir");
        }

        // Create the child file w/ a '+' property that the parent doesn't have.
        $fh = fopen($sectionChildFile, "w+");
        fwrite($fh, '{ "+roles": { "+default": { "+permitted_modules": [ { "name": "job_viewer", "title": "Job Viewer", "position": 5000, "javascriptClass": "XDMoD.Module.JobViewer", "javascriptReference": "CCR.xdmod.ui.jobViewer", "tooltip": "View detailed job-level metrics", "userManualSectionName": "Job Viewer" } ] } } }');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($section);

        // Clean up the child file.
        $this->assertTrue(unlink($sectionChildFile), "Unable to remove $sectionChildFile");

        // Clean up the section directory.
        $this->assertTrue(rmdir($sectionDir), "Unable to remove $sectionDir");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");

    }

    public function testMalformedParentSection()
    {
        $section = 'baz';
        $sectionFilePath = CONFIG_DIR . "/$section.json";
        $sectionDir = CONFIG_DIR . "/$section.d";
        $sectionChildFile = "$sectionDir/xdmod.json";


        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": "totally not going to work."}');
        fflush($fh);
        fclose($fh);

        // If the section directory doesn't exist then create it.
        if (!is_dir($sectionDir)) {
            $this->assertTrue(mkdir($sectionDir), "Unable to create: $sectionDir");
        }

        // Create the child file w/ a '+' property that the parent doesn't have.
        $fh = fopen($sectionChildFile, "w+");
        fwrite($fh, '{ "+roles": { "+default": { "+permitted_modules": [ { "name": "job_viewer", "title": "Job Viewer", "position": 5000, "javascriptClass": "XDMoD.Module.JobViewer", "javascriptReference": "CCR.xdmod.ui.jobViewer", "tooltip": "View detailed job-level metrics", "userManualSectionName": "Job Viewer" } ] } } }');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);
        try {
            $config->getModuleSection($section);
        } catch (\Exception $e) {
            $msg =$e->getMessage();
            $condition = strpos($msg, "Cannot merge non-array/object values") >= 0;
            $this->assertTrue(
                $condition,
                "Unable to correctly identify the expected exception message."
            );
        }


        // Clean up the child file.
        $this->assertTrue(unlink($sectionChildFile), "Unable to remove $sectionChildFile");

        // Clean up the section directory.
        $this->assertTrue(rmdir($sectionDir), "Unable to remove $sectionDir");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testAddMetaDataToASectionThatAlreadyHasSome()
    {
        $section = 'abc';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "enabled": true }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $metaData = $data['meta-data'];
        $this->assertArrayHasKey('enabled', $metaData);

        $this->assertTrue($metaData['enabled'] === true);

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testFilteringWithMismatchedValueTypes()
    {
        $invalidMetaData = array(
            'modules' => 'bar'
        );

        $config = Config::factory();
        $this->assertNotNull($config);

        $roles = $config->getModuleSection('roles');
        $filtered = $config->filterByMetaData($roles, $invalidMetaData);
        $this->assertEmpty($filtered);

    }

    public function testFilteringWithNonArrays()
    {
        $section = 'bac';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "enabled": true }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $filtered = $config->filterByMetaData(
            $data,
            array(
                'enabled'=> true
            )
        );
        $this->assertNotEmpty($filtered, "Expected data to be returned but found none.");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testFilteringWithNonAssociativeArrays()
    {
        $section = 'cba';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "testing": { "modules": ["xdmod"] } }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $filtered = $config->filterByMetaData(
            $data,
            array(
                'testing'=> array(
                    "modules" => array(
                        "xdmod"
                    )
                )
            )
        );
        $this->assertNotEmpty($filtered, "Expected data to be returned but found none.");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function moduleSectionProvider()
    {
        return array(
            array(
                'roles',
                array(
                    array(
                        'meta_data' => array(
                            'modules' => array(
                                'xdmod'
                            )
                        ),
                        'expected' => '{"roles":{"default":{"permitted_modules":[{"name":"tg_summary","default":true,"title":"Summary","position":100,"javascriptClass":"XDMoD.Module.Summary","javascriptReference":"CCR.xdmod.ui.tgSummaryViewer","tooltip":"Displays summary information","userManualSectionName":"Summary Tab"},{"name":"tg_usage","title":"Usage","position":200,"javascriptClass":"XDMoD.Module.Usage","javascriptReference":"CCR.xdmod.ui.chartViewerTGUsage","tooltip":"Displays usage","userManualSectionName":"Usage Tab"},{"name":"metric_explorer","title":"Metric Explorer","position":300,"javascriptClass":"XDMoD.Module.MetricExplorer","javascriptReference":"CCR.xdmod.ui.metricExplorer","userManualSectionName":"Metric Explorer","tooltip":""},{"name":"report_generator","title":"Report Generator","position":1000,"javascriptClass":"XDMoD.Module.ReportGenerator","javascriptReference":"CCR.xdmod.ui.reportGenerator","userManualSectionName":"Report Generator","tooltip":""},{"name":"about_xdmod","title":"About","position":10000,"javascriptClass":"XDMoD.Module.About","javascriptReference":"CCR.xdmod.ui.aboutXD","userManualSectionName":"About","tooltip":""}],"query_descripters":[{"realm":"Jobs","group_by":"none"},{"realm":"Jobs","group_by":"jobsize"},{"realm":"Jobs","group_by":"jobwalltime"},{"realm":"Jobs","group_by":"nodecount"},{"realm":"Jobs","group_by":"nsfdirectorate"},{"realm":"Jobs","group_by":"parentscience"},{"realm":"Jobs","group_by":"fieldofscience"},{"realm":"Jobs","group_by":"pi"},{"realm":"Jobs","group_by":"queue"},{"realm":"Jobs","group_by":"resource"},{"realm":"Jobs","group_by":"resource_type"},{"realm":"Jobs","group_by":"person"},{"realm":"Jobs","group_by":"username"}],"summary_charts":[{"data_series":{"data":[{"combine_type":"stack","display_type":"column","filters":{"data":[],"total":0},"group_by":"resource","has_std_err":false,"id":1.0e-14,"ignore_global":false,"log_scale":false,"long_legend":true,"metric":"total_cpu_hours","realm":"Jobs","sort_type":"value_desc","std_err":false,"value_labels":false,"x_axis":false}],"total":1},"global_filters":{"data":[],"total":0},"legend_type":"right_center","limit":10,"show_filters":true,"start":0,"timeseries":true,"title":"Total CPU Hours By Resource (Top 10)"},{"data_series":{"data":[{"combine_type":"stack","display_type":"column","filters":{"data":[],"total":0},"group_by":"jobsize","has_std_err":false,"id":2.0e-14,"ignore_global":false,"log_scale":false,"long_legend":true,"metric":"total_cpu_hours","realm":"Jobs","sort_type":"value_desc","std_err":false,"value_labels":false,"x_axis":false}],"total":1},"global_filters":{"data":[],"total":0},"legend_type":"right_center","limit":20,"show_filters":true,"start":0,"timeseries":true,"title":"Total CPU Hours by Job Size"},{"data_series":{"data":[{"combine_type":"side","display_type":"column","filters":{"data":[],"total":0},"group_by":"none","has_std_err":true,"id":3.0e-14,"ignore_global":false,"log_scale":false,"long_legend":true,"metric":"avg_processors","realm":"Jobs","sort_type":"none","std_err":true,"value_labels":false,"x_axis":false}],"total":1},"global_filters":{"data":[],"total":0},"legend_type":"off","limit":20,"show_filters":true,"start":0,"timeseries":true,"title":"Avg Job Size (Core Count)"},{"data_series":{"data":[{"combine_type":"side","display_type":"pie","filters":{"data":[],"total":0},"group_by":"pi","has_std_err":true,"id":4.0e-14,"ignore_global":false,"log_scale":false,"long_legend":true,"metric":"total_cpu_hours","realm":"Jobs","sort_type":"value_desc","std_err":true,"value_labels":true,"x_axis":false}],"total":1},"global_filters":{"data":[],"total":0},"legend_type":"off","limit":10,"show_filters":true,"start":0,"timeseries":false,"title":"Total CPU Hours by PI"}]},"pub":{"display":"Public User","type":"data","hierarchies":[{"level":0,"filter_override":false}],"permitted_modules":[{"name":"tg_summary","default":true,"title":"Summary","position":100,"javascriptClass":"XDMoD.Module.Summary","javascriptReference":"CCR.xdmod.ui.tgSummaryViewer","tooltip":"Displays summary information","userManualSectionName":"Summary Tab"},{"name":"tg_usage","title":"Usage","position":200,"javascriptClass":"XDMoD.Module.Usage","javascriptReference":"CCR.xdmod.ui.chartViewerTGUsage","tooltip":"Displays usage","userManualSectionName":"Usage Tab"},{"name":"about_xdmod","title":"About","position":10000,"javascriptClass":"XDMoD.Module.About","javascriptReference":"CCR.xdmod.ui.aboutXD","userManualSectionName":"About","tooltip":""}],"query_descripters":[{"realm":"Jobs","group_by":"none"},{"realm":"Jobs","group_by":"fieldofscience"},{"realm":"Jobs","group_by":"jobsize"},{"realm":"Jobs","group_by":"jobwalltime"},{"realm":"Jobs","group_by":"nodecount"},{"realm":"Jobs","group_by":"nsfdirectorate"},{"realm":"Jobs","group_by":"parentscience"},{"realm":"Jobs","group_by":"pi"},{"realm":"Jobs","group_by":"queue"},{"realm":"Jobs","group_by":"resource"},{"realm":"Jobs","group_by":"resource_type"},{"realm":"Jobs","group_by":"person"},{"realm":"Jobs","group_by":"username","disable":true}]},"usr":{"display":"User","type":"data","hierarchies":[{"level":100,"filter_override":false}],"dimensions":["person"],"extends":"default"},"cd":{"display":"Center Director","type":"feature","hierarchies":[{"level":400,"filter_override":false}],"dimensions":["provider"],"extends":"default"},"pi":{"display":"Principal Investigator","type":"data","hierarchies":[{"level":200,"filter_override":false}],"dimensions":["pi"],"extends":"default"},"cs":{"display":"Center Staff","type":"data","hierarchies":[{"level":300,"filter_override":false}],"dimensions":["provider"],"extends":"default"},"mgr":{"display":"Manager","type":"data","hierarchies":[{"level":301,"filter_override":false}],"dimensions":["person"],"extends":"default"}}}'
                    )
                )
            ),
            array(
                'datawarehouse',
                array(
                    array(
                        'meta_data' => array(
                            'modules' => array(
                                'xdmod'
                            )
                        ),
                        'expected' => '{"realms":{"Jobs":{"schema":"modw_aggregates","table":"jobfact","datasource":"HPcDB","group_bys":[{"name":"none","class":"GroupByNone"},{"name":"nodecount","class":"GroupByNodeCount"},{"name":"person","class":"GroupByPerson"},{"name":"pi","class":"GroupByPI"},{"name":"resource","class":"GroupByResource"},{"name":"resource_type","class":"GroupByResourceType"},{"name":"nsfdirectorate","class":"GroupByNSFDirectorate"},{"name":"parentscience","class":"GroupByParentScience"},{"name":"fieldofscience","class":"GroupByScience"},{"name":"jobsize","class":"GroupByJobSize"},{"name":"jobwalltime","class":"GroupByJobTime"},{"name":"queue","class":"GroupByQueue"},{"name":"username","class":"GroupByUsername"},{"name":"day","class":"GroupByDay"},{"name":"month","class":"GroupByMonth"},{"name":"quarter","class":"GroupByQuarter"},{"name":"year","class":"GroupByYear"}],"statistics":[{"name":"job_count","class":"JobCountStatistic"},{"name":"job_count","class":"JobCountStatistic"},{"name":"running_job_count","class":"RunningJobCountStatistic","control":true},{"name":"started_job_count","class":"StartedJobCountStatistic","control":true},{"name":"submitted_job_count","class":"SubmittedJobCountStatistic"},{"name":"active_person_count","class":"ActiveUserCountStatistic"},{"name":"active_pi_count","class":"ActivePICountStatistic"},{"name":"total_cpu_hours","class":"TotalCPUHoursStatistic"},{"name":"total_waitduration_hours","class":"TotalWaitHoursStatistic"},{"name":"total_node_hours","class":"TotalNodeHoursStatistic"},{"name":"total_wallduration_hours","class":"TotalWallHoursStatistic"},{"name":"avg_cpu_hours","class":"AverageCPUHoursStatistic"},{"name":"sem_avg_cpu_hours","class":"SEMAverageCPUHoursStatistic","visible":false},{"name":"avg_node_hours","class":"AverageNodeHoursStatistic"},{"name":"sem_avg_node_hours","class":"SEMAverageNodeHoursStatistic","visible":false},{"name":"avg_waitduration_hours","class":"AverageWaitHoursStatistic"},{"name":"sem_avg_waitduration_hours","class":"SEMAverageWaitHoursStatistic","visible":false},{"name":"avg_wallduration_hours","class":"AverageWallHoursStatistic"},{"name":"sem_avg_wallduration_hours","class":"SEMAverageWallHoursStatistic","visible":false},{"name":"avg_processors","class":"AverageProcessorCountStatistic"},{"name":"sem_avg_processors","class":"SEMAverageProcessorCountStatistic","visible":false},{"name":"min_processors","class":"MinProcessorCountStatistic"},{"name":"max_processors","class":"MaxProcessorCountStatistic"},{"name":"utilization","class":"UtilizationStatistic"},{"name":"expansion_factor","class":"ExpansionFactorStatistic"},{"name":"normalized_avg_processors","class":"NormalizedAverageProcessorCountStatistic"},{"name":"avg_job_size_weighted_by_cpu_hours","class":"JobSizeWeightedByCPUHours"},{"name":"active_resource_count","class":"ActiveResourceCountStatistic"}]}}}'
                    )
                )
            )
        );
    }
}
