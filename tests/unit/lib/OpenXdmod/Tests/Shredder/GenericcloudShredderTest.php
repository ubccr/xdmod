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

    public function setUp(): void
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor(): void
    {
        $shredder = Shredder::factory('genericcloud', $this->db);
        $this->assertInstanceOf(\OpenXdmod\Shredder\Genericcloud::class, $shredder);
    }

    /**
     * Tests to make sure that if a non-existent directory is given that the shredDirectory
     * function returns false
     */
    public function testShredderParsing(): void
    {
        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Genericcloud::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['getResourceConfig'])
            ->getMock();

        $shredder
            ->method('getResourceConfig')
            ->willReturn([]);

        $shredder->setLogger(Log::singleton('null'));

        $shredder->setResource('testresource');

        $this->assertFalse($shredder->shredDirectory("/directory/does/not/exist"));
    }
}
