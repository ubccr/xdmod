<?php

namespace ComponentTests;

use IntegrationTests\TestHarness\TestFiles;
use Models\Services\Realms;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    private static $TEST_ARTIFACT_OUTPUT_PATH;

    protected static $XDMOD_REALMS;

    public const DEFAULT_TEST_ENVIRONMENT = 'open_xdmod';

    public const DEFAULT_PROJECT = 'acls';

    public const DEFAULT_TYPE = 'output';

    public const PUBLIC_USER_NAME = 'Public User';
    public const PUBLIC_ACL_NAME = 'pub';
    public const PUBLIC_USER_EXPECTED = '/public_user.json';

    public const CENTER_DIRECTOR_USER_NAME = 'centerdirector';
    public const CENTER_DIRECTOR_ACL_NAME = 'cd';
    public const CENTER_DIRECTOR_EXPECTED = '/center_director.json';

    public const CENTER_STAFF_USER_NAME = 'centerstaff';
    public const CENTER_STAFF_ACL_NAME = 'cs';
    public const CENTER_STAFF_EXPECTED = '/center_staff.json';

    public const PRINCIPAL_INVESTIGATOR_USER_NAME = 'principal';
    public const PRINCIPAL_INVESTIGATOR_ACL_NAME = 'pi';
    public const PRINCIPAL_INVESTIGATOR_EXPECTED = '/principal.json';

    public const NORMAL_USER_USER_NAME = 'normaluser';
    public const NORMAL_USER_ACL = 'usr';
    public const NORMAL_USER_EXPECTED = '/normal_user.json';

    public const VALID_SERVICE_PROVIDER_ID = 1;
    public const VALID_SERVICE_PROVIDER_NAME = 'screw';

    public const INVALID_ID = -999;
    public const INVALID_ACL_NAME = 'babbaganoush';

    // Used when creating users to test all possible combinations of the ACLs
    public const DEFAULT_CENTER = 1;
    public const DEFAULT_USER_TYPE = 3;

    public const MIN_USERS = 1;
    public const MAX_USERS = 1000;
    public const DEFAULT_TEST_USER_NAME = "test";
    public const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@test.com";

    private static $ENV;

    public static function setUpBeforeClass(): void
    {
        self::setupEnvironment();
        self::setupPaths();
        $xdmod_realms = [];
        $rawRealms = Realms::getRealms();
        foreach($rawRealms as $item) {
            array_push($xdmod_realms, strtolower($item->name));
        }
        self::$XDMOD_REALMS = $xdmod_realms;
    }
    private static function setupEnvironment(): void
    {
        $testEnvironment = getenv('TEST_ENV');
        if ($testEnvironment !== false) {
            self::$ENV = $testEnvironment;
        } else {
            self::$ENV = self::DEFAULT_TEST_ENVIRONMENT;
        }
    }

    private static function setupPaths(): void
    {
        self::$TEST_ARTIFACT_OUTPUT_PATH = __DIR__ . "/../../artifacts/xdmod";
    }

    /**
     * @return TestFiles
     * @throws \Exception
     */
    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../');
        }
        return $this->testFiles;
    }

    /**
     * @param string  $fileName
     * @param string  $project
     * @param string  $type
     * @return string
     */
    public function getTestFile($fileName, $project = self::DEFAULT_PROJECT, $type = self::DEFAULT_TYPE, $additionalDirs = [])
    {
        if (!isset(self::$ENV)){
            self::setupEnvironment();
        }

        if (!isset(self::$TEST_ARTIFACT_OUTPUT_PATH)) {
            self::setupPaths();
        }

        return implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                [self::$TEST_ARTIFACT_OUTPUT_PATH, 'xdmod', $project, $type, self::$ENV],
                $additionalDirs,
                [$fileName]
            )
        );
    }

    /**
     * Recursively filter out any elements matching a key in $keyList. Note that only keys with
     * scalar values are filtered, keys with array values are traversed.
     *
     * @param  array $keyList The list of keys to remove
     * @param  array $input The input array being filtered.
     * @return array The filtered array with specified keys removed
     */

    protected function arrayFilterKeysRecursive(array $keyList, array $input)
    {
        $tmpArray = [];
        foreach ($input as $key => &$value)
        {
            if (!in_array($key, $keyList)) {
                continue;
            } elseif (is_array($value)) {
                $value = $this->arrayFilterKeysRecursive($keyList, $value);
            }
            $tmpArray[$key] = $value;
        }
        return $tmpArray;
    }
}
