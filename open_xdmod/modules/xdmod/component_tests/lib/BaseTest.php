<?php

namespace ComponentTests;

use TestHarness\TestFiles;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    private static $TEST_ARTIFACT_OUTPUT_PATH;

    const DEFAULT_TEST_ENVIRONMENT = 'open_xdmod';

    const DEFAULT_PROJECT = 'acls';

    const DEFAULT_TYPE = 'output';

    const PUBLIC_USER_NAME = 'Public User';
    const PUBLIC_ACL_NAME = 'pub';
    const PUBLIC_USER_EXPECTED = '/public_user.json';

    const CENTER_DIRECTOR_USER_NAME = 'centerdirector';
    const CENTER_DIRECTOR_ACL_NAME = 'cd';
    const CENTER_DIRECTOR_EXPECTED = '/center_director.json';

    const CENTER_STAFF_USER_NAME = 'centerstaff';
    const CENTER_STAFF_ACL_NAME = 'cs';
    const CENTER_STAFF_EXPECTED = '/center_staff.json';

    const PRINCIPAL_INVESTIGATOR_USER_NAME = 'principal';
    const PRINCIPAL_INVESTIGATOR_ACL_NAME = 'pi';
    const PRINCIPAL_INVESTIGATOR_EXPECTED = '/principal.json';

    const NORMAL_USER_USER_NAME = 'normaluser';
    const NORMAL_USER_ACL = 'usr';
    const NORMAL_USER_EXPECTED = '/normal_user.json';

    const VALID_SERVICE_PROVIDER_ID = 1;
    const VALID_SERVICE_PROVIDER_NAME = 'screw';

    const INVALID_ID = -999;
    const INVALID_ACL_NAME = 'babbaganoush';

    const DEFAULT_CENTER = 1;
    const DEFAULT_USER_TYPE = 3;

    const MIN_USERS = 1;
    const MAX_USERS = 1000;
    const DEFAULT_TEST_USER_NAME = "test";
    const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@test.com";

    private static $ENV;

    /**
     * @var TestFiles
     */
    private $testFiles = null;

    public static function setUpBeforeClass()
    {
        self::setupEnvironment();
        self::setupPaths();
    }
    private static function setupEnvironment()
    {
        $testEnvironment = getenv('TEST_ENV');
        if ($testEnvironment !== false) {
            self::$ENV = $testEnvironment;
        } else {
            self::$ENV = self::DEFAULT_TEST_ENVIRONMENT;
        }
    }

    private static function setupPaths()
    {
        self::$TEST_ARTIFACT_OUTPUT_PATH = __DIR__ . "/../artifacts/xdmod-test-artifacts";
    }

    public function getTestFiles()
    {
        if ( ! isset($this->testFiles) ) {
            $this->testFiles = new TestFiles(__DIR__ . '/../');
        }
        return $this->testFiles;
    }
}
