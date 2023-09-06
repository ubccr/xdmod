<?php

namespace IntegrationTests\Rest;

use IntegrationTests\BaseTest;
use TestHarness\XdmodTestHelper;

class AuthenticationControllerProviderTest extends BaseTest
{
    private static $helper;

    public static function setUpBeforeClass()
    {
        self::$helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideGetIdpRedirect
     */
    public function testGetIdpRedirect($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetIdpRedirect()
    {
        $validInput = [
            'path' => 'rest/auth/idpredirect',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            ['string_params' => ['returnTo']]
        );
        // TODO: Add more test coverage of this method.
        return $tests;
    }
}
