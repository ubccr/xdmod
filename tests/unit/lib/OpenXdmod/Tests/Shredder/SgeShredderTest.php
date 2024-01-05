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
    public const TEST_GROUP = 'unit/shredder/sge';

    public function testShredderConstructor(): void
    {
        $shredder = Shredder::factory('sge', $this->db);
        $this->assertInstanceOf(\OpenXdmod\Shredder\Sge::class, $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row): void
    {
        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Sge::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertRow', 'getResourceConfig'])
            ->getMock();

        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with($row);

        $shredder
            ->method('getResourceConfig')
            ->willReturn([]);

        $shredder->setLogger($this->logger);

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return $this->getLogFileTestCases('accounting-logs');
    }
}
