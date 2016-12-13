<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Build;

use OpenXdmod\Build\Config;

/**
 * Build configuration file map test class.
 */
class FileMapConfigTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider fileMapConfigProvider
     */
    public function testFileMapNormalization($map, $normalizedMap)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'file-map-config-test-');

        file_put_contents(
            $tmpFile,
            json_encode(
                array(
                    'name' => 'test',
                    'version' => '0.0.0',
                    'files' => array(),
                    'file_maps' => array('test' => $map)
                )
            )
        );

        $config = Config::createFromConfigFile($tmpFile);

        $this->assertEquals(
            $config->getFileMaps(),
            array('test' => $normalizedMap)
        );

        unlink($tmpFile);
    }

    public function fileMapConfigProvider()
    {
        return array(

            // Array converted to object.
            array(
                array(
                    'source1',
                    'path/to/source2',
                ),
                array(
                    'source1' => 'source1',
                    'path/to/source2' => 'source2',
                ),
            ),

            // Object in array.
            array(
                array(
                    'source1',
                    array('path/to/source2' => 'dest2'),
                ),
                array(
                    'source1' => 'source1',
                    'path/to/source2' => 'dest2',
                ),
            ),

            // Empty string destination.
            array(
                array(
                    'source' => '',
                ),
                array(
                    'source' => 'source',
                ),
            ),

            // Deep path with empty string destination.
            array(
                array(
                    'path/to/file' => '',
                ),
                array(
                    'path/to/file' => 'file',
                ),
            ),


            // True destination.
            array(
                array(
                    'path/to/file' => true,
                ),
                array(
                    'path/to/file' => 'path/to/file',
                ),
            ),

            // Trailing slash.
            array(
                array(
                    'path/to/dir/' => '',
                ),
                array(
                    'path/to/dir/' => '',
                ),
            ),

            // Trailing slash with destination.
            array(
                array(
                    'path/to/dir/' => 'dest',
                ),
                array(
                    'path/to/dir/' => 'dest',
                ),
            ),
        );
    }
}
