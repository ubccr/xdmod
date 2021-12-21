<?php

namespace ComponentTests;

use CCR\Json;
use Exception;
use Models\Services\Parameters;

class ParametersTest extends BaseTest
{
    /**
     * Exercises the `Parameters::getParameters` function to ensure that it is functioning as expected.
     *
     * @dataProvider provideTestGetParameters
     *
     * @throws Exception if unable to find the provided user name.
     * @throws Exception if the 'database' db config cannot be found.
     * @throws Exception if the default test base directory cannot be found.
     */
    public function testGetParameters($userName, $aclName, $expectedFileName)
    {

        $user = \XDUser::getUserByUserName($userName);
        $actual = Parameters::getParameters($user, $aclName);

        $expectedFilePath = $this->getTestFiles()->getFile('parameters', $expectedFileName);
        // if the file containing the expected output is not present then create it.
        if (!file_exists($expectedFilePath)) {
            file_put_contents($expectedFilePath, json_encode($actual, JSON_PRETTY_PRINT) . "\n");
            $this->markTestSkipped("Generated expected test output: $expectedFilePath");
        } else {
            $expected = Json::loadFile($expectedFilePath);

            $this->assertEquals($expected, $actual, "Error testing getParameters for: $userName, $aclName");
        }
    }

    /**
     * Provides test input for `testParametersGetParameters`.
     *
     * Tests both expected input and some unexpected input such
     * as:
     *   - non-existent acl name
     *   - empty acl name
     *   - numeric acl name
     *   - sql injection formatted acl name.
     *
     * @return array|object
     * @throws Exception if unable to find / load the specified input file.
     */
    public function provideTestGetParameters()
    {
        return $this->getTestFiles()->loadJsonFile('parameters', 'parameters', 'input');
    }
}
