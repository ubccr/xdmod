<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;

/**
 * UGE shredder test class.
 */
class UgeShredderTest extends JobShredderBaseTestCase
{
    const TEST_GROUP = 'unit/shredder/uge';

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('uge', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Uge', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Uge')
            ->disableOriginalConstructor()
            ->onlyMethods(array('insertRow', 'getResourceConfig'))
            ->getMock();

        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with($row);

        $shredder
            ->method('getResourceConfig')
            ->willReturn(array());

        $shredder->setLogger(\CCR\Log::singleton('null'));

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return $this->getLogFileTestCases('accounting-logs');
    }
}
