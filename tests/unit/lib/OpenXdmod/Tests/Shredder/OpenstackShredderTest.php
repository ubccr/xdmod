<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * PBS shredder test class.
 */
class OpenstackShredderTest extends \PHPUnit_Framework_TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new NullDB();
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('openstack', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Openstack', $shredder);
    }

    /**
     * Tests to make sure that if a non-existent directory is given that the shredDirectory
     * function returns false
     */
    public function testShredderParsing()
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Openstack')
            ->setConstructorArgs(array($this->db))
            ->setMethods(array('getResourceConfig'))
            ->getMock();

        $shredder
            ->method('getResourceConfig')
            ->willReturn(array());

        $shredder->setLogger(\Log::singleton('null'));

        $shredder->setResource('testresource');

        $this->assertFalse($shredder->shredDirectory("/directory/does/not/exist"));
    }
}
