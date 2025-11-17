<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use IntegrationTests\TokenAuthTest;
use Models\Services\Tokens;
use stdClass;
use IntegrationTests\TestHarness\Utilities;
use IntegrationTests\TestHarness\XdmodTestHelper;

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
                'Reed',
                'Bunting',
                'centerdirector@example.com',
                'Center Director - screw',
                'Center Director - screw',
                '97'
            ],
            'cs' => [
                'Turtle',
                'Dove',
                'centerstaff@example.com',
                'Center Staff - screw',
                'Center Staff - screw',
                '111'
            ],
            'pi' => [
                'Caspian',
                'Tern',
                'principal@example.com',
                'Principal Investigator',
                'Principal Investigator',
                '9'
            ],
            'usr' => [
                '',
                'Whimbrel',
                'normaluser@example.com',
                'User',
                'User',
                '114'
            ],
            'mgr' => [
                'Admin',
                'User',
                'admin@localhost',
                'User',
                'User',
                '-2'
            ]
        ];
        foreach ($expectedResultsByRole as $role => $expectedResults) {
            list(
                $firstName,
                $lastName,
                $emailAddress,
                $activeRole,
                $mostPrivilegedRole,
                $personId
            ) = $expectedResults;
            $expectedResults = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email_address' => $emailAddress,
                'is_sso_user' => false,
                'first_time_login' => false,
                'autoload_suppression' => false,
                'field_of_science' => '0',
                'active_role' => $activeRole,
                'most_privileged_role' => $mostPrivilegedRole,
                'person_id' => $personId,
                'raw_data_allowed_realms' => [
                    'Jobs',
                    'Cloud'
                ]
            ];
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
     * @dataProvider provideUpdateCurrentUser
     */
    public function testUpdateCurrentUser($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
    }

    public function provideUpdateCurrentUser()
    {
        $validInput = [
            'path' => 'rest/users/current',
            'method' => 'patch',
            'params' => null,
            'data' => []
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            ['string_params' => [
                'first_name',
                'last_name',
                'email_address',
                'password'
            ]]
        );
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
                    $this->assertMatchesRegularExpression(
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
