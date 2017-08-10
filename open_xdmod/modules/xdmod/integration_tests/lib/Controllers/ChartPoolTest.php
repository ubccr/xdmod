<?php

namespace IntegrationTests\Controllers;

class ChartPoolTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->helper = new \TestHarness\XdmodTestHelper();
        $this->baseUrl = 'rest/v1.0/charts/pools';
        $this->baseParams = array(
            'chart_id' => 'controller_module=metric_explorer&aggregation_unit=Auto&data_series=%5B%7B%22id%22%3A0.65108459233306%2C%22metric%22%3A%22avg_node_hours%22%2C%22category%22%3A%22%22%2C%22realm%22%3A%22Jobs%22%2C%22group_by%22%3A%22jobsize%22%2C%22x_axis%22%3Afalse%2C%22log_scale%22%3Afalse%2C%22has_std_err%22%3Afalse%2C%22std_err%22%3Afalse%2C%22std_err_labels%22%3A%22%22%2C%22value_labels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3A%22value_desc%22%2C%22filters%22%3A%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D%2C%22ignore_global%22%3Afalse%2C%22long_legend%22%3Atrue%2C%22trend_line%22%3Afalse%2C%22color%22%3A%22auto%22%2C%22shadow%22%3Afalse%2C%22visibility%22%3Anull%2C%22z_index%22%3A0%2C%22enabled%22%3Atrue%7D%5D&defaultDatasetConfig=%7B%7D&end_date=2015-05-31&featured=false&font_size=3&format=hc_jsonstore&global_filters=%7B%22data%22%3A%5B%7B%22id%22%3A%22provider%3D1%22%2C%22value_id%22%3A%221%22%2C%22value_name%22%3A%22CCR%22%2C%22dimension_id%22%3A%22provider%22%2C%22categories%22%3A%22%22%2C%22realms%22%3A%5B%22SUPREMM%22%5D%2C%22checked%22%3Atrue%7D%5D%2C%22total%22%3A1%7D&hide_tooltip=false&legend=%7B%7D&legend_type=bottom_center&limit=10&operation=get_data&share_y_axis=false&showContextMenu=y&show_filters=true&show_guide_lines=y&show_remainder=false&show_warnings=true&start=0&start_date=2015-05-01&swap_xy=false&timeframe_label=User Defined&timeseries=y&title=query 1&trendLineEnabled=&x_axis=%7B%7D&y_axis=%7B%7D',
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

    public function enumUserRoles()
    {
        return array(
            array('cd'),
            array('pi'),
            array('usr')
        );
    }

    private function addChart($params, $expectedHttpCode, array $expectedResults)
    {
        $this->postChartRequest('/add', $params, $expectedHttpCode, $expectedResults);
    }

    private function removeChart($params, $expectedHttpCode, array $expectedResults)
    {
        $this->postChartRequest('/remove', $params, $expectedHttpCode, $expectedResults);
    }

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
