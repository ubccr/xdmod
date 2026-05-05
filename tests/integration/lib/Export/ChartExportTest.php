<?php declare(strict_types=1);

namespace IntegrationTests\Export;

use IntegrationTests\BaseTest;
use IntegrationTests\TestHarness\XdmodTestHelper;

class ChartExportTest extends BaseTest
{

    /**
     * @var XdmodTestHelper
     */
    private $helper;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helper = new XdmodTestHelper();
    }

    /**
     * Test that proper escaping is done when exporting an svg ( which also occurs when exporting pdf's)
     * @dataProvider provideChartExportEscapesCorrectly
     * @return void
     */
    public function testChartExportEscapesCorrectly(string $url, array $exportParams)
    {
        // login as the center director
        $this->helper->authenticate('cd');

        $originalLastName = $this->getPropertyFromUserProfile('last_name');

        try {
            $updateResponse = $this->helper->patch('rest/v1/users/current', [], ['last_name' => "Changed' ; id > /tmp/this_shouldnt_exist; '"]);

            // Make sure that the update was successful.
            $this->assertEquals(200, $updateResponse[1]['http_code']);
            $this->assertSame(
                [
                    'success' => true,
                    'message' => 'User profile updated successfully'
                ],
                $updateResponse[0]
            );

            // Make sure that the last_name that was returned actually contains the right data.
            $newLastName = $this->getPropertyFromUserProfile('last_name');
            $this->assertNotFalse(strpos($newLastName, 'Changed'), 'The user last_name updated failed.');

            $format = $exportParams['format'];
            $exportResponse = $this->helper->get($url, $exportParams);
            $this->assertEquals(200, $exportResponse[1]['http_code'], "Request to export in $format was unsuccessful.");

            // Make sure that the file that shouldnt' exist, does not in fact exist.
            $this->assertFalse(is_file('/tmp/this_shouldnt_exist'), "Woops, chart export in $format did the bad thing. Best figure out why.");


        } finally {
            // Make sure to revert the update to centerdirector's last name.
            $revertResponse = $this->helper->patch('rest/v1/users/current', [], ['last_name' => $originalLastName]);
            if ($revertResponse[1]['http_code'] !== 200 || $revertResponse[0]['success'] === false) {
                throw new \Exception('Unable to revert centerdirectors last name. You have been warned!');
            }
        }

        // all done, logout.
        $this->helper->logout();
    }

    protected function getUserProfile()
    {
        // Retrieve the user profile information and make sure that the last_name was updated.
        $response = $this->helper->get('rest/v1/users/current');

        // make sure that the request was successful
        $this->assertEquals(200, $response[1]['http_code']);

        $responseData = $response[0];

        // Make sure that the response is as expected.
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('results', $responseData);

        return $responseData['results'];
    }

    protected function getPropertyFromUserProfile(string $property)
    {
        $userProfile = $this->getUserProfile();

        $this->assertArrayHasKey($property, $userProfile);

        return $userProfile[$property];
    }

    /**
     * @return array
     */
    public function provideChartExportEscapesCorrectly(): array
    {
        $results = [];
        $urls = [
            'controllers/user_interface.php' => [
                'public_user' => 'true',
                'realm' => 'Jobs',
                'group_by' => 'none',
                'statistic' => 'total_cpu_hours',
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'timeframe_label' => 'Previous+month',
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
                'width' => '916',
                'height' => '484',
                'legend_type' => 'bottom_center',
                'font_size' => '3',
                'format' => '<invalid>',
                'inline' => 'n',
                'operation' => 'get_data'
            ],
            'controllers/metric_explorer.php' => [
                'show_title' => 'y',
                'timeseries' => 'y',
                'aggregation_unit' => 'Auto',
                'start_date' => '2016-12-22',
                'end_date' => '2017-01-01',
                'global_filters' => '%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D',
                'title' => 'untitled+query+1',
                'show_filters' => 'true',
                'show_warnings' => 'true',
                'show_remainder' => 'false',
                'start' => '0',
                'limit' => '10',
                'timeframe_label' => 'User+Defined',
                'operation' => 'get_data',
                'data_series' => '%5B%7B%22id%22%3A4860340157018481%2C%22metric%22%3A%22total_cpu_hours%22%2C%22category%22%3A%22Jobs%22%2C%22realm%22%3A%22Jobs%22%2C%22group_by%22%3A%22none%22%2C%22x_axis%22%3Afalse%2C%22log_scale%22%3Afalse%2C%22has_std_err%22%3Afalse%2C%22std_err%22%3Afalse%2C%22std_err_labels%22%3A%22%22%2C%22value_labels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3A%22value_desc%22%2C%22filters%22%3A%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D%2C%22ignore_global%22%3Afalse%2C%22long_legend%22%3Atrue%2C%22trend_line%22%3Afalse%2C%22color%22%3A%22auto%22%2C%22shadow%22%3Afalse%2C%22visibility%22%3Anull%2C%22z_index%22%3A0%2C%22enabled%22%3Atrue%7D%5D',
                'swap_xy' => 'false',
                'share_y_axis' => 'false',
                'hide_tooltip' => 'false',
                'show_guide_lines' => 'y',
                'showContextMenu' => 'y',
                'scale' => '1',
                'format' => '<invalid>',
                'width' => '916',
                'height' => '484',
                'legend_type' => 'bottom_center',
                'font_size' => '3',
                'featured' => 'false',
                'trendLineEnabled' => 'undefined',
                'x_axis' => '%7B%7D',
                'y_axis' => '%7B%7D',
                'legend' => '%7B%7D',
                'defaultDatasetConfig' => urldecode('%257B%257D'),
                'controller_module' => 'metric_explorer',
                'inline' => 'n'
            ]
        ];
        $formats = ['svg', 'png', 'pdf'];
        foreach($formats as $format) {
            foreach($urls as $url => $urlData) {
                $urlData['format'] = $format;
                $results[] = [
                    $url,
                    $urlData
                ];

            }
        }
        return $results;
    }
}
