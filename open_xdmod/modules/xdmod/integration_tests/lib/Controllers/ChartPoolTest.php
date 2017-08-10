<?php

namespace IntegrationTests\Controllers;

class ChartPoolTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {

        // A helper class that encapsulates such things as logging in / out etc.
        $this->helper = new \TestHarness\XdmodTestHelper();

        // The base url that will be used during the testst.
        $this->baseUrl = 'rest/v1.0/charts/pools';

        // the base set of request parameters that will be used during the
        // testing.
        $this->baseParams = array(
            'chart_id' => 'controller_module=metric_explorer&aggregation_unit=Au'.
                          'to&data_series=%5B%7B%22id%22%3A0.65108459233306%2C%2'.
                          '2metric%22%3A%22avg_node_hours%22%2C%22category%22%3A'.
                          '%22%22%2C%22realm%22%3A%22Jobs%22%2C%22group_by%22%3A'.
                          '%22jobsize%22%2C%22x_axis%22%3Afalse%2C%22log_scale%2'.
                          '2%3Afalse%2C%22has_std_err%22%3Afalse%2C%22std_err%22'.
                          '%3Afalse%2C%22std_err_labels%22%3A%22%22%2C%22value_l'.
                          'abels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C'.
                          '%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%'.
                          '2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3'.
                          'A%22value_desc%22%2C%22filters%22%3A%7B%22data%22%3A%'.
                          '5B%5D%2C%22total%22%3A0%7D%2C%22ignore_global%22%3Afa'.
                          'lse%2C%22long_legend%22%3Atrue%2C%22trend_line%22%3Af'.
                          'alse%2C%22color%22%3A%22auto%22%2C%22shadow%22%3Afals'.
                          'e%2C%22visibility%22%3Anull%2C%22z_index%22%3A0%2C%22'.
                          'enabled%22%3Atrue%7D%5D&defaultDatasetConfig=%7B%7D&e'.
                          'nd_date=2015-05-31&featured=false&font_size=3&format='.
                          'hc_jsonstore&global_filters=%7B%22data%22%3A%5B%7B%22'.
                          'id%22%3A%22provider%3D1%22%2C%22value_id%22%3A%221%22'.
                          '%2C%22value_name%22%3A%22CCR%22%2C%22dimension_id%22%'.
                          'A%22provider%22%2C%22categories%22%3A%22%22%2C%22real'.
                          'ms%22%3A%5B%22SUPREMM%22%5D%2C%22checked%22%3Atrue%7D'.
                          '%5D%2C%22total%22%3A1%7D&hide_tooltip=false&legend=%7'.
                          'B%7D&legend_type=bottom_center&limit=10&operation=get'.
                          '_data&share_y_axis=false&showContextMenu=y&show_filte'.
                          'rs=true&show_guide_lines=y&show_remainder=false&show_'.
                          'warnings=true&start=0&start_date=2015-05-01&swap_xy=f'.
                          'alse&timeframe_label=User Defined&timeseries=y&title='.
                          'query 1&trendLineEnabled=&x_axis=%7B%7D&y_axis=%7B%7D',
            'chart_drill_details' => '',
            'chart_date_desc' => '2015-05-01 to 2015-05-31',
            'chart_title' => 'query 1',
            'module' => 'metric_explorer'
        );
    }

    /**
     * Test the endpoint with the additional $urlFragment, when logged in as
     * $user, and with the $field missing from the provided params
     *
     * @param string $field
     * @param string $user
     * @param string $urlFragment
     *
     * @dataProvider enumMissingFieldsForAdd
     **/
    public function testMissingFieldsForAdd($field, $user)
    {
        if ($user !== null) {
            $this->helper->authenticate($user);
        }

        $params = $this->baseParams;
        unset($params[$field]);

        $expectedResults = array(
            'success' => false,
            'message' => function ($value) use ($field) {
                return strpos($value, $field) >= 0;
            }
        );

        $this->addChart($params, 400, $expectedResults);

        if ($user !== null) {
            $this->helper->logout();
        }
    }

    /**
     * Test the endpoint with the additional $urlFragment, when logged in as
     * $user, and with the $field missing from the provided params
     *
     * @param string $field
     * @param string $user
     * @param string $urlFragment
     *
     * @dataProvider enumMissingFieldsForRemove
     **/
    public function testMissingFieldsForRemove($field, $user)
    {
        if ($user !== null) {
            $this->helper->authenticate($user);
        }

        $params = $this->baseParams;
        unset($params[$field]);

        $expectedResults = array(
            'success' => false,
            'message' => function ($value) use ($field) {
                return strpos($value, $field) >= 0;
            }
        );

        $this->removeChart($params, 400, $expectedResults);

        if ($user !== null) {
            $this->helper->logout();
        }
    }

    /**
     * Test the happy path of adding / removing a chart.
     *
     * @dataProvider enumUserRoles
     **/
    public function testAddingAndRemovingChartShouldBeValid($user)
    {
        if ($user !== null) {
            $this->helper->authenticate($user);
        }

        $this->addChart($this->baseParams, 200, array(
            'success' => true,
            'action' => 'add'
        ));

        $this->removeChart($this->baseParams, 200, array(
            'success' => true,
            'action' => 'remove'
        ));

        if ($user !== null) {
            $this->helper->logout();
        }
    }

    /**
     *
     * @dataProvider enumUserRoles
     **/
    public function testAddingADuplicateChartIdShouldError($user)
    {
        if ($user !== null) {
            $this->helper->authenticate($user);
        }

        $this->addChart($this->baseParams, 200, array(
            'success' => true,
            'action' => 'add'
        ));

        $this->addChart($this->baseParams, 500, array(
            'success' => false,
            'message' => 'chart_exists_in_queue'
        ));

        $this->removeChart($this->baseParams, 200, array(
            'success' => true,
            'action' => 'remove'
        ));

        if ($user !== null) {
            $this->helper->logout();
        }
    }

    /**
     * Test to ensure that a public ( non-authenticated ) user is not able to
     * add a chart to a chart pool.
     **/
    public function testPublicUserCannotAddChart()
    {
        $this->addChart($this->baseParams, 401, array(
            'success' => false,
            'message' => function ($value) {
                return strpos($value, 'Not Authorized') >=0;
            }
        ));
    }

    /**
     * Test to ensure that a public ( non-authenticated ) user is not able to
     * remove a chart from a chart pool.
     **/
    public function testPublicUserCannotRemoveChart()
    {
        $this->removeChart($this->baseParams, 401, array(
            'success' => false,
            'message' => function ($value) {
                return strpos($value, 'Not Authorized') >=0;
            }
        ));
    }

    /**
     * Data provider function for the test function testMissingFieldsForAdd.
     *
     * @returns array in the format:
     *     array(
     *         array('<field>', '<acl>'),
     *         array('<field>', '<acl>')
     *     )
     **/
    public function enumMissingFieldsForAdd()
    {
        $fields = array('chart_id', 'chart_drill_details', 'chart_date_desc');
        $users = array('cd', 'pi', 'usr');
        $results = array();
        foreach ($fields as $field) {
            foreach($users as $user) {
                $results []= array(
                    $field,
                    $user
                );
            }
        }
        return $results;
    }

    /**
     * Data provider for the test function testMissingFieldsForRemove
     *
     * @returns array in the format:
     *     array(
     *         array('<field>', '<acl>'),
     *         array('<field>', '<acl>')
     *     )
     **/
    public function enumMissingFieldsForRemove()
    {
        $fields = array('chart_id');
        $users = array('cd', 'pi', 'usr');
        $results = array();
        foreach($fields as $field) {
            foreach($users as $user) {
                $results []= array(
                    $field,
                    $user
                );
            }
        }
        return $results;
    }

    /**
     * Data Provider for the functions:
     *     - testAddingAndRemovingChartShouldBeValid
     *     - testAddingADuplicateChartIdShouldError
     * @returns array in the format:
     *     array(
     *         array('<acl>'),
     *         array('<acl>')
     *     )
     **/
    public function enumUserRoles()
    {
        return array(
            array('cd'),
            array('pi'),
            array('usr')
        );
    }

    /**
     * Convenience function that encapsulates the action of adding a chart as
     * well as the assertions for said action.
     *
     * @param array $params
     * @param int   $expectedHttpCode
     * @param array $expectedResults
     **/
    private function addChart($params, $expectedHttpCode, array $expectedResults)
    {
        $this->postChartRequest('/add', $params, $expectedHttpCode, $expectedResults);
    }

    /**
     * Convenience function that encapsulates the action of removing a chart as
     * well as the assertions for said action.
     *
     * @param array $params
     * @param int   $expectedHttpCode
     * @param array $expectedResults
     **/
    private function removeChart($params, $expectedHttpCode, array $expectedResults)
    {
        $this->postChartRequest('/remove', $params, $expectedHttpCode, $expectedResults);
    }

    /**
     * A convenience function that encapsulates issuing a POST request to the
     * endpoint identified by the provided $urlFragment. It supplies $params
     * to the POST request and provides the following assertions:
     *     - the content_type of the response is 'application/json'
     *     - the http_code of the response is equal to $expectedHttpCode
     *     - for each key => value pair in $expectedResults
     *         - that the key is present in the returned json.
     *         - that, if the value is:
     *             - a function utilize the results of $value($data[$key])
     *             - a string then utilize the $value as is.
     *         - that the $value is equal to $data[$key]
     *
     * @param string $urlFragment
     * @param array  $params
     * @param int    $expectedHttpCode
     * @param array  $expectedResults
     **/
    private function postChartRequest($urlFragment, $params, $expectedHttpCode, array $expectedResults)
    {
        $request = $this->helper->post($this->baseUrl . $urlFragment, null, $params);
        $this->assertEquals('application/json', $request[1]['content_type']);
        $this->assertEquals($expectedHttpCode, $request[1]['http_code']);

        $data = $request[0];

        foreach ($expectedResults as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $actual = is_callable($value) ? $value($data[$key]) : $value;
            $expected = is_callable($value) ? true : $data[$key];
            $this->assertEquals($expected, $actual);
        }
    }
}
