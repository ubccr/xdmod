<?php

namespace IntegrationTests\Controllers;

use CCR\Json;

class SummaryControllerProviderTest extends BaseUserAdminTest
{
    /**
     * Exercises the `summary/statistics` REST endpoint.
     *
     * @dataProvider provideTestGetStatistics
     *
     * @param array $options
     * @throws \Exception
     */
    public function testGetStatistics(array $options)
    {
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

        $response = $this->helper->get('rest/v0.1/summary/statistics', $params);
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
            echo "Generated Expected File: $expectedFilePath\n";
            $this->assertTrue(true);
        } else {
            $expected = json_decode(file_get_contents($expectedFilePath), true);

            // If we have formats then the validation is a bit more complicated.
            if (array_key_exists('formats', $expected)) {

                // Collect the `fieldName` => `numberType` for each of the items we expect to be
                // present in the `data` section. This will let us detect when we have a `float` and
                // include a delta for the equality test.
                $expectedTypes = array();
                foreach($expected['formats'] as $format) {
                    foreach($format['items'] as $item) {
                        $expectedTypes[$item['fieldName']] = $item['numberType'];
                    }
                }

                // These attributes can just straight up be checked for equality.
                $equalAttributes = array('totalCount', 'success', 'message', 'formats');
                foreach($equalAttributes as $attribute) {
                    $this->assertEquals($expected[$attribute], $actual[$attribute]);
                }

                // For the `data` section we need to account for adding a `delta` to the equality test
                // if it's expected that the data type is `float` or if the `$fieldName` is one of the
                // `sem_` statistics.
                $expectedData = $expected['data'][0];
                $actualData = $actual['data'][0];

                foreach($expectedData as $fieldName => $value) {
                    $this->assertArrayHasKey($fieldName, $actualData);

                    if ((array_key_exists($fieldName, $expectedTypes) && $expectedTypes[$fieldName] === 'float') ||
                        strpos($fieldName, 'sem_') !== false) {
                        // Make sure that the values we are validating are in the correct format.
                        $expectedValue = (float) $value[0];
                        $actualValue = (float) $actualData[$fieldName][0];

                        $this->assertEquals($expectedValue, $actualValue, "", 1.0e-8);
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

    private function recursivelyFilter(array $data, array $keys)
    {
        foreach($data as $key => $value) {
            if (in_array($key, $keys, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->recursivelyFilter($value, $keys);
            }
        }

        return $data;
    }
}
