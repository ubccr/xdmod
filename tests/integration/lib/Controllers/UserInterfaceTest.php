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
     * @throws \Exception
     */
    public function testGetMenus(array $options): void
    {
        $user = $options['user'];
        $additionalData = $options['data'];

        $defaultExpected = ["output" => "$user-get_menus", "http_code" => 200, "content_type" => 'application/json'];

        $expectedConfig = $options['expected'] ?? $defaultExpected;
        $expectedOutputFileName = $expectedConfig['output'];
        $expectedHttpCode = $expectedConfig['http_code'] ?? 200;
        $expectedContentType = $expectedConfig['content_type'] ?? 'application/json';

        // Make sure to authenticate the user if necessary.
        if ($user !== ROLE_ID_PUBLIC) {
            $this->helper->authenticate($user);
        }

        $data = array_merge(
            ['operation' => 'get_menus', 'query_group' => 'tg_usage'],
            $additionalData
        );

        $response = $this->helper->post("controllers/user_interface.php", null, $data);

        if ($user !== ROLE_ID_PUBLIC) {
            $this->helper->logout();
        }

        $this->validateResponse($response, $expectedHttpCode, $expectedContentType);

        $actual = $response[0];

        $this->validateJsonAgainstFile($actual, 'schema', 'get-menus.spec');

        # Check expected file
        $expected = [];
        foreach(self::$XDMOD_REALMS as $realm) {
            $expectedOutputFile = $this->getTestFiles()->getFile('user_interface', $expectedOutputFileName, "output/$realm");

            # Create missing files/directories
            if (!is_file($expectedOutputFile)) {
                $newFile = [];
                foreach ($actual as $arr) {
                    if (isset($arr['realm'])) {
                        if (strtolower($arr['realm']) == $realm) {
                            array_push($newFile, $arr);
                        }
                    }
                }
                $separator = ["text" => "", "id" => "-111", "node_type" => "separator", "iconCls" => "blank", "leaf" => true, "disabled" => true];
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

    }

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
    }
}
