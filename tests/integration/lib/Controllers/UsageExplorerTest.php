<?php

namespace IntegrationTests\Controllers;

use IntegrationTests\TokenAuthTest;
use IntegrationTests\TestHarness\UsageExplorerHelper;
use IntegrationTests\TestHarness\XdmodTestHelper;

function arrayRecursiveDiff($a1, $a2) {
    $retval = [];

    foreach ($a1 as $key => $value) {
        if (array_key_exists($key, $a2)) {
            if (is_array($value)) {
                $result = arrayRecursiveDiff($value, $a2[$key]);
                if (count($result)) {
                    $retval[$key] = $result;
                }
            } else {
                if ($value != $a2[$key]) {
                    $retval[$key] = $value;
                }
            }
        } else {
            $retval[$key] = $value;
        }
    }
    return $retval;
}

class UsageExplorerTest extends TokenAuthTest
{
    private static $publicView;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$publicView = ["public_user" => "true", "realm" => "Jobs", "group_by" => "none", "start_date"=> "2017-05-01", "end_date" => "2017-05-31", "statistic" => "job_count", "operation" => "get_charts", "controller_module"=> "user_interface"];
    }

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider corruptDataProvider
     */
    public function testCorruptRequestData($input, $expectedMessage): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(400, $response[1]['http_code']);
        $this->assertEquals($expectedMessage, $response[0]['message']);
    }

    public function corruptDataProvider()
    {
        $tests = [];
        $view = ["public_user" => "true", "realm" => "Jobs", "group_by" => "none", "start_date"=> "2017-05-01", "end_date" => "2017-05-31", "statistic" => "job_count", "operation" => "get_charts", "controller_module"=> "user_interface"];

        $view['start_date'] = null;
        $tests[] = [$view, 'missing required start_date parameter'];

        $view['start_date'] = '2017-05-01';
        $view['end_date'] = null;
        $tests[] = [$view, 'missing required end_date parameter'];

        $view['end_date'] = 'Yesterday';
        $tests[] = [$view, 'end_date param is not in the correct format of Y-m-d.'];

        $view['start_date'] = 'Tomorrow';
        $view['end_date'] = '2017-05-01';
        $tests[] = [$view, 'start_date param is not in the correct format of Y-m-d.'];

        $view['group_by'] = "elephants";
        $tests[] = [$view, "No GroupBy found with id 'elephants' in Realm: Jobs"];

        return $tests;
    }

    /**
     * Checks the structure of the get_tabs endpoint.
     * @dataProvider corruptDataProvider
     */
    public function testGetTabs(): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $response = $this->helper->post('/controllers/user_interface.php', null, ['operation' => 'get_tabs', 'public_user' => 'true']);

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
     *
     */
    public function testSystemUsernameAccess(): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        self::$publicView['group_by'] = "username";
        $response = $this->helper->post('/controllers/user_interface.php', null, self::$publicView);

        $expectedErrorMessage = <<<EOF
Your user account does not have permission to view the requested data.  If you
believe that you should be able to see this information, then please select
"Submit Support Request" in the "Contact Us" menu to request access.
EOF;

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 403);
        $this->assertEquals($response[0]['message'], $expectedErrorMessage);
    }

    public function aggregateDataProvider()
    {
        $view = ["public_user" => "true", "realm" => "Jobs", "group_by" => "none", "statistic" => "avg_wallduration_hours", "start_date" => "2016-01-01", "end_date" => "2016-12-31", "operation" => "get_charts", "timeframe_label" => "User Defined", "scale" => "1", "aggregation_unit" => "Auto", "dataset_type" => "aggregate", "thumbnail" => "n", "query_group" => "tg_usage", "display_type" => "line", "combine_type" => "side", "limit" => "10", "offset" => "0", "log_scale" => "n", "show_guide_lines" => "y", "show_trend_line" => "n", "show_error_bars" => "n", "show_aggregate_labels" => "n", "show_error_labels" => "n", "hide_tooltip" => "false", "show_title" => "y", "width" => "1377", "height" => "590", "legend_type" => "bottom_center", "font_size" => "3", "controller_module" => "user_interface"];

        $view['start_date'] = "2016-01-01";
        $view['end_date'] = "2016-12-31";
        $validRange = [$view, 1.97829913];

        $view['start_date'] = "2017-01-01";
        $view['end_date'] = "2017-12-31";
        $validStart = [$view, 1.01283296];

        $view['start_date'] = "2015-01-01";
        $view['end_date'] = "2015-12-31";
        $past = [$view, null];

        $view['start_date'] = "2018-01-01";
        $view['end_date'] = "2018-12-31";
        $future = [$view, null];

        return [$validRange, $validStart, $past, $future];
    }

    public function provideJsonExport() {

        $input = ['public_user' => 'true', 'realm' => 'Jobs', 'group_by' => 'none', 'statistic' => 'max_processors', 'start_date' => '2016-12-01', 'end_date' => '2016-12-31', 'timeframe_label' => 'User%20Defined', 'scale' => '1', 'aggregation_unit' => 'Auto', 'dataset_type' => 'timeseries', 'thumbnail' => 'n', 'query_group' => 'tg_usage', 'display_type' => 'datasheet', 'combine_type' => 'side', 'limit' => '10', 'offset' => '0', 'log_scale' => 'n', 'show_guide_lines' => 'y', 'show_trend_line' => 'n', 'show_error_bars' => 'n', 'show_aggregate_labels' => 'n', 'show_error_labels' => 'n', 'hide_tooltip' => 'false', 'show_title' => 'y', 'width' => '876', 'height' => '592', 'legend_type' => 'bottom_center', 'font_size' => '3', 'drilldowns' => '%5Bobject%20Object%5D', 'none' => '-9999', 'format' => 'jsonstore', 'operation' => 'get_data'];

        $expected = json_decode(<<<EOF
{
    "metaData": {
        "totalProperty": "total",
        "messageProperty": "message",
        "root": "records",
        "id": "id",
        "fields": [],
        "sortInfo": {
            "field": "day",
            "direction": "asc"
        }
    },
    "message": "",
    "success": true,
    "total": "",
    "records": [],
    "columns": [],
    "restrictedByRoles": false,
    "roleRestrictionsMessage": ""
}
EOF
, true);

        $data = [];

        $input['group_by']  = 'none';
        $expected['message'] = '<ul><li><b>Screwdriver</b>: Summarizes Jobs data reported to the Screwdriver database.</li><li><b>Job Size: Max (Core Count)</b>: The maximum size Screwdriver job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.</li></ul>';
        $expected['total'] = 10;
        $fieldCount = 2;
        $recordCount = 10;

        $data[] = [$input, $expected, $fieldCount, $recordCount];

        $input['group_by']  = 'pi';
        $expected['message'] = '<ul><li><b>PI</b>: The principal investigator of a project.</li><li><b>Job Size: Max (Core Count)</b>: The maximum size Screwdriver job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.</li></ul>';
        $expected['total'] = 145;
        $fieldCount = 42;
        $recordCount = 10;

        $data[] = [$input, $expected, $fieldCount, $recordCount];

        $input['pi_filter'] = "5,7,32,11";
        $expected['total'] = 18;
        $fieldCount = 5;
        $recordCount = 10;
        $data[] = [$input, $expected, $fieldCount, $recordCount];

        return $data;
    }
    /**
     * @dataProvider provideJsonExport
     */
    public function testJsonExport($input, $expected, $fieldCount, $recordCount): void
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $got = json_decode($response[0], true);

        // Check correct syntax of fields argument.

        $fields = $got['metaData']['fields'];
        $this->assertCount($fieldCount, $fields);

        $firstField = array_shift($fields);
        $this->assertEquals('day', $firstField['name']);
        $this->assertEquals('string', $firstField['type']);
        $this->assertEquals('DESC', $firstField['sortDir']);

        foreach($fields as $field) {
            $this->assertMatchesRegularExpression('/dimension_column_\d+/', $field['name']);
            $this->assertEquals('float', $field['type']);
            $this->assertEquals('DESC', $field['sortDir']);
        }

        // Check correct syntax of records argument.
        $records = $got['records'];
        $this->assertCount($recordCount, $records);
        foreach($records as $record) {
            $this->assertMatchesRegularExpression('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $record['day']);
            foreach($record as $rkey => $rval) {
                $this->assertMatchesRegularExpression('/^day|dimension_column_\d+$/', $rkey);
            }
        }

        // Check correct syntax of columns argument.
        $columns = $got['columns'];
        $this->assertCount($fieldCount, $columns);
        $firstCol = array_shift($columns);

        $this->assertEquals(['header' => 'Day', 'width' => 150, 'dataIndex' => 'day', 'sortable' => 1, 'editable' => false, 'locked' => 1], $firstCol);

        foreach($columns as $column) {
            $this->assertMatchesRegularExpression('/^\[[^\]]+\] Job Size: Max \(Core Count\)$/', $column['header']);
            $this->assertEquals(140, $column['width']);
            $this->assertMatchesRegularExpression('/^dimension_column_\d+$/', $column['dataIndex']);
            $this->assertEquals(1, $column['sortable']);
            $this->assertEquals(false, $column['editable']);
            $this->assertEquals('right', $column['align']);
            $this->assertEquals('numbercolumn', $column['xtype']);
            $this->assertEquals('0,000', $column['format']);
        }

        // These have been checked - zero out so allow checking
        // of the rest
        $got['metaData']['fields'] = [];
        $got['columns'] = [];
        $got['records'] = [];
        $result = arrayRecursiveDiff($got, $expected);
        $this->assertEmpty($result);
    }

    /**
     * @dataProvider aggregateDataProvider
     */
    public function testAggregateViewValidData($view, $expected): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $response = $this->helper->post('/controllers/user_interface.php', null, $view);

        $this->assertNotFalse(strpos($response[1]['content_type'], 'text/plain'));
        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode($response[0], true);
        $dataseries = $plotdata['data'][0]['hc_jsonstore']['series'];

        $this->assertCount(1, $dataseries);
        $this->assertArrayHasKey('data', $dataseries[0]);
        $this->assertCount(1, $dataseries[0]['data']);
        $this->assertArrayHasKey('y', $dataseries[0]['data'][0]);

        $this->assertEquals($expected, $dataseries[0]['data'][0]['y'], '', 1.0e-6);
    }

    /**
     * @dataProvider errorBarDataProvider
     */
    public function testErrorBars($input, $expected): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $this->assertNotFalse(strpos($response[1]['content_type'], 'text/plain'));
        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode(UsageExplorerHelper::demanglePlotData($response[0]), true);

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
    "controller_module": "user_interface"
}
EOF;
        $baseSettings = json_decode($baseJson, true);

        $ret = [[$baseSettings, 'y']];

        $baseSettings['statistic'] = 'job_count';
        $ret[] = [$baseSettings, 'n'];

        $baseSettings['statistic'] = 'avg_node_hours';
        $ret[] = [$baseSettings, 'y'];

        $baseSettings['group_by'] = 'nsfdirectorate';
        $ret[] = [$baseSettings, 'n'];

        return $ret;
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExport($chartConfig, $expectedMimeType, $expectedFinfo): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $response = $this->helper->post('/controllers/user_interface.php', null, $chartConfig);

        $this->assertEquals($response[1]['http_code'], 200);

        $actualContentType = $response[1]['content_type'];
        $this->assertEquals($expectedMimeType, $actualContentType);

        $actualFinfo = finfo_buffer(finfo_open(FILEINFO_MIME), $response[0]);
        $this->assertEquals($expectedFinfo, $actualFinfo);
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

        $ret = [[$baseSettings, 'application/pdf', 'application/pdf; charset=binary']];

        $baseSettings['scale'] = '1';
        $baseSettings['font_size'] = '3';
        $baseSettings['format'] = 'png';
        $ret[] = [$baseSettings, 'image/png', 'image/png; charset=binary'];

        $baseSettings['format'] = 'csv';
        $ret[] = [$baseSettings, 'application/xls', 'text/plain; charset=us-ascii'];

        /**
         * The following array of expected values are necessary due to `finfo` returning different
         * values for the same input from PHP 5.4 -> PHP 7.2. The response mimetype has also changed
         * when returning xml from centos7 -> centos8, again for the same input.
         */
        $osSpecificExpected = ['svg' => ['centos8' => ['image/svg+xml', 'image/svg; charset=utf-8'], 'centos7' => ['image/svg+xml', 'text/plain; charset=utf-8']], 'xml' => ['centos8' => ['text/xml;charset=UTF-8', 'text/xml; charset=us-ascii'], 'centos7' => ['text/xml', 'application/xml; charset=us-ascii']]];

        // Try to determine what os / version we're operating on ( CentOS only ).
        $osInfo = false;
        try {
            $osInfo = parse_ini_file('/etc/os-release');
        } catch (\Exception) {
            // if we don't have access to OS related info then that's fine, we'll just use the default expected.json
        }

        // If we do have some osInfo then make sure we have the distribution ( `ID` ) and the version (`VERSION_ID`)
        // and continue on
        if ($osInfo !== false && isset($osInfo['VERSION_ID']) && isset($osInfo['ID'])) {
            $osIdentifier = implode("", [$osInfo['ID'], $osInfo['VERSION_ID']]);
            foreach($osSpecificExpected as $fileType => $fileTypeExpected) {
                $baseSettings['format'] = $fileType;
                if (isset($fileTypeExpected[$osIdentifier])) {
                    [$mimetype, $finfo] = $fileTypeExpected[$osIdentifier];
                    $ret[] = [$baseSettings, $mimetype, $finfo];
                }
            }
        } else {
            throw new Exception('Unable to determine which OS these integration tests are being run on.');
        }

        return $ret;
    }

    /**
     * Ensure that the public user is able to see all of the realms that are
     * currently installed in this instance of XDMoD.
     */
    public function testPublicUserGetMenus(): void
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

        $this->assertNotEmpty(self::$XDMOD_REALMS, "Unable to retrieve realms from datawarehouse.json");

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
            []
        );

        $this->assertTrue(count($categories) >= 1, "There were no 'menus' that had a category propery, this is unexpected.");

        $realmCategoryDiff = array_udiff(self::$XDMOD_REALMS, $categories, 'strcasecmp');

        $this->assertEmpty($realmCategoryDiff, "There were realms in datawarehouse.json that were not returned by get_menus.");
    }

    /**
     * @dataProvider dataFilteringProvider
     * @group DataAccess
     */
    public function testDataFiltering($user, $chartSettings, $expectedNames): void
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->helper->authenticate($user);

        $response = $this->helper->post('/controllers/user_interface.php', null, $chartSettings);

        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode(UsageExplorerHelper::demanglePlotData($response[0]), true);

        $this->assertTrue($plotdata['success']);

        $this->assertCount(count($expectedNames), $plotdata['data'][0]['hc_jsonstore']['series']);

        foreach($plotdata['data'][0]['hc_jsonstore']['series'] as $seriesIdx => $seriesData) {
            $this->assertEquals(($seriesIdx + 1) . '. ' . $expectedNames[$seriesIdx] . '*', $seriesData['name']);
        }
    }

    /**
     */
    public function dataFilteringProvider()
    {
        $tests = [];

        $chartSettings = ['public_user' => 'false', 'realm' => 'Jobs', 'group_by' => 'username', 'statistic' => 'running_job_count', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01', 'timeframe_label' => 'User Defined', 'scale' => '1', 'aggregation_unit' => 'Auto', 'dataset_type' => 'timeseries', 'thumbnail' => 'n', 'query_group' => 'tg_usage', 'display_type' => 'line', 'combine_type' => 'stack', 'limit' => '10', 'offset' => '0', 'log_scale' => 'n', 'show_guide_lines' => 'y', 'show_trend_line' => 'n', 'show_error_bars' => 'n', 'show_aggregate_labels' => 'n', 'show_error_labels' => 'n', 'hide_tooltip' => 'false', 'show_title' => 'y', 'width' => '1529', 'height' => '706', 'legend_type' => 'bottom_center', 'font_size' => '3', 'operation' => 'get_charts', 'controller_module' => 'user_interface'];

        $expectedNames = ['swath', 'savsp', 'litst', 'ybsbu', 'ovenb', 'sante'];

        $tests[] = ['pi', $chartSettings, $expectedNames];

        $chartSettings['limit'] = 2;

        $expectedNames = ['swath', 'savsp', 'Avg of 4 Others'];
        $tests[] = ['pi', $chartSettings, $expectedNames];

        $chartSettings['limit'] = 10;
        $expectedNames = ['whimb'];
        $tests[] = ['usr', $chartSettings, $expectedNames];

        return $tests;
    }

    /**
     * This test exercises the new Usage feature of allowing non-numeric values for a certain subset
     * of the currently supported filters ( when those filters are supplied as '<filter_key>_filter' ).
     * Notice that the corresponding string value is quoted w/ either single or double quotes. This
     * is required so that we can tell the difference between strings and numbers.
     *
     * Examples:
     *   pi=40            => pi_filter="taifl"
     *   resource=1       => resource_filter='frearson'
     *   project_filter=2 => project_filter="zealous"
     *
     * @dataProvider provideFilterIdLookup
     *
     * @param $options
     * @throws \Exception if there is a problem authenticating
     */
    public function testFilterIdLookup($options): void
    {
        //TODO: Needs further integration for storage realm
        if (self::$XDMOD_REALMS == ["storage"])  {
            $this->markTestSkipped('Needs realm integration.');
        }

        $user = $options['user'];
        $data = $options['data'];
        $helper = $options['helper'];

        $expectedValue = $options['expected']['value'];
        $expectedXpath = $options['expected']['xpath'] ?? null;

        $originalResults = $helper->post('controllers/user_interface.php', null, $data);

        if ($expectedXpath !== null) {
            $xml = simplexml_load_string($originalResults[0]);

            $xmlValues = $xml->xpath($expectedXpath);

            $actualValue = is_array($expectedValue) ? $xmlValues : array_pop($xmlValues);

            $expectedCount = count($expectedValue);
            $actualCount = count($actualValue);
            $this->assertEquals($expectedCount, $actualCount, "Number of actual values does not match the expected.\n" . print_r($data, true));

            for ($i = 0; $i < count($expectedValue); $i++) {
                $expected = $expectedValue[$i];
                $actual = (string)$actualValue[$i];
                $this->assertEquals($expected, $actual);
            }
        } else {
            $actual = $originalResults[0];

            foreach($expectedValue as $key => $value) {
                $this->assertArrayHasKey($key, $actual);

                if (is_array($value)) {
                    // If we have an array value just make sure that the keys from the expected ==
                    // the keys from the actual
                    $this->assertEquals(array_keys($value), array_keys($actual[$key]));
                }
            }
        }

        if (isset($options['last']) && $user !== 'pub') {
            $helper->logout();
        }
    }

    /**
     * Provides the test data for testFilterIdLookup.
     *
     * NOTE: string values supplied to the '*_filter' parameters need to be quoted w/ single or
     * double quotes to be treated as strings.
     *
     * @return array
     * in the form:
     * array(
     *   array(
     *     array(
     *       'user'     => 'the user under whom this test should be run',
     *       'data'     => 'the POST data for the request that will be issued',
     *       'expected' => 'information that describes what values we expect and how to get them'
     *     )
     *   ),
     *   array(
     *     array(
     *       'user'     => ... ,
     *       'data'     => ... ,
     *       'expected' => ...
     *     )
     *   ),
     *   etc. etc.
     * )
     */
    public function provideFilterIdLookup()
    {

        $users = ['pub', 'cd', 'cs', 'pi', 'usr'];

        // Base POST parameters for the request we are testing.
        $baseData = ['dataset_type' => 'aggregate', 'query_group' => 'tg_usage', 'limit' => 10, 'format' => 'xml', 'operation' => 'get_data'];

        // Per realm test scenario data.
        //TODO: Needs further integration for storage realm
        $realmData = [];

        if (in_array("cloud", parent::getRealms())) {
            array_push(
                $realmData,
                // Cloud, single value filter tests
                ['realm' => 'Cloud', 'filters' => [[['project' => 'zealous'], ['project_filter' => 'zealous'], ['project_filter'=> 'zealous']]], 'expected' => ['value' => ['zealous', '1755.8894'], 'xpath' => '//rows//row//cell/value'], 'additional_data' => ['group_by' => 'project', 'statistic' => 'cloud_core_time', 'start_date' => '2018-04-01', 'end_date' => '2018-05-01']],
                // Cloud, multi-value filter tests. ( Note: at time of writing, only one project has any
                // core_time in the docker image. )
                ['realm' => 'Cloud', 'filters' => [[['project_filter' => "zealous, youthful, zen"], ['project_filter'=> 'zealous, youthful, zen']]], 'expected' => ['value' => ['zealous', '1755.8894'], 'xpath' => '//rows//row//cell/value'], 'additional_data' => ['group_by' => 'project', 'statistic' => 'cloud_core_time', 'start_date' => '2018-04-01', 'end_date' => '2018-05-01']]
            );
        };

        if (in_array("jobs", parent::getRealms())) {
            array_push(
                $realmData,
                // Jobs, single value filter tests
                ['realm' => 'Jobs', 'filters' => [[['resource' => '1'], ['resource_filter'=> '1'], ['resource_filter' => '"frearson"']], [['pi' => '40'], ['pi_filter' => '40'], ['pi_filter' => '"taifl"']]], 'expected' => ['value' => ['frearson', '78142.2133'], 'xpath' => '//rows//row//cell/value'], 'additional_data' => ['group_by' => 'resource', 'statistic' => 'total_cpu_hours', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01']],
                // Jobs, multi-value filter tests
                ['realm' => 'Jobs', 'filters' => [[['resource' => '4,1'], ['resource_filter'=> '1,4'], ['resource_filter' => '\'frearson\',"pozidriv"']], [['pi' => '40,22'], ['pi_filter' => '22,40'], ['pi_filter' => '"taifl",\'henha\'']]], 'expected' => ['value' => ['frearson', '78142.2133', 'pozidriv', '25358.4119'], 'xpath' => '//rows//row//cell//value'], 'additional_data' => ['group_by' => 'resource', 'statistic' => 'total_cpu_hours', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01']],
                // Jobs, multi-value ( inc. invalid numeric values ) filter tests
                ['realm' => 'Jobs', 'filters' => [[['resource' => '4,1,99999'], ['resource_filter'=> '1,4,99999'], ['resource_filter' => '"frearson","pozidriv"']], [['pi' => '40,22,99999'], ['pi_filter' => '22,40,99999'], ['pi_filter' => '"taifl","henha"']]], 'expected' => ['value' => ['frearson', '78142.2133', 'pozidriv', '25358.4119'], 'xpath' => '//rows//row//cell//value'], 'additional_data' => ['group_by' => 'resource', 'statistic' => 'total_cpu_hours', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01']],
                // Jobs, multi-value ( inc. unknown string values ) filter tests
                ['realm' => 'Jobs', 'filters' => [[['resource_filter' => '"frearson","pozidriv","unknownresource"']], [['pi_filter' => '"taifl",\'henha\',"unknownperson"']]], 'expected' => ['value' => ['success' => false, 'count' => 0, 'total' => 0, 'totalCount'=> 0, 'results' => [], 'data' => [], 'message' => 'Invalid filter value detected: %s', 'code' => 0]], 'additional_data' => ['group_by' => 'resource', 'statistic' => 'total_cpu_hours', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01']]
            );
        }

        /**
         * Generates all combinations of the elements contained within $data.
         *
         * @return array
         */
        function generateCombinations(array $data)
        {
            $results = [[]];
            foreach ($data as $datum) {
                $temp = [];
                foreach ($datum as $subDatum) {
                    foreach($results as $result) {
                        $temp[] = array_merge($result, [$subDatum]);
                    }
                }
                $results = $temp;
            }

            /* Results are currently in the form:
             * array(
             *   array(
             *     array( key 1 => value 1),
             *     array( key 2 => value 2),
             *   )
             * )
             *
             * We need it in the following format:
             * array (
             *    array( key 1 => value 1, key 2 => value 2 ),
             *    array( key 1 => value 1, key 3 => value 3 ),
             *    etc, etc.
             * )
             *
             * This is so that each of the second level arrays are suitable for merging into an
             * existing associative array.
             */
            return array_reduce(
                $results,
                function ($carry, $item) {
                    $results = [];
                    foreach($item as $subItem) {
                        foreach($subItem as $key => $value) {
                            $results[$key] = $value;
                        }
                    }
                    array_push($carry, $results);
                    return $carry;
                },
                []
            );
        } // allCombinations

        $results = [];
        foreach($users as $user) {
            $helper = new XdmodTestHelper();
            if ($user !== 'pub') {
                $helper->authenticate($user);
            }
            foreach($realmData as $realmDatum) {
                $realm = $realmDatum['realm'];
                $filterCombos = generateCombinations($realmDatum['filters']);

                foreach($filterCombos as $filterCombo) {
                    $requestData = array_merge($baseData, $filterCombo);
                    $requestData['realm'] = $realm;

                    // Because a PHP boolean is interpreted as a 1 / 0 when building the request
                    // parameters.
                    $requestData['public_user'] = $user === 'pub' ? 'true' : 'false';

                    $requestData = array_merge($requestData, $realmDatum['additional_data']);

                    $results[] = [['user' => $user, 'helper' => $helper, 'data' => $requestData, 'expected' => $realmDatum['expected']]];
                }
            }
            $results[count($results) - 1]['last'] = true;
        }


        return $results;
    }

    /**
     * @dataProvider provideGetTimeseriesDataCsv
     */
    public function testGetTimeseriesDataCsv(
        $groupBy,
        $groupByName,
        $groups,
        $filterKey,
        $filterKeyName,
        $filterValue,
        $filterValueName,
        $startDate,
        $endDate,
        $isEmpty
    ): void {
        if (!in_array('jobs', self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        $this->helper->authenticate('cd');
        $data = ['operation' => 'get_data', 'start_date' => $startDate, 'end_date' => $endDate, 'realm' => 'Jobs', 'statistic' => 'active_person_count', 'group_by' => $groupBy, 'dataset_type' => 'timeseries', 'format' => 'csv'];
        if (isset($filterKey)) {
            $data[$filterKey] = $filterValue;
            $expectedParameterLine = "\"$filterKeyName =  $filterValueName\"";
        } else {
            $expectedParameterLine = '';
        }
        $response = $this->helper->post(
            '/controllers/user_interface.php',
            null,
            $data
        );
        $statName = 'Number of Users: Active';
        $columns = 'Day';
        if (!$isEmpty) {
            $columns .= ',';
            if (isset($groupByName)) {
                $otherColumns = [];
                foreach ($groups as $group) {
                    $otherColumns[] = "\"[$group] $statName\"";
                }
                $columns .= implode(',', $otherColumns);
            } else {
                $columns .= "\"[$groups] $statName\"";
            }
        }
        $this->assertSame('application/xls', $response[1]['content_type']);
        $this->assertSame(200, $response[1]['http_code']);
        if (isset($groupByName)) {
            $statName .= ': by ' . $groupByName;
        }
        $expectedOutput = <<<END
title
"$statName"
parameters
$expectedParameterLine
start,end
$startDate,$endDate
---------
$columns
END;
        $expected = preg_split('/\n/', $expectedOutput);
        $actual = preg_split('/\n/', $response[0]);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertSame(
                $expected[$i],
                $actual[$i],
                'Output line ' . $i
            );
        }
        $this->helper->logout();
    }

    public function provideGetTimeseriesDataCsv()
    {
        $noGroupBy = ['none', null, 'Screwdriver'];
        $withGroupBy = ['resource', 'Resource', ['robertson', 'pozidriv', 'frearson', 'mortorq', 'phillips']];
        $noFilter = [null, null, null, null];
        $withFilter = ['provider_filter', 'Service Provider', 1, 'screw'];
        $emptyData = ['9999-12-01', '9999-12-31', true];
        $nonEmptyData = ['2016-12-01', '2016-12-31', false];
        $arrays = [];
        foreach ([$noGroupBy, $withGroupBy] as $groupParams) {
            foreach ([$noFilter, $withFilter] as $filterParams) {
                foreach ([$emptyData, $nonEmptyData] as $emptinessParams) {
                    $arrays[] = array_merge(
                        $groupParams,
                        $filterParams,
                        $emptinessParams
                    );
                }
            }
        }
        return $arrays;
    }

    /**
     * @dataProvider provideTokenAuthTestData
     */
    public function testGetDataTokenAuth($role, $tokenType): void
    {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            [
                'path' => 'controllers/user_interface.php',
                'method' => 'post',
                'params' => null,
                'data' => ['operation' => 'get_data'],
                'endpoint_type' => 'controller',
                'authentication_type' => 'token_optional'
            ],
            [
                'status_code' => 500,
                'body_validator' => parent::validateErrorResponseBody(
                    'One or more realms must be specified.',
                    0
                )
            ]
        );
    }
}
