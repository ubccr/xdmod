<?php

namespace IntegrationTests\Rest;

use CCR\Json;
use IntegrationTests\Controllers\BaseUserAdminTest;
use Models\Services\Realms;

class DashboardControllerProviderTest extends BaseUserAdminTest
{
    /**
     * @dataProvider provideSetLayout
     */
    public function testSetLayout($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
    }
    public function provideSetLayout()
    {
        $validInput = [
            'path' => 'rest/dashboard/layout',
            'method' => 'post',
            'params' => null,
            'data' => ['data' => 'foo']
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['data']
            ]
        );
    }

    /**
     * Exercises the `dashboard/statistics` REST endpoint.
     *
     * @dataProvider provideTestGetStatistics
     *
     * @param array $options
     * @throws \Exception
     */
    public function testGetStatistics(array $options)
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $username = $options['username'];
        $startDate = \xd_utilities\array_get($options, 'start_date', '2016-12-22');
        $endDate = \xd_utilities\array_get($options, 'end_date', '2017-01-01');

        $params = array();
        if ($startDate !== "null") {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== "null") {
            $params['end_date'] = $endDate;
        }

        if ($username !== ROLE_ID_PUBLIC) {
            $this->helper->authenticate($username);
        }

        $response = $this->helper->get('rest/v0.1/dashboard/statistics', $params);
        if ($username !== ROLE_ID_PUBLIC) {
            $this->helper->logout();
        }

        $defaultExpectedFile = "get_statistics-$username";
        $expected = \xd_utilities\array_get(
            $options,
            'expected',
            array(
                'file' => $defaultExpectedFile,
                'http_code' => 200,
                'content_type' => 'application/json'
            )
        );
        $expectedHttpCode = \xd_utilities\array_get($expected, 'http_code', 200);
        $expectedContentType = \xd_utilities\array_get($expected, 'content_type', 'application/json');

        $this->validateResponse($response, $expectedHttpCode, $expectedContentType);

        $actual = $this->recursivelyFilter($response[0], array('query_string', 'query_time'));

        $expectedFileName = \xd_utilities\array_get($expected, 'file', $defaultExpectedFile);
        $expectedFilePath = $this->getTestFiles()->getFile('rest', $expectedFileName);

        if (!is_file($expectedFilePath)) {
            file_put_contents($expectedFilePath, sprintf("%s\n", json_encode($actual, JSON_PRETTY_PRINT)));
            $this->markTestSkipped("Generated Expected File: $expectedFilePath");
        } else {
            $expected = json_decode(file_get_contents($expectedFilePath), true);

            // If we have formats then the validation is a bit more complicated.
            if (array_key_exists('formats', $expected)) {

                // Collect the `fieldName` => `numberType` for each of the items we expect to be
                // present in the `data` section. This will let us detect when we have a `float` and
                // include a delta for the equality test.
                $expectedTypes = array();
                foreach ($expected['formats'] as $format) {
                    foreach ($format['items'] as $item) {
                        $expectedTypes[$item['fieldName']] = $item['numberType'];
                    }
                }

                // These attributes can just straight up be checked for equality.
                $equalAttributes = array('totalCount', 'success', 'message', 'formats');
                foreach ($equalAttributes as $attribute) {
                    $this->assertEquals($expected[$attribute], $actual[$attribute]);
                }

                // For the `data` section we need to account for adding a `delta` to the equality test
                // if it's expected that the data type is `float` or if the `$fieldName` is one of the
                // `sem_` statistics.
                $expectedData = $expected['data'][0];
                $actualData = $actual['data'][0];

                foreach ($expectedData as $fieldName => $value) {
                    $this->assertArrayHasKey($fieldName, $actualData);

                    if ((array_key_exists($fieldName, $expectedTypes) && $expectedTypes[$fieldName] === 'float') ||
                        strpos($fieldName, 'sem_') !== false) {
                        // Make sure that the values we are validating are in the correct format.
                        $expectedValue = (float)$value[0];
                        $actualValue = (float)$actualData[$fieldName][0];

                        $this->assertEqualsWithDelta($expectedValue, $actualValue,  1.0e-8, "Failed equivalency for: $fieldName");
                    } else {
                        $this->assertEquals($value, $actualData[$fieldName]);
                    }
                }
            } else {
                $this->assertEquals($expected, $actual);
            }
        }
    } // total_wallduration_hours

    public function provideTestGetStatistics()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('rest', 'get_statistics', 'input')
        );
    }

    /**
     * @dataProvider provideGetStatisticsParamValidation
     */
    public function testGetStatisticsParamValidation(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::requestAndValidateJson($this->helper, $input, $output);
    }

    public function provideGetStatisticsParamValidation()
    {
        $validInput = [
            'path' => 'rest/dashboard/statistics',
            'method' => 'get',
            'params' => [
                'start_date' => 'foo',
                'end_date' => 'foo'
            ],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'run_as' => 'pub',
                'string_params' => ['start_date', 'end_date']
            ]
        );
    }

    private function recursivelyFilter(array $data, array $keys)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $keys, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->recursivelyFilter($value, $keys);
            }
        }

        return $data;
    }

    /**
     * @dataProvider provideSetViewedUserTour
     */
    public function testSetViewedUserTour($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
    }

    public function provideSetViewedUserTour()
    {
        $validInput = [
            'path' => 'rest/dashboard/viewedUserTour',
            'method' => 'post',
            'params' => null,
            'data' => ['viewedTour' => '0']
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['viewedTour']
            ]
        );
        // Test bad request parameters.
        $tests[] = [
            'invalid_data_parameter',
            'usr',
            parent::mergeParams(
                $validInput,
                'data',
                ['viewedTour' => '-1']
            ),
            parent::validateBadRequestResponse('Invalid data parameter')
        ];
        // Test successful requests.
        foreach ([1, 0] as $viewedTour) {
            $tests[] = [
                'success_' . $viewedTour,
                'usr',
                parent::mergeParams(
                    $validInput,
                    'data',
                    ['viewedTour' => "$viewedTour"]
                ),
                parent::validateSuccessResponse([
                    'success' => true,
                    'total' => 1,
                    'msg' => [
                        'viewedTour' => $viewedTour,
                        'recordid' => 0
                    ]
                ])
            ];
        }
        return $tests;
    }
}
