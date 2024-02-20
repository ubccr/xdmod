<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use OpenXdmod\Shredder;

/**
 * SGE shredder test class.
 */
class SgeShredderTest extends JobShredderBaseTestCase
{
    const TEST_GROUP = 'unit/shredder/sge';

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('sge', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Sge', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Sge')
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

        $shredder->setLogger($this->logger);

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return $this->getLogFileTestCases('accounting-logs');
    }
}
