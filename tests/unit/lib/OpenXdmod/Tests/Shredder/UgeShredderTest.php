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
    public const TEST_GROUP = 'unit/shredder/uge';

    public function testShredderConstructor(): void
    {
        $shredder = Shredder::factory('uge', $this->db);
        $this->assertInstanceOf(\OpenXdmod\Shredder\Uge::class, $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row): void
    {
        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Uge::class)
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

        $shredder->setLogger(\CCR\Log::singleton('null'));

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return $this->getLogFileTestCases('accounting-logs');
    }
}
