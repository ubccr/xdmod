<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use CCR\Log;
use OpenXdmod\Shredder;

/**
 * PBS shredder test class.
 */
class GenericcloudShredderTest extends \PHPUnit\Framework\TestCase
{
    protected $db;

    public function setup(): void
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('genericcloud', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Genericcloud', $shredder);
    }

    /**
     * Tests to make sure that if a non-existent directory is given that the shredDirectory
     * function returns false
     */
    public function testShredderParsing()
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Genericcloud')
            ->setConstructorArgs(array($this->db))
            ->onlyMethods(array('getResourceConfig'))
            ->getMock();

        $shredder
            ->method('getResourceConfig')
            ->willReturn(array());

        $shredder->setLogger(Log::singleton('null'));

        $shredder->setResource('testresource');

        $this->assertFalse($shredder->shredDirectory("/directory/does/not/exist"));
    }
}
