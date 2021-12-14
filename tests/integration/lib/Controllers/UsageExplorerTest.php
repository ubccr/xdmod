<?php

namespace IntegrationTests\Controllers;

use TestHarness\XdmodTestHelper;
use IntegrationTests\BaseTest;

function arrayRecursiveDiff($a1, $a2) {
    $retval = array();

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

class UsageExplorerTest extends BaseTest
{
    private static $publicView;

    public static function setUpBeforeClass()
    {
        self::$publicView = array(
            "public_user" => "true",
            "realm" => "Jobs",
            "group_by" => "none",
            "start_date"=> "2017-05-01",
            "end_date" => "2017-05-31",
            "statistic" => "job_count",
            "operation" => "get_charts",
            "controller_module"=> "user_interface"
        );
    }

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new \TestHarness\XdmodTestHelper();
    }

    /**
     * @dataProvider corruptDataProvider
     */
    public function testCorruptRequestData($input, $expectedMessage)
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
        $tests = array();
        $view = array(
            "public_user" => "true",
            "realm" => "Jobs",
            "group_by" => "none",
            "start_date"=> "2017-05-01",
            "end_date" => "2017-05-31",
            "statistic" => "job_count",
            "operation" => "get_charts",
            "controller_module"=> "user_interface"
        );

        $view['end_date'] = null;
        $tests[] = array($view, 'end_date param is not in the correct format of Y-m-d.');

        $view['start_date'] = null;
        $tests[] = array($view, 'start_date param is not in the correct format of Y-m-d.');

        $view['group_by'] = "elephants";
        $tests[] = array($view, "No GroupBy found with id 'elephants' in Realm: Jobs");

        return $tests;
    }

    /**
     * Checks the structure of the get_tabs endpoint.
     * @dataProvider corruptDataProvider
     */
    public function testGetTabs()
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

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
     *
     */
    public function testSystemUsernameAccess()
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
        $view = array(
            "public_user" => "true",
            "realm" => "Jobs",
            "group_by" => "none",
            "statistic" => "avg_wallduration_hours",
            "start_date" => "2016-01-01",
            "end_date" => "2016-12-31",
            "operation" => "get_charts",
            "timeframe_label" => "User Defined",
            "scale" => "1",
            "aggregation_unit" => "Auto",
            "dataset_type" => "aggregate",
            "thumbnail" => "n",
            "query_group" => "tg_usage",
            "display_type" => "line",
            "combine_type" => "side",
            "limit" => "10",
            "offset" => "0",
            "log_scale" => "n",
            "show_guide_lines" => "y",
            "show_trend_line" => "n",
            "show_error_bars" => "n",
            "show_aggregate_labels" => "n",
            "show_error_labels" => "n",
            "hide_tooltip" => "false",
            "show_title" => "y",
            "width" => "1377",
            "height" => "590",
            "legend_type" => "bottom_center",
            "font_size" => "3",
            "controller_module" => "user_interface"
        );

        $view['start_date'] = "2016-01-01";
        $view['end_date'] = "2016-12-31";
        $validRange = array($view, 1.97829913);

        $view['start_date'] = "2017-01-01";
        $view['end_date'] = "2017-12-31";
        $validStart = array($view, 1.01283296);

        $view['start_date'] = "2015-01-01";
        $view['end_date'] = "2015-12-31";
        $past = array($view, null);

        $view['start_date'] = "2018-01-01";
        $view['end_date'] = "2018-12-31";
        $future = array($view, null);

        return array(
            $validRange,
            $validStart,
            $past,
            $future
        );
    }

    public function provideJsonExport() {

        $input = array(
            'public_user' => 'true',
            'realm' => 'Jobs',
            'group_by' => 'none',
            'statistic' => 'max_processors',
            'start_date' => '2016-12-01',
            'end_date' => '2016-12-31',
            'timeframe_label' => 'User%20Defined',
            'scale' => '1',
            'aggregation_unit' => 'Auto',
            'dataset_type' => 'timeseries',
            'thumbnail' => 'n',
            'query_group' => 'tg_usage',
            'display_type' => 'datasheet',
            'combine_type' => 'side',
            'limit' => '10',
            'offset' => '0',
            'log_scale' => 'n',
            'show_guide_lines' => 'y',
            'show_trend_line' => 'n',
            'show_error_bars' => 'n',
            'show_aggregate_labels' => 'n',
            'show_error_labels' => 'n',
            'hide_tooltip' => 'false',
            'show_title' => 'y',
            'width' => '876',
            'height' => '592',
            'legend_type' => 'bottom_center',
            'font_size' => '3',
            'drilldowns' => '%5Bobject%20Object%5D',
            'none' => '-9999',
            'format' => 'jsonstore',
            'operation' => 'get_data'
        );

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

        $data = array();

        $input['group_by']  = 'none';
        $expected['message'] = '<ul><li><b>Screwdriver</b>: Summarizes Jobs data reported to the Screwdriver database.</li><li><b>Job Size: Max (Core Count)</b>: The maximum size Screwdriver job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.</li></ul>';
        $expected['total'] = 10;
        $fieldCount = 2;
        $recordCount = 10;

        $data[] = array($input, $expected, $fieldCount, $recordCount);

        $input['group_by']  = 'pi';
        $expected['message'] = '<ul><li><b>PI</b>: The principal investigator of a project.</li><li><b>Job Size: Max (Core Count)</b>: The maximum size Screwdriver job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.</li></ul>';
        $expected['total'] = 145;
        $fieldCount = 42;
        $recordCount = 10;

        $data[] = array($input, $expected, $fieldCount, $recordCount);

        $input['pi_filter'] = "5,7,32,11";
        $expected['total'] = 18;
        $fieldCount = 5;
        $recordCount = 10;
        $data[] = array($input, $expected, $fieldCount, $recordCount);

        return $data;
    }
    /**
     * @dataProvider provideJsonExport
     */
    public function testJsonExport($input, $expected, $fieldCount, $recordCount)
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
            $this->assertRegExp('/dimension_column_\d+/', $field['name']);
            $this->assertEquals('float', $field['type']);
            $this->assertEquals('DESC', $field['sortDir']);
        }

        // Check correct syntax of records argument.
        $records = $got['records'];
        $this->assertCount($recordCount, $records);
        foreach($records as $record) {
            $this->assertRegexp('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $record['day']);
            foreach($record as $rkey => $rval) {
                $this->assertRegExp('/^day|dimension_column_\d+$/', $rkey);
            }
        }

        // Check correct syntax of columns argument.
        $columns = $got['columns'];
        $this->assertCount($fieldCount, $columns);
        $firstCol = array_shift($columns);

        $this->assertEquals(array('header' => 'Day', 'width' => 150, 'dataIndex' => 'day', 'sortable' => 1, 'editable' => false, 'locked' => 1), $firstCol);

        foreach($columns as $column) {
            $this->assertRegExp('/^\[[^\]]+\] Job Size: Max \(Core Count\)$/', $column['header']);
            $this->assertEquals(140, $column['width']);
            $this->assertRegExp('/^dimension_column_\d+$/', $column['dataIndex']);
            $this->assertEquals(1, $column['sortable']);
            $this->assertEquals(false, $column['editable']);
            $this->assertEquals('right', $column['align']);
            $this->assertEquals('numbercolumn', $column['xtype']);
            $this->assertEquals('0,000', $column['format']);
        }

        // These have been checked - zero out so allow checking
        // of the rest
        $got['metaData']['fields'] = array();
        $got['columns'] = array();
        $got['records'] = array();
        $result = arrayRecursiveDiff($got, $expected);
        $this->assertEmpty($result);
    }

    /**
     * @dataProvider aggregateDataProvider
     */
    public function testAggregateViewValidData($view, $expected)
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
    public function testErrorBars($input, $expected)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
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
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

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
            array()
        );

        $this->assertTrue(count($categories) >= 1, "There were no 'menus' that had a category propery, this is unexpected.");

        $realmCategoryDiff = array_udiff(self::$XDMOD_REALMS, $categories, 'strcasecmp');

        $this->assertEmpty($realmCategoryDiff, "There were realms in datawarehouse.json that were not returned by get_menus.");
    }

    /**
     * @dataProvider dataFilteringProvider
     * @group DataAccess
     */
    public function testDataFiltering($user, $chartSettings, $expectedNames)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->helper->authenticate($user);

        $response = $this->helper->post('/controllers/user_interface.php', null, $chartSettings);

        $this->assertEquals($response[1]['http_code'], 200);

        $plotdata = json_decode(\TestHarness\UsageExplorerHelper::demanglePlotData($response[0]), true);

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
        $tests = array();

        $chartSettings = array(
            'public_user' => 'false',
            'realm' => 'Jobs',
            'group_by' => 'username',
            'statistic' => 'running_job_count',
            'start_date' => '2016-12-22',
            'end_date' => '2017-01-01',
            'timeframe_label' => 'User Defined',
            'scale' => '1',
            'aggregation_unit' => 'Auto',
            'dataset_type' => 'timeseries',
            'thumbnail' => 'n',
            'query_group' => 'tg_usage',
            'display_type' => 'line',
            'combine_type' => 'stack',
            'limit' => '10',
            'offset' => '0',
            'log_scale' => 'n',
            'show_guide_lines' => 'y',
            'show_trend_line' => 'n',
            'show_error_bars' => 'n',
            'show_aggregate_labels' => 'n',
            'show_error_labels' => 'n',
            'hide_tooltip' => 'false',
            'show_title' => 'y',
            'width' => '1529',
            'height' => '706',
            'legend_type' => 'bottom_center',
            'font_size' => '3',
            'operation' => 'get_charts',
            'controller_module' => 'user_interface'
        );

        $expectedNames = array('swath', 'savsp', 'litst', 'ybsbu', 'ovenb', 'sante');

        $tests[] = array('pi', $chartSettings, $expectedNames);

        $chartSettings['limit'] = 2;

        $expectedNames = array('swath', 'savsp', 'Avg of 4 Others');
        $tests[] = array('pi', $chartSettings, $expectedNames);

        $chartSettings['limit'] = 10;
        $expectedNames = array('whimb');
        $tests[] = array('usr', $chartSettings, $expectedNames);

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
    public function testFilterIdLookup($options)
    {
        //TODO: Needs further integration for storage realm
        if (self::$XDMOD_REALMS == array("storage"))  {
            $this->markTestSkipped('Needs realm integration.');
        }

        $user = $options['user'];
        $data = $options['data'];
        $helper = $options['helper'];

        $expectedValue = $options['expected']['value'];
        $expectedXpath = isset($options['expected']['xpath']) ? $options['expected']['xpath'] : null;

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

        $users = array('pub','cd', 'cs', 'pi', 'usr');

        // Base POST parameters for the request we are testing.
        $baseData = array(
            'dataset_type' => 'aggregate',
            'query_group' => 'tg_usage',
            'limit' => 10,
            'format' => 'xml',
            'operation' => 'get_data'
        );

        // Per realm test scenario data.
        //TODO: Needs further integration for storage realm
        $realmData = array();

        if (in_array("cloud", self::getRealms())) {
            array_push(
                $realmData,
                // Cloud, single value filter tests
                array(
                    'realm' => 'Cloud',
                    'filters' => array(
                        array(
                            array('project' => 'zealous'),
                            array('project_filter' => 'zealous'),
                            array('project_filter'=> 'zealous')
                        )
                    ),
                    'expected' => array(
                        'value' => array('zealous', '1754.1433'),
                        'xpath' => '//rows//row//cell/value'
                    ),
                    'additional_data' => array(
                        'group_by' => 'project',
                        'statistic' => 'cloud_core_time',
                        'start_date' => '2018-04-01',
                        'end_date' => '2018-05-01'
                    )
                ),
                // Cloud, multi-value filter tests. ( Note: at time of writing, only one project has any
                // core_time in the docker image. )
                array(
                    'realm' => 'Cloud',
                    'filters' => array(
                        array(
                            array('project_filter' => "zealous, youthful, zen"),
                            array('project_filter'=> 'zealous, youthful, zen')
                        )
                    ),
                    'expected' => array(
                        'value' => array('zealous', '1754.1433'),
                        'xpath' => '//rows//row//cell/value'
                    ),
                    'additional_data' => array(
                        'group_by' => 'project',
                        'statistic' => 'cloud_core_time',
                        'start_date' => '2018-04-01',
                        'end_date' => '2018-05-01'
                    )
                )
            );
        };

        if (in_array("jobs", self::getRealms())) {
            array_push(
                $realmData,
                // Jobs, single value filter tests
                array(
                    'realm' => 'Jobs',
                    'filters' => array(
                        array(
                            array('resource' => '1'),
                            array('resource_filter'=> '1'),
                            array('resource_filter' => '"frearson"')
                        ),
                        array(
                            array('pi' => '40'),
                            array('pi_filter' => '40'),
                            array('pi_filter' => '"taifl"')
                        )
                    ),
                    'expected' => array(
                        'value' => array('frearson', '78142.2133'),
                        'xpath' => '//rows//row//cell/value'
                    ),
                    'additional_data' => array(
                        'group_by' => 'resource',
                        'statistic' => 'total_cpu_hours',
                        'start_date' => '2016-12-22',
                        'end_date' => '2017-01-01'
                    )
                ),
                // Jobs, multi-value filter tests
                array(
                    'realm' => 'Jobs',
                    'filters' => array(
                        array(
                            array('resource' => '4,1'),
                            array('resource_filter'=> '1,4'),
                            array('resource_filter' => '\'frearson\',"pozidriv"')
                        ),
                        array(
                            array('pi' => '40,22'),
                            array('pi_filter' => '22,40'),
                            array('pi_filter' => '"taifl",\'henha\'')
                        )
                    ),
                    'expected' => array(
                        'value' => array('frearson', '78142.2133', 'pozidriv', '25358.4119'),
                        'xpath' => '//rows//row//cell//value'
                    ),
                    'additional_data' => array(
                        'group_by' => 'resource',
                        'statistic' => 'total_cpu_hours',
                        'start_date' => '2016-12-22',
                        'end_date' => '2017-01-01'
                    )
                ),
                // Jobs, multi-value ( inc. invalid numeric values ) filter tests
                array(
                    'realm' => 'Jobs',
                    'filters' => array(
                        array(
                            array('resource' => '4,1,99999'),
                            array('resource_filter'=> '1,4,99999'),
                            array('resource_filter' => '"frearson","pozidriv"')
                        ),
                        array(
                            array('pi' => '40,22,99999'),
                            array('pi_filter' => '22,40,99999'),
                            array('pi_filter' => '"taifl","henha"')
                        )
                    ),
                    'expected' => array(
                        'value' => array('frearson', '78142.2133', 'pozidriv', '25358.4119'),
                        'xpath' => '//rows//row//cell//value'
                    ),
                    'additional_data' => array(
                        'group_by' => 'resource',
                        'statistic' => 'total_cpu_hours',
                        'start_date' => '2016-12-22',
                        'end_date' => '2017-01-01'
                    )
                ),
                // Jobs, multi-value ( inc. unknown string values ) filter tests
                array(
                    'realm' => 'Jobs',
                    'filters' => array(
                        array(
                            array('resource_filter' => '"frearson","pozidriv","unknownresource"')
                        ),
                        array(
                            array('pi_filter' => '"taifl",\'henha\',"unknownperson"')
                        )
                    ),
                    'expected' => array(
                        'value' => array(
                            'success' => false,
                            'count' => 0,
                            'total' => 0,
                            'totalCount'=> 0,
                            'results' => array(),
                            'data' => array(),
                            'message' => 'Invalid filter value detected: %s',
                            'code' => 0
                        )
                    ),
                    'additional_data' => array(
                        'group_by' => 'resource',
                        'statistic' => 'total_cpu_hours',
                        'start_date' => '2016-12-22',
                        'end_date' => '2017-01-01'
                    )
                )
            );
        }

        /**
         * Generates all combinations of the elements contained within $data.
         *
         * @param array $data
         * @return array
         */
        function generateCombinations(array $data)
        {
            $results = array(array());
            foreach ($data as $datum) {
                $temp = array();
                foreach ($datum as $subDatum) {
                    foreach($results as $result) {
                        $temp[] = array_merge($result, array($subDatum));
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
                    $results = array();
                    foreach($item as $subItem) {
                        foreach($subItem as $key => $value) {
                            $results[$key] = $value;
                        }
                    }
                    array_push($carry, $results);
                    return $carry;
                },
                array()
            );
        } // allCombinations

        $results = array();
        foreach($users as $user) {
            $helper = new \TestHarness\XdmodTestHelper();
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

                    $results[] = array(array(
                        'user' => $user,
                        'helper' => $helper,
                        'data' => $requestData,
                        'expected' => $realmDatum['expected']
                    ));
                }
            }
            $results[count($results) - 1]['last'] = true;
        }


        return $results;
    }
}
