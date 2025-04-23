<?php
namespace UnitTests\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use Configuration\JsonReferenceWithFallbackTransformer;
use Exception;
use PHPUnit\Framework\TestCase;

class JsonReferenceWithFallbackTest extends TestCase
{

    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";
    const VALID_FILE = 'rfc6901.json';
    const VALID_REFERENCE = self::VALID_FILE . '#/bar';
    const VALID_REFERENCE_2 = self::VALID_FILE . '#/foo';

    protected static $transformer = null;
    protected static $config = null;

    public static function setupBeforeClass(): void
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

    public function testMixedRefs()
    {
        $this->expectExceptionMessage('References cannot be mixed with other keys in an object: "$ref-with-fallback"');
        $this->expectException(Exception::class);
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
     */
    public function testInvalidValue($value)
    {
        $this->expectExceptionMessage('Value of "$ref-with-fallback" must be a non-empty, non-associative array of strings');
        $this->expectException(Exception::class);
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
     */
    public function testLastFileDNE($value)
    {
        $this->expectExceptionMessageMatches("/Failed to open file '(.*)': file_get_contents\((.*)\): Failed to open stream: No such file or directory/");
        $this->expectException(Exception::class);
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
     */
    public function testLastFileBadUrl($value)
    {
        $this->expectExceptionMessage("Unable to extract path from URL: badscheme://string");
        $this->expectException(Exception::class);
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

    public function testInvalidPointer()
    {
        $this->expectExceptionMessage("JSON pointer '/invalid_pointer' references a nonexistent value in file rfc6901.json");
        $this->expectException(Exception::class);
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

    public function testUndefinedVariable()
    {
        $this->expectExceptionMessage("Undefined macros in URL reference: FILE_EXTENSION in string 'rfc6901.\${FILE_EXTENSION}#/bar'");
        $this->expectException(Exception::class);
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
