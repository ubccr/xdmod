<?php

namespace TestHarness;

class TestFiles
{
    const TEST_ARTIFACT_OUTPUT_PATH = './artifacts/xdmod-test-artifacts';

    /**
     * The base directory to use when retrieving test files.
     * @var string
     */
    private $baseDir;

    /**
     * The string to be used as the 'default' test environment
     * one is not found in the environment.
     * @var string
     */
    private $defaultEnvironment;

    /**
     * The current environment setting for this utility class.
     * @var string
     */
    private $env;

    /**
     * TestFiles constructor.
     * @param string $baseDir the base directory to use when retrieving test
     * @param string $defaultEnvironment the value to use as the default
     * environment if one is not found
     * files.
     */
    public function __construct($baseDir, $defaultEnvironment = null)
    {
        if (!is_dir($baseDir)) {
            throw new \Exception("Base Dir: $baseDir is not a directory. Unable to continue");
        }
        $this->baseDir = $baseDir;
        $this->defaultEnvironment = isset($defaultEnvironment) ? $defaultEnvironment : 'xdmod';
        $this->setupEnvironment();
    }


    private function setupEnvironment()
    {
        $testEnv = getenv('TEST_ENV');
        $this->env = $testEnv !== false ? $testEnv : $this->defaultEnvironment;
    }

    public function getFile($testGroup, $fileName, $type = 'output', $extension = '.json')
    {
        return implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->baseDir,
                self::TEST_ARTIFACT_OUTPUT_PATH,
                $this->env,
                $testGroup,
                $type,
                $fileName . $extension,
            )
        );
    }
}
