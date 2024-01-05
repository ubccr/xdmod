<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Build;

use OpenXdmod\Build\Config;

/**
 * Build configuration file map test class.
 */
class FileMapConfigTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider fileMapConfigProvider
     */
    public function testFileMapNormalization($map, $normalizedMap): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'file-map-config-test-');

        file_put_contents(
            $tmpFile,
            json_encode(
                ['name' => 'test', 'version' => '0.0.0', 'files' => [], 'file_maps' => ['test' => $map]]
            )
        );

        $config = Config::createFromConfigFile($tmpFile);

        $this->assertEquals(
            $config->getFileMaps(),
            ['test' => $normalizedMap]
        );

        unlink($tmpFile);
    }

    public function fileMapConfigProvider()
    {
        return [
            // Array converted to object.
            [['source1', 'path/to/source2'], ['source1' => 'source1', 'path/to/source2' => 'source2']],
            // Object in array.
            [['source1', ['path/to/source2' => 'dest2']], ['source1' => 'source1', 'path/to/source2' => 'dest2']],
            // Empty string destination.
            [['source' => ''], ['source' => 'source']],
            // Deep path with empty string destination.
            [['path/to/file' => ''], ['path/to/file' => 'file']],
            // True destination.
            [['path/to/file' => true], ['path/to/file' => 'path/to/file']],
            // Trailing slash.
            [['path/to/dir/' => ''], ['path/to/dir/' => '']],
            // Trailing slash with destination.
            [['path/to/dir/' => 'dest'], ['path/to/dir/' => 'dest']],
        ];
    }
}
