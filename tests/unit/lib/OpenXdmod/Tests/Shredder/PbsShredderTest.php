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
    public const TEST_GROUP = 'unit/shredder/pbs';

    public function testShredderConstructor(): void
    {
        $shredder = Shredder::factory('pbs', $this->db);
        $this->assertInstanceOf(\OpenXdmod\Shredder\Pbs::class, $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row): void
    {
        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Pbs::class)
            ->setConstructorArgs([$this->db])
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
