<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use IntegrationTests\TokenAuthTest;
use Models\Services\Tokens;
use stdClass;
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
     * Test expected successes and failures at creating, getting, and revoking
     * API tokens.
     *
     * @dataProvider provideBaseRoles
     */
    public function testAPITokensCRD($role)
    {
        if ('pub' === $role) {
            // If the user is not logged in; attempting to get, create, or
            // revoke their tokens should fail.
            foreach (['get', 'post', 'delete'] as $method) {
                $this->makeTokenRequest(
                    $method,
                    parent::assertAuthorizationError(401)
                );
            }
        } else {
            // Log the user in so we can create, get, and revoke their tokens.
            $this->helper->authenticate($role);
            // Revoke the token in case the user already has one.
            $this->helper->delete('rest/users/current/api/token');
            // Since the user now doesn't have a token, getting it should fail.
            $notFoundOutput = [
                'status_code' => 404,
                'body_validator' => parent::assertErrorBody(
                    'API token not found.',
                    0
                )
            ];
            $this->makeTokenRequest('get', $notFoundOutput);
            // Since the user still doesn't have a token, creating one should
            // succeed.
            $this->makeTokenRequest(
                'post',
                parent::assertSuccess(function ($body) {
                    $this->assertRegExp(
                        '/^[0-9]+\\.[0-9a-f]{64}$/',
                        $body['data']['token']
                    );
                    parent::assertDate($body['data']['expiration_date']);
                })
            );
            // Now that the user has a token, getting it should succeed.
            $this->makeTokenRequest(
                'get',
                parent::assertSuccess(function ($body) {
                    parent::assertDate($body['data']['created_on']);
                    parent::assertDate($body['data']['expiration_date']);
                })
            );
            // Since the user still has a token and can only have one at a
            // time, creating a new one should fail.
            $this->makeTokenRequest(
                'post',
                [
                    'status_code' => 409,
                    'body_validator' => parent::assertErrorBody(
                        'Token already exists.',
                        0
                    )
                ]
            );
            // Since the user has a token, revoking it should succeed.
            $this->makeTokenRequest(
                'delete',
                parent::assertSuccess(function ($body) {
                    parent::assertSame(
                        'Token successfully revoked.',
                        $body['message']
                    );
                })
            );
            // Now that the user does not have a token, revoking one should
            // fail.
            $this->makeTokenRequest('delete', $notFoundOutput);
            // We are finished manipulating tokens, so we can log the user out.
            $this->helper->logout();
            // If tokens have been generated for use in other tests, those
            // tokens have now been invalidated and need to be regenerated.
            TokenAuthTest::nullifyTokens();
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

    /**
     * Make an HTTP request that creates, gets, or revokes a token, and make
     * assertions about the JSON response's status code, content type, and
     * body.
     *
     * @param string $method the HTTP method to use: 'get', 'post', or
     *                       'delete'.
     * @param array $output @see BaseTest::requestAndValidateJson
     * @throws Exception if there is an error making the request or running the
     *                   validation of it.
     */
    private function makeTokenRequest(string $method, array $output) {
        parent::requestAndValidateJson(
            $this->helper,
            [
                'path' => 'rest/users/current/api/token',
                'method' => $method,
                'params' => null,
                'data' => null
            ],
            $output
        );
    }
}
