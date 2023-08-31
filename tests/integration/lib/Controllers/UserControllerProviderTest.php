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
     * @dataProvider provideGetCurrentUser
     */
    public function testGetCurrentUser($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetCurrentUser()
    {
        $validInput = [
            'path' => 'rest/users/current',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Test authentication.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            ['authentication' => true]
        );
        // Test successful requests.
        $expectedResultsByRole = [
            'cd' => [
                'first_name' => 'Reed',
                'last_name' => 'Bunting',
                'email_address' => 'centerdirector@example.com',
                'active_role' => 'Center Director - screw',
                'most_privileged_role' => 'Center Director - screw',
                'person_id' => '97'
            ],
            'cs' => [
                'first_name' => 'Turtle',
                'last_name' => 'Dove',
                'email_address' => 'centerstaff@example.com',
                'active_role' => 'Center Staff - screw',
                'most_privileged_role' => 'Center Staff - screw',
                'person_id' => '111'
            ],
            'pi' => [
                'first_name' => 'Caspian',
                'last_name' => 'Tern',
                'email_address' => 'principal@example.com',
                'active_role' => 'Principal Investigator',
                'most_privileged_role' => 'Principal Investigator',
                'person_id' => '9'
            ],
            'usr' => [
                'first_name' => '',
                'last_name' => 'Whimbrel',
                'email_address' => 'normaluser@example.com',
                'active_role' => 'User',
                'most_privileged_role' => 'User',
                'person_id' => '114'
            ],
            'mgr' => [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email_address' => 'admin@localhost',
                'active_role' => 'User',
                'most_privileged_role' => 'User',
                'person_id' => '-1'
            ]
        ];
        foreach ($expectedResultsByRole as $role => $expectedResults) {
            $expectedResults = array_merge(
                $expectedResults,
                [
                    'is_sso_user' => false,
                    'first_time_login' => false,
                    'autoload_suppression' => false,
                    'field_of_science' => '0',
                    'raw_data_allowed_realms' => [
                        'Jobs',
                        'Cloud'
                    ]
                ]
            );
            $tests[] = [
                'success_' . $role,
                $role,
                $validInput,
                parent::validateSuccessResponse([
                    'success' => true,
                    'results' => $expectedResults
                ])
            ];
        }
        return $tests;
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
                    parent::validateAuthorizationErrorResponse(401)
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
                'body_validator' => parent::validateErrorResponseBody(
                    'API token not found.',
                    0
                )
            ];
            $this->makeTokenRequest('get', $notFoundOutput);
            // Since the user still doesn't have a token, creating one should
            // succeed.
            $this->makeTokenRequest(
                'post',
                parent::validateSuccessResponse(function ($body) {
                    $this->assertRegExp(
                        '/^[0-9]+\\.[0-9a-f]{64}$/',
                        $body['data']['token']
                    );
                    parent::validateDate($body['data']['expiration_date']);
                })
            );
            // Now that the user has a token, getting it should succeed.
            $this->makeTokenRequest(
                'get',
                parent::validateSuccessResponse(function ($body) {
                    parent::validateDate($body['data']['created_on']);
                    parent::validateDate($body['data']['expiration_date']);
                })
            );
            // Since the user still has a token and can only have one at a
            // time, creating a new one should fail.
            $this->makeTokenRequest(
                'post',
                [
                    'status_code' => 409,
                    'body_validator' => parent::validateErrorResponseBody(
                        'Token already exists.',
                        0
                    )
                ]
            );
            // Since the user has a token, revoking it should succeed.
            $this->makeTokenRequest(
                'delete',
                parent::validateSuccessResponse(function ($body) {
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
    private function makeTokenRequest($method, array $output) {
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
