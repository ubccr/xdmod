<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use Xdmod\Config;

class UsageExplorerTest extends \PHPUnit_Framework_TestCase
{

    protected $testFiles;

    protected function setUp()
    {
        $this->helper = new \TestHarness\XdmodTestHelper();
        $this->testFiles = new TestFiles(__DIR__ . '/../../');
    }
    
    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../');
        }
        return $this->testFiles;
    }

    /**
     * @dataProvider corruptDataProvider
     */
    public function testCorruptRequestData($input, $expectedMessage)
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 400);
        $this->assertEquals($response[0]['message'], $expectedMessage);
    }

    public function corruptDataProvider()
    {
        $defaultJson = <<<EOF
        {
            "public_user": "true",
            "realm": "Jobs",
            "group_by": "none",
            "start_date": "2017-05-01",
            "end_date": "2017-05-31",
            "statistic": "job_count",
            "operation": "get_charts",
            "controller_module": "user_interface"
        }
EOF;
        $tests = array();

        $input = json_decode($defaultJson, true);
        unset($input['end_date']);
        $tests[] = array($input, 'end_date param is not in the correct format of Y-m-d.');

        $input = json_decode($defaultJson, true);
        unset($input['start_date']);
        $tests[] = array($input, 'start_date param is not in the correct format of Y-m-d.');

        $input = json_decode($defaultJson, true);
        $input['group_by'] = 'elephants';
        $tests[] = array($input, 'Query: Unknown Group By "elephants" Specified');

        return $tests;
    }

    /**
     * Checks the structure of the get_tabs endpoint.
     */
    public function testGetTabs()
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, array('operation' => 'get_tabs', 'public_user' => 'true'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);


        $dwdata = $response[0];

        $this->assertArrayHasKey('totalCount', $dwdata);
        $this->assertArrayHasKey('data', $dwdata);
        $this->assertEquals($dwdata['totalCount'], count($dwdata['data']));

        foreach($dwdata['data'] as $entry)
        {
            $this->assertArrayHasKey('tabs', $entry);

            // This is a funny one - the data is actually json encoded.
            $tabdata = json_decode($entry['tabs'], true);

            $this->assertTrue(count($tabdata) > 0);
        }
    }

    /*
     * Check that the System Username plots are not available to the public user
     */
    public function testSystemUsernameAccess()
    {
        $defaultJson = <<<EOF
{
    "public_user": "true",
    "realm": "Jobs",
    "group_by": "username",
    "statistic": "job_count",
    "start_date": "2017-05-01",
    "end_date": "2017-05-31",
    "operation": "get_charts",
    "controller_module": "user_interface"
}
EOF;

        $response = $this->helper->post('/controllers/user_interface.php', null, json_decode($defaultJson, true));

        $expectedErrorMessage = <<<EOF
Your user account does not have permission to view the requested data.  If you
believe that you should be able to see this information, then please select
"Submit Support Request" in the "Contact Us" menu to request access.
EOF;

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 403);
        $this->assertEquals($response[0]['message'], $expectedErrorMessage);
    }

    public function testAggregateView()
    {
         $defaultJson = <<<EOF
{
    "public_user": "true",
    "realm": "Jobs",
    "group_by": "none",
    "statistic": "avg_wallduration_hours",
    "start_date": "2016-01-01",
    "end_date": "2017-12-31",
    "operation": "get_charts",
    "timeframe_label": "User Defined",
    "scale": "1",
    "aggregation_unit": "Auto",
    "dataset_type": "aggregate",
    "thumbnail": "n",
    "query_group": "tg_usage",
    "display_type": "line",
    "combine_type": "side",
    "limit": "10",
    "offset": "0",
    "log_scale": "n",
    "show_guide_lines": "y",
    "show_trend_line": "n",
    "show_error_bars": "n",
    "show_aggregate_labels": "n",
    "show_error_labels": "n",
    "hide_tooltip": "false",
    "show_title": "y",
    "width": "1377",
    "height": "590",
    "legend_type": "bottom_center",
    "font_size": "3",
    "interactive_elements": "y",
    "controller_module": "user_interface"
}
EOF;
        $response = $this->helper->post('/controllers/user_interface.php', null, json_decode($defaultJson, true));

        $this->assertNotFalse(strpos($response[1]['content_type'], 'text/plain'));
        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode(\TestHarness\UsageExplorerHelper::demanglePlotData($response[0]), true);

        $dataseries = $plotdata['data'][0]['hc_jsonstore']['series'];

        $this->assertCount(1, $dataseries);
        $this->assertArrayHasKey('data', $dataseries[0]);
        $this->assertCount(1, $dataseries[0]['data']);
        $this->assertArrayHasKey('y', $dataseries[0]['data'][0]);

        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('controllers', 'aggregate_view')
        );

        $this->assertEquals($expected['value'], $dataseries[0]['data'][0]['y'], '', 1.0e-6);
    }

    /**
     * @dataProvider errorBarDataProvider
     */
    public function testErrorBars($input, $expected)
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $this->assertNotFalse(strpos($response[1]['content_type'], 'text/plain'));
        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode(\TestHarness\UsageExplorerHelper::demanglePlotData($response[0]), true);

        $this->assertArrayHasKey('chart_settings', $plotdata['data'][0]);

        $settings = json_decode($plotdata['data'][0]['chart_settings'], true);

        $this->assertArrayHasKey('enable_errors', $settings);
        $this->assertEquals($expected, $settings['enable_errors']);
    }

    public function errorBarDataProvider()
    {
        $baseJson = <<<EOF
{
    "public_user": "true",
    "realm": "Jobs",
    "group_by": "none",
    "statistic": "avg_wallduration_hours",
    "start_date": "2016-12-25",
    "end_date": "2017-01-02",
    "operation": "get_charts",
    "timeframe_label": "User Defined",
    "scale": "1",
    "aggregation_unit": "Auto",
    "dataset_type": "timeseries",
    "thumbnail": "n",
    "query_group": "tg_usage",
    "display_type": "line",
    "combine_type": "side",
    "limit": "10",
    "offset": "0",
    "log_scale": "n",
    "show_guide_lines": "y",
    "show_trend_line": "n",
    "show_error_bars": "n",
    "show_aggregate_labels": "n",
    "show_error_labels": "y",
    "hide_tooltip": "false",
    "show_title": "y",
    "width": "1377",
    "height": "590",
    "legend_type": "bottom_center",
    "font_size": "3",
    "interactive_elements": "y",
    "controller_module": "user_interface"
}
EOF;
        $baseSettings = json_decode($baseJson, true);

        $ret = array(
            array($baseSettings, 'y')
        );

        $baseSettings['statistic'] = 'job_count';
        $ret[] = array($baseSettings, 'n');

        $baseSettings['statistic'] = 'avg_node_hours';
        $ret[] = array($baseSettings, 'y');

        $baseSettings['group_by'] = 'nsfdirectorate';
        $ret[] = array($baseSettings, 'n');

        return $ret;
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExport($chartConfig, $expectedMimeType, $expectedFinfo)
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, $chartConfig);

        $this->assertEquals($response[1]['http_code'], 200);

        $this->assertEquals($expectedMimeType, $response[1]['content_type']);

        // Check the mime type of the file is correct.
        $finfo = finfo_open(FILEINFO_MIME);
        $this->assertEquals($expectedFinfo, finfo_buffer($finfo, $response[0]));
    }

    public function exportDataProvider()
    {
        $baseJson = <<<EOF
{
    "public_user": "true",
    "realm": "Jobs",
    "group_by": "none",
    "statistic": "active_person_count",
    "start_date": "2016-12-20",
    "end_date": "2017-01-05",
    "timeframe_label": "User Defined",
    "scale": "2.5",
    "aggregation_unit": "Auto",
    "dataset_type": "timeseries",
    "thumbnail": "n",
    "query_group": "tg_usage",
    "display_type": "line",
    "combine_type": "stack",
    "limit": "10",
    "offset": "0",
    "log_scale": "n",
    "show_guide_lines": "y",
    "show_trend_line": "n",
    "show_error_bars": "n",
    "show_aggregate_labels": "n",
    "show_error_labels": "n",
    "hide_tooltip": "false",
    "show_title": "y",
    "width": "540",
    "height": "288",
    "legend_type": "bottom_center",
    "font_size": "13",
    "format": "pdf",
    "inline": "n",
    "operation": "get_data"
}
EOF;

        $baseSettings = json_decode($baseJson, true);

        $ret = array(
            array($baseSettings, 'application/pdf', 'application/pdf; charset=binary'),
        );

        $baseSettings['scale'] = '1';
        $baseSettings['font_size'] = '3';
        $baseSettings['format'] = 'png';
        $ret[] = array($baseSettings, 'image/png', 'image/png; charset=binary');

        $baseSettings['format'] = 'svg';
        $ret[] = array($baseSettings, 'image/svg+xml', 'text/plain; charset=utf-8');

        $baseSettings['format'] = 'csv';
        $ret[] = array($baseSettings, 'application/xls', 'text/plain; charset=us-ascii');

        $baseSettings['format'] = 'xml';
        $ret[] = array($baseSettings, 'text/xml', 'application/xml; charset=us-ascii');

        return $ret;
    }

    /**
     * Ensure that the public user is able to see all of the realms that are
     * currently installed in this instance of XDMoD.
     */
    public function testPublicUserGetMenus()
    {
        $data = <<< EOF
{
    "operation": "get_menus",
    "public_user": "true",
    "query_group": "tg_usage",
    "node": "category_"
}
EOF;

        $response = $this->helper->post('/controllers/user_interface.php', null, json_decode($data, true));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);

        $menus = $response[0];

        $this->assertTrue(count($menus) > 0, "Public User: get_menus has returned no results.");

        $realms = array('Jobs');
        $this->assertNotEmpty($realms, "Unable to retrieve realms from datawarehouse.json");

        $categories = array_reduce(
            $menus,
            function ($carry, $item) {
                if (isset($item['category'])) {
                    if (!in_array($item['category'], $carry)) {
                        $carry[] = $item['category'];
                    }
                }
                return $carry;
            },
            array()
        );

        $this->assertTrue(count($categories) >= 1, "There were no 'menus' that had a category propery, this is unexpected.");

        $realmCategoryDiff = array_diff($realms, $categories);

        $this->assertEmpty($realmCategoryDiff, "There were realms in datawarehouse.json that were not returned by get_menus.");
    }
}
