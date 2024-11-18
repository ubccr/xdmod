<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use OpenXdmod\Shredder;

/**
 * PBS shredder test class.
 */
class PbsShredderTest extends JobShredderBaseTestCase
{
    const TEST_GROUP = 'unit/shredder/pbs';

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('pbs', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Pbs', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Pbs')
            ->setConstructorArgs(array($this->db))
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
