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

        $params = array(
            'start_date' => $startDate,
            'end_date' => $endDate
        );

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
        $expectedFilePath = $this->getTestFiles()->getFile('controllers', $expectedFileName);

        if (!is_file($expectedFilePath)) {
            file_put_contents($expectedFilePath, sprintf("%s\n", json_encode($actual)));
            echo "Generated Expected File: $expectedFilePath\n";
            $this->assertTrue(true);
        } else {
            $expected = json_decode(file_get_contents($expectedFilePath), true);

            $this->assertEquals($expected, $actual);
        }
    }

    public function provideTestGetStatistics()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'get_statistics', 'input')
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
