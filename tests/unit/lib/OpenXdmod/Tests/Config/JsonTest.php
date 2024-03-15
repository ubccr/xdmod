<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Config;

/**
 * JSON config file test class.
 */
class JsonTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test that all the JSON configuration files are formatted
     * properly.
     */
    public function testJsonDecoding()
    {
        $pattern = BASE_DIR . '/configuration/*.json';
        $jsonFiles = glob($pattern);
        sort($jsonFiles);

        foreach ($jsonFiles as $file) {
            $contents = file_get_contents($file);
            $this->assertNotFalse($contents, "Got contents of $file");
            $data = json_decode($contents, true);
            $this->assertNotNull($data, "Decoded $file");
        }
    }
}
