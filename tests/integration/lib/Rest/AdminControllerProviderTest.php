<?php

namespace IntegrationTests\Rest;

use IntegrationTests\BaseTest;
use IntegrationTests\TestHarness\XdmodTestHelper;

class AdminControllerProviderTest extends BaseTest
{
    private static $helper;

    public static function setupBeforeClass(): void
    {
        self::$helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideResetUserTourViewed
     */
    public function testResetUserTourViewed($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideResetUserTourViewed()
    {
        $validInput = [
            'path' => 'reset_user_tour_viewed',
            'method' => 'post',
            'params' => null,
            'data' => [
                'viewedTour' => '0',
                'uid' => '1'
            ]
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'authorization' => 'mgr',
                'int_params' => ['viewedTour', 'uid']
            ]
        );
        // Test bad request parameters.
        array_push(
            $tests,
            [
                'invalid_data_parameter',
                'mgr',
                parent::mergeParams(
                    $validInput,
                    'data',
                    ['viewedTour' => '-1']
                ),
                parent::validateBadRequestResponse('Invalid data parameter')
            ],
            [
                'user_not_found',
                'mgr',
                parent::mergeParams($validInput, 'data', ['uid' => '-1']),
                parent::validateBadRequestResponse('User not found')
            ]
        );
        // Test successful requests.
        foreach ([1, 0] as $viewedTour) {
            $tests[] = [
                'success_' . $viewedTour,
                'mgr',
                parent::mergeParams(
                    $validInput,
                    'data',
                    ['viewedTour' => "$viewedTour"]
                ),
                parent::validateSuccessResponse([
                    'success' => true,
                    'total' => 1,
                    'message' => (
                        'This user will be now be prompted to'
                        . ' view the New User Tour the next'
                        . ' time they visit XDMoD'
                    )
                ])
            ];
        }
        return $tests;
    }
}
