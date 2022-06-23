<?php

namespace IntegrationTests\Controllers;

use CCR\Json;

class UserInterfaceTest extends BaseUserAdminTest
{

    /**
     * Test the `html/controllers/user_interface/get_menus.php` endpoint.
     *
     * @dataProvider provideTestGetMenus
     *
     * @param array $options
     * @throws \Exception
     */
    public function testGetMenus(array $options)
    {
        $user = $options['user'];
        $additionalData = $options['data'];

        $defaultExpected = array(
            "output" => "$user-get_menus",
            "http_code" => 200,
            "content_type" => 'application/json'
        );

        $expectedConfig = isset($options['expected']) ? $options['expected'] : $defaultExpected;
        $expectedOutputFileName = $expectedConfig['output'];
        $expectedHttpCode = isset($expectedConfig['http_code']) ? $expectedConfig['http_code'] : 200;
        $expectedContentType = isset($expectedConfig['content_type']) ? $expectedConfig['content_type'] : 'application/json';

        // Make sure to authenticate the user if necessary.
        if ($user !== ROLE_ID_PUBLIC) {
            $this->helper->authenticate($user);
        }

        $data = array_merge(
            array(
                'operation' => 'get_menus',
                'query_group' => 'tg_usage'
            ),
            $additionalData
        );

        $response = $this->helper->post("controllers/user_interface.php", null, $data);

        if ($user !== ROLE_ID_PUBLIC) {
            $this->helper->logout();
        }

        $this->validateResponse($response, $expectedHttpCode, $expectedContentType);

        $actual = $response[0];

        # Check spec file
        $schemaObject = JSON::loadFile(
            $this->getTestFiles()->getFile('schema', 'get-menus.spec', ''),
            false
        );
        $this->validateJson($actual, $schemaObject);

        # Check expected file
        $expected = array();
        foreach(self::$XDMOD_REALMS as $realm) {
            $expectedOutputFile = $this->getTestFiles()->getFile('user_interface', $expectedOutputFileName, "output/$realm");

            # Create missing files/directories
            if (!is_file($expectedOutputFile)) {
                $newFile = array();
                foreach ($actual as $arr) {
                    if (isset($arr['realm'])) {
                        if (strtolower($arr['realm']) == $realm) {
                            array_push($newFile, $arr);
                        }
                    }
                }
                $separator = array(
                    "text" => "",
                    "id" => "-111",
                    "node_type" => "separator",
                    "iconCls" => "blank",
                    "leaf" => true,
                    "disabled" => true
                );
                array_push($newFile, $separator);
                $filePath = dirname($expectedOutputFile);
                if (!is_dir($filePath)){
                    mkdir($filePath);
                }
                file_put_contents($expectedOutputFile, json_encode($newFile, JSON_PRETTY_PRINT) . "\n");
                $this->markTestSkipped("Generated Expected Output for UserInterfaceTest testGetMenus: $expectedOutputFile\n");
            }

            $expected = array_merge($expected, json_decode(file_get_contents($expectedOutputFile), true));

        }

        $this->assertEquals($expected, $actual);

    } // public function testGetMenus(array $options)

    /**
     * Provides test data to `testGetMenus`.
     *
     * @return array|object
     * @throws \Exception
     */
    public function provideTestGetMenus()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_interface', 'get_menus', 'input')
        );
    } // public function provideTestGetMenus()
}
