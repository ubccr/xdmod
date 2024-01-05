<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use OpenXdmod\Shredder;

/**
 * LSF shredder test class.
 */
class LsfShredderTest extends JobShredderBaseTestCase
{
    public const TEST_GROUP = 'unit/shredder/lsf';

    public function testShredderConstructor(): void
    {
        $shredder = Shredder::factory('lsf', $this->db);
        $this->assertInstanceOf(\OpenXdmod\Shredder\Lsf::class, $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredder($line, $row): void
    {
        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Lsf::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['insertRow', 'getResourceConfig'])
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


    /**
     * Test parsing job commands that contain multibyte UTF-8 characters.
     *
     * @dataProvider utf8MultibyteCharsLogProvider()
     * @param string $line Line from an LSF accounting log file.
     * @param array $job Subset of the corresponding parsed job record
     *     containing UTF-8 encoded characters.
     */
    public function testUtf8MultibyteCharsParsing($line, $job): void
    {

        $shredder = $this
            ->getMockBuilder(\OpenXdmod\Shredder\Lsf::class)
            ->setConstructorArgs([$this->db])
            ->onlyMethods(['insertRow', 'getResourceConfig'])
            ->getMock();
        $shredder
            ->expects($this->once())
            ->method('insertRow');
            #->with(new \PHPUnit\Framework\Constraint\ArraySubset($job));
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

    public function utf8MultibyteCharsLogProvider()
    {
        return $this->getLogFileTestCases('utf8-multibyte-chars');
    }
}
