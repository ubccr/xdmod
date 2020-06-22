<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use OpenXdmod\Shredder;

/**
 * LSF shredder test class.
 */
class LsfShredderTest extends JobShredderBaseTestCase
{
    const TEST_GROUP = 'unit/shredder/lsf';

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('lsf', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Lsf', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredder($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Lsf')
            ->disableOriginalConstructor()
            ->setMethods(array('insertRow', 'getResourceConfig'))
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
