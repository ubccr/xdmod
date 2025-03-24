<?php
/* ------------------------------------------------------------------------------------------
 * Test various values for an RFC-6901 pointer.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use Configuration\JsonReferenceTransformer;
use PHPUnit\Framework\TestCase;
use Exception;

class Rfc6901Test extends TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";

    private $config = null;
    private $transformer = null;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        // Configuration is used in the transformer to qualify relative paths
        $this->config = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        $this->transformer = new JsonReferenceTransformer();
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Test invalid pointer (unknown path)
     *
     *
     */

    public function testRfc6901InvalidPointer()
    {
        $this->expectException(Exception::class);
        $key = '$ref';
        $value = 'rfc6901.json#/wehavenobananastoday';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);
    }

    /**
     * Include whole document
     */

    public function testRfc6901ScalarValue()
    {
        $key = '$ref';
        $value = 'rfc6901.json#/bar';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);

        // A null key means replace the entire object with the transformed value
        $this->assertNull($key);
        $this->assertEquals($value, 99);
    }

    /**
     * Include whole document
     */

    public function testRfc6901ObjectValue()
    {
        $key = '$ref';
        $value = 'rfc6901.json#/key1';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);

        // A null key means replace the entire object with the transformed value
        $this->assertNull($key);
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/rfc6901_object.json'));
        $this->assertEquals($value, $expected);
    }

    /**
     * Include the 2nd element of an array
     */

    public function testRfc6901ArrayElement()
    {
        $key = '$ref';
        $value = 'rfc6901.json#/foo/1';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);

        // A null key means replace the entire object with the transformed value
        $this->assertNull($key);
        $this->assertEquals($value, 'two');
    }

    /**
     * Include the last element of an array
     */

    public function testRfc6901LastArrayElement()
    {
        $key = '$ref';
        $value = 'rfc6901.json#/key1/key2/key3/-';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);

        // A null key means replace the entire object with the transformed value
        $this->assertNull($key);
        $this->assertEquals($value, 5);
    }

     /**
      * Include whole document
      */

    public function testRfc6901SpecialCharacter()
    {
        $key = '$ref';
        $value = 'rfc6901.json#/a~1b';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);

        // A null key means replace the entire object with the transformed value
        $this->assertNull($key);
        $this->assertEquals($value, 'specialchar');
    }

     /**
      * Include whole document
      *
      *
      */

    public function testRfc6901BadFragment()
    {
        $this->expectException(Exception::class);
        $key = '$ref';
        $value = 'rfc6901.json#/does-not-exist';
        $obj = (object) array($key => $value);
        $this->transformer->transform($key, $value, $obj, $this->config, Log::ERR);
    }
}  // class Rfc6901Test
