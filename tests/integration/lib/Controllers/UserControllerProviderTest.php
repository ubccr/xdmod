<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use Models\Services\Tokens;
use stdClass;
use TestHarness\TokenHelper;
use TestHarness\Utilities;
use TestHarness\XdmodTestHelper;

class UserControllerProviderTest extends BaseUserAdminTest
{
    /**
     * Tests the UserControllerProvider route: GET /users/current
     *
     * @dataProvider provideGetCurrentUser
     *
     * @param array $options
     * @throws Exception if unable to authenticate as the specified user role.
     */
    public function testGetCurrentUser(array $options)
    {
        $user = $options['user'];
        $expected = $options['expected'];
        $expectedFile = $expected['file'];
        $expectedHttpCode = $expected['http_code'];
        $expectedContentType = $expected['content_type'];


        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $response = $this->helper->get('rest/v1/users/current');

        $this->validateResponse($response, $expectedHttpCode, $expectedContentType);

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_controller', $expectedFile)
        );

        $actual = $response[0];

        $this->assertEquals($expected, $actual);

        if ($user !== 'pub') {
            $this->helper->logout();
        }
    }

    /**
     * @dataProvider provideBaseRoles
     */
    public function testAPITokensCRD($role)
    {
        if ('pub' === $role) {
            foreach (array('get', 'create', 'revoke') as $action) {
                $this->makeTokenRequest($action, 401, 'authentication_error');
            }
        } else {
            $this->helper->authenticate($role);
            $this->helper->delete('rest/users/current/api/token');
            $this->makeTokenRequest('get', 200, 'get_failure');
            $this->makeTokenRequest('create', 200, 'create_success', 'schema');
            $this->makeTokenRequest('create', 200, 'create_failure');
            $this->makeTokenRequest('get', 200, 'get_success', 'schema');
            $this->makeTokenRequest('revoke', 200, 'revoke_success');
            $this->makeTokenRequest('revoke', 200, 'revoke_failure');
            $this->helper->logout();
        }
    }

    /**
     * This tests that API Token authentication is working for the controller operation:
     * `html/controllers/metric_explorer.php?operation=get_dw_descripter`.
     *
     * @dataProvider provideControllerTokenTest
     * @param stdClass $options
     * @return void
     * @throws Exception
     */
    public function testControllerTokenAuthentication($role, stdClass $options)
    {
        $this->endpointTokenAuthenticationTest($role, $options);
    }

    /**
     * This tests that API Token authentication is working for the Warehouse Export REST Controllers getRealms endpoint:
     * `rest/warehouse/export/realms`.
     *
     * @dataProvider provideRestTokenTest
     *
     * @param stdClass $options
     * @return void
     * @throws Exception
     */
    public function testRestTokenAuthentication($role, stdClass $options)
    {
        $this->endpointTokenAuthenticationTest($role, $options);
    }

    /**
     * A helper function that will test the end point provided with $options for it's ability to support API Token
     * authentication. Currently API Token authentication is supported for both controllers/* and Rest endpoints. This
     * is accomplished by a user logging into XDMoD normally and generating an API Token. Then, when making a request to
     * and endpoint that supports API Token authentication they include this token as a request header
     * 'Bearer: <token_value>`.
     *
     * The steps of this test are as follows:
     *   - Attempt a request to the endpoint located at $options->test->url before authorization. This is expected to fail.
     *   - If we're not testing the public user then log the test user.
     *   - Create a new API token for the currently logged in user.
     *   - Add the newly created token to $this->helpers->headers so that it will be included when making requests.
     *   - Attempt a request to the test endpoint again this time w/ the token in the headers.
     *   - Verify that the results of the API Token authenticated request are as expected.
     *   - Begin cleanup by clearing the API Token from the helpers headers.
     *   - Revoke the previously created API Token.
     *   - and finally, if the test user is not the public user then log them out.
     *
     * @param string $role
     * @param stdclass $options
     *
     * @return void
     *
     * @throws Exception
     */
    private function endpointTokenAuthenticationTest($role, stdclass $options)
    {
        $expected = $options->expected;
        $test = $options->test;

        $failureHttpCode = $expected->test->failure->http_code;
        $failureContentType = $expected->test->failure->content_type;
        $failureOutputFileName = $expected->test->failure->file_name;

        // Attempt to make a request to the controller endpoint unauthenticated in any way.
        // This should fail.
        $this->makeRequest(
            $this->helper,
            $test->url,
            $test->verb,
            $test->parameters,
            $test->data,
            $failureHttpCode,
            $failureContentType,
            'integration/rest/user/api_token',
            $failureOutputFileName,
            'exact'
        );

        // Now go ahead and authenticate the test user so we can create / use their API Token.
        if ('pub' !== $role) {
            $this->helper->authenticate($role);

            $this->helper->delete('rest/users/current/api/token');

            $tokenResponse = $this->makeTokenRequest(
                'create',
                200,
                'create_success',
                'schema'
            );

            $token = $tokenResponse->data->token;
            // We add the token to the request headers so that we can use the token authentication.
            $this->helper->addheader('Authorization', sprintf('%s %s', Tokens::HEADER_KEY, $token));

            // While we prefer the 'Authorization' header approach to token authentication. We also support having it
            // provided as a query parameter. Also, retrieving the 'Authorization' header on el7 isn't working, so
            // this is the only way to test authentication.
            if (!property_exists($test, 'parameters') || !isset($test->parameters)) {
                $test->parameters = array();
            } elseif (is_object($test->parameters)) {
                $test->parameters = (array)$test->parameters;
            }

            $test->parameters[Tokens::HEADER_KEY] = $token;
            $this->makeRequest(
                $this->helper,
                $test->url,
                $test->verb,
                $test->parameters,
                $test->data,
                $expected->test->success->http_code,
                $expected->test->success->content_type,
                $expected->test->success->file_group,
                $expected->test->success->file_name,
                'exact'
            );

            // clean up the helper's headers.
            $this->helper->addheader('Authorization', null);

            $this->makeTokenRequest('revoke', 200, 'revoke_success');

            // And finally make sure that we log out.
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideGetCurrentUser()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_controller', 'get_current_user-8.0.0', 'input')
        );
    }

    public function provideControllerTokenTest()
    {
        return $this->provideTokenTest('test_controller_token_auth');
    }

    public function provideRestTokenTest()
    {
        return $this->provideTokenTest('test_rest_token_auth');
    }

    private function provideTokenTest($fileName)
    {
        $json = Json::loadFile(
            $this->getTestFiles()->getFile(
                'integration/rest/user',
                $fileName,
                'input'
            ),
            false
        );
        return array_map(
            function ($roleArray) use ($json) {
                return array($roleArray[0], $json);
            },
            $this->provideBaseRoles()
        );
    }

    private function makeTokenRequest(
        $action,
        $httpCode,
        $outputFileName,
        $validationType = 'exact'
    ) {
        if ('get' === $action) {
            $verb = 'get';
        } elseif ('create' === $action) {
            $verb = 'post';
        } else {
            $verb = 'delete';
        }
        return $this->makeRequest(
            $this->helper,
            'rest/users/current/api/token',
            $verb,
            null,
            null,
            $httpCode,
            'application/json',
            'integration/rest/user/api_token',
            $outputFileName . ($validationType === 'schema' ? '.spec' : ''),
            $validationType
        );
    }
}
