<?php

namespace TestHarness;

class TestFiles
{
    const TEST_ARTIFACT_OUTPUT_PATH = '../../artifacts/xdmod-test-artifacts';
    private static $ENV;


    private static function setupEnvironment()
    {
        $testEnv = getenv('TEST_ENV');
        self::$ENV = $testEnv !== false ? $testEnv : 'xdmod';
    }

    public static function getFile($testGroup, $fileName, $type = 'output')
    {
        if (!isset(self::$ENV)){
            self::setupEnvironment();
        }
        return implode(
            DIRECTORY_SEPARATOR,
            array(
                __DIR__ ,
                self::TEST_ARTIFACT_OUTPUT_PATH ,
                self::$ENV,
                $testGroup,
                $type,
                $fileName . '.json',
            )
        );
    }
}
