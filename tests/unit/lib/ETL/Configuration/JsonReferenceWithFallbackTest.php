<?php
namespace UnitTests\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use Configuration\JsonReferenceWithFallbackTransformer;

class JsonReferenceWithFallbackTest extends \PHPUnit_Framework_TestCase
{

    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";
    const VALID_FILE = 'rfc6901.json';
    const VALID_REFERENCE = self::VALID_FILE . '#/bar';
    const VALID_REFERENCE_2 = self::VALID_FILE . '#/foo';

    protected static $transformer = null;
    protected static $config = null;

    public static function setupBeforeClass()
    {
      // Set up a logger so we can get warnings and error messages from the ETL infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::EMERG
        );
        $logger = Log::factory('PHPUnit', $conf);

        // Configuration is used in the transformer to qualify relative paths
        self::$config = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        self::$transformer = new JsonReferenceWithFallbackTransformer($logger);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage References cannot be mixed with other keys in an object: "$ref-with-fallback"
     */
    public function testMixedRefs()
    {
        $this->runTransformTest([self::VALID_FILE], null, ['foo' => 'bar']);
    }

    private function runTransformTest($value, $expected = null, $additionalKeys = [])
    {
        $key = JsonReferenceWithFallbackTransformer::REFERENCE_KEY;
        $obj = (object)array_merge([$key => $value], $additionalKeys);
        self::$transformer->transform($key, $value, $obj, self::$config, Log::ERR);
        $this->assertNull($key);
        $this->assertEquals($expected, $value);
    }

    /**
     * @dataProvider provideInvalidValue
     * @expectedException Exception
     * @expectedExceptionMessage Value of "$ref-with-fallback" must be a non-empty, non-associative array of strings
     */
    public function testInvalidValue($value)
    {
        $this->runTransformTest($value);
    }

    public function provideInvalidValue()
    {
        return [
            [2],
            ['foo'],
            [['foo' => 'bar']],
            [[]],
            [[2]],
            [['foo', 2]],
            [[['foo' => 'bar']]],
            [[[]]]
        ];
    }

    /**
     * @dataProvider provideLastFileDNE
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Failed to open file '[^']+file_does_not_exist.txt': file_get_contents\([^)]+\): failed to open stream: No such file or directory/
     */
    public function testLastFileDNE($value)
    {
        $this->runTransformTest($value);
    }

    public function provideLastFileDNE()
    {
        return [
            [['file_does_not_exist.txt']],
            [['foo', 'file_does_not_exist.txt']],
            [['foo', 'bar', 'file_does_not_exist.txt']]
        ];
    }

    /**
     * @dataProvider provideLastFileBadUrl
     * @expectedException Exception
     * @expectedExceptionMessage Unable to extract path from URL: badscheme://string
     */
    public function testLastFileBadUrl($value)
    {
        $this->runTransformTest($value);
    }

    public function provideLastFileBadUrl()
    {
        return [
            [['badscheme://string']],
            [['foo', 'badscheme://string']],
            [['foo', 'bar', 'badscheme://string']]
        ];
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage JSON pointer '/invalid_pointer' references a nonexistent value in file rfc6901.json
     */
    public function testInvalidPointer()
    {
        $this->runTransformTest([self::VALID_FILE . '#/invalid_pointer']);
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess($value, $expected)
    {
        $this->runTransformTest($value, $expected);
    }

    public function provideSuccess()
    {
        return [
            [[self::VALID_REFERENCE], 99],
            [['foo', self::VALID_REFERENCE], 99],
            [['foo', self::VALID_REFERENCE, 'bar'], 99],
            [['foo', self::VALID_REFERENCE, self::VALID_REFERENCE_2], 99],
            [['foo', 'bar', self::VALID_REFERENCE], 99],
            [
                [self::VALID_FILE . '#/key1'],
                json_decode(
                    file_get_contents(
                        self::TEST_ARTIFACT_OUTPUT_PATH
                        . '/rfc6901_object.json'
                    )
                )
            ],
            [[self::VALID_FILE . '#/foo/1'], 'two'],
            [[self::VALID_FILE . '#/key1/key2/key3/-'], 5],
            [[self::VALID_FILE . '#/a~1b'], 'specialchar']
        ];
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Undefined macros in URL reference: FILE_EXTENSION in string 'rfc6901.${FILE_EXTENSION}#/bar'
     */
    public function testUndefinedVariable()
    {
        self::$config->getVariableStore()->FILENAME = 'rfc6901';

        $this->runTransformTest(['${FILENAME}.${FILE_EXTENSION}#/bar']);
    }

    public function testSuccessWithVariable()
    {
        self::$config->getVariableStore()->FILENAME = 'rfc6901';
        self::$config->getVariableStore()->FILE_EXTENSION = 'json';

        $this->runTransformTest(['${FILENAME}.${FILE_EXTENSION}#/bar'], 99);
    }
}
