<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use DMS\PHPUnitExtensions\ArraySubset\Constraint\ArraySubset;
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
        $this->assertShreddedRow($line, $row);
    }

    /**
     * Test parsing records from LSF 10.1, which contain submit extension
     * sections, and the GPU count taken from the effective resource
     * requirement.
     *
     * @dataProvider gpuAccountingLogProvider
     * @param string $line Line from an LSF accounting log file.
     * @param array $row The corresponding parsed job record.
     */
    public function testGpuShredder($line, $row)
    {
        $this->assertShreddedRow($line, $row);
    }

    /**
     * Test parsing a record that ends before the submit extension
     * section.  Every field that is stored in the database appears
     * before that point, so the record is still usable.
     *
     * @dataProvider truncatedRecordProvider
     * @param string $line Line from an LSF accounting log file.
     * @param array $row The corresponding parsed job record.
     */
    public function testTruncatedRecord($line, $row)
    {
        $this->assertShreddedRow($line, $row);
    }

    /**
     * Test parsing job commands that contain multibyte UTF-8 characters.
     *
     * @dataProvider utf8MultibyteCharsLogProvider()
     * @param string $line Line from an LSF accounting log file.
     * @param array $job Subset of the corresponding parsed job record
     *     containing UTF-8 encoded characters.
     */
    public function testUtf8MultibyteCharsParsing($line, $job)
    {
        $this->assertShreddedRow($line, new ArraySubset($job));
    }

    /**
     * Shred a single line and assert the row that would be inserted.
     *
     * @param string $line Line from an LSF accounting log file.
     * @param array|\PHPUnit\Framework\Constraint\Constraint $row The
     *     expected row.
     */
    private function assertShreddedRow($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Lsf')
            ->setConstructorArgs([$this->db])
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

    public function gpuAccountingLogProvider()
    {
        return $this->getLogFileTestCases('gpu-accounting-logs');
    }

    public function truncatedRecordProvider()
    {
        return $this->getLogFileTestCases('truncated-record');
    }

    public function utf8MultibyteCharsLogProvider()
    {
        return $this->getLogFileTestCases('utf8-multibyte-chars');
    }
}
