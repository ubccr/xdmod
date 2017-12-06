<?php

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    private static $TEST_ARTIFACT_OUTPUT_PATH;

    const DEFAULT_TEST_ENVIRONMENT = 'xdmod';

    const DEFAULT_PROJECT = 'acls';

    const DEFAULT_TYPE = 'output';

    private static $ENV;

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
        self::$TEST_ARTIFACT_OUTPUT_PATH = __DIR__ . "/../artifacts/xdmod-test-artifacts/";
    }

    /**
     * @param string  $fileName
     * @param string  $project
     * @param string  $type
     * @return string
     */
    public function getTestFile($fileName, $project = self::DEFAULT_PROJECT, $type = self::DEFAULT_TYPE)
    {
        if (!isset(self::$ENV)){
            $this->setupEnvironment();
        }
        return implode(
            '',
            array(
                self::$TEST_ARTIFACT_OUTPUT_PATH,
                DIRECTORY_SEPARATOR,
                self::$ENV,
                DIRECTORY_SEPARATOR,
                $project,
                DIRECTORY_SEPARATOR,
                $type,
                DIRECTORY_SEPARATOR,
                $fileName
            )
        );
    }
}
