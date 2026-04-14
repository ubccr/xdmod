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
                'global_filters' => urldecode('%257B%2522data%2522%253A%255B%255D%252C%2522total%2522%253A0%257D'),
                'title' => 'untitled+query+1',
                'show_filters' => 'true',
                'show_warnings' => 'true',
                'show_remainder' => 'false',
                'start' => '0',
                'limit' => '10',
                'timeframe_label' => 'User+Defined',
                'operation' => 'get_data',
                'data_series' => urldecode('%255B%257B%2522id%2522%253A4860340157018481%252C%2522metric%2522%253A%2522total_cpu_hours%2522%252C%2522category%2522%253A%2522Jobs%2522%252C%2522realm%2522%253A%2522Jobs%2522%252C%2522group_by%2522%253A%2522none%2522%252C%2522x_axis%2522%253Afalse%252C%2522log_scale%2522%253Afalse%252C%2522has_std_err%2522%253Afalse%252C%2522std_err%2522%253Afalse%252C%2522std_err_labels%2522%253A%2522%2522%252C%2522value_labels%2522%253Afalse%252C%2522display_type%2522%253A%2522line%2522%252C%2522line_type%2522%253A%2522Solid%2522%252C%2522line_width%2522%253A2%252C%2522combine_type%2522%253A%2522side%2522%252C%2522sort_type%2522%253A%2522value_desc%2522%252C%2522filters%2522%253A%257B%2522data%2522%253A%255B%255D%252C%2522total%2522%253A0%257D%252C%2522ignore_global%2522%253Afalse%252C%2522long_legend%2522%253Atrue%252C%2522trend_line%2522%253Afalse%252C%2522color%2522%253A%2522auto%2522%252C%2522shadow%2522%253Afalse%252C%2522visibility%2522%253Anull%252C%2522z_index%2522%253A0%252C%2522enabled%2522%253Atrue%257D%255D'),
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
                'x_axis' => urldecode('%257B%257D'),
                'y_axis' => urldecode('%257B%257D'),
                'legend' => urldecode('%257B%257D'),
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
