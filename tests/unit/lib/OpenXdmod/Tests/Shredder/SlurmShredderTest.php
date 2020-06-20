<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use OpenXdmod\Shredder;

/**
 * PBS shredder test class.
 */
class SlurmShredderTest extends JobShredderBaseTestCase
{
    const TEST_GROUP = 'unit/shredder/slurm';

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('slurm', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Slurm', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
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

    /**
     * @dataProvider accountingLogWithJobArraysProvider
     */
    public function testJobArrayParsing($line, array $arrayIds)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
            ->setMethods(array('insertRow'))
            ->getMock();

        $callCount = 0;

        $shredder
            ->expects($this->exactly(count($arrayIds)))
            ->method('insertRow')
            ->with($this->callback(
                function ($subject) use (&$callCount, $arrayIds) {

                    // There is a bug in the PHPUnit version being used
                    // that calls the callback more that it should.
                    // See https://github.com/sebastianbergmann/phpunit-mock-objects/pull/311
                    if ($callCount >= count($arrayIds)) {
                        return true;
                    }

                    return $arrayIds[$callCount++] == $subject['job_array_index'];
                }
            ));

        $shredder->setLogger($this->logger);

        $shredder->shredLine($line);
    }

    /**
     * @dataProvider accountingLogWithGpuGresProvider
     */
    public function testJobGpuGresParsing($line, $gpuCount)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs([$this->db])
            ->setMethods(['insertRow'])
            ->getMock();
        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with(new \PHPUnit_Framework_Constraint_ArraySubset(['ngpus' => $gpuCount]));
        $shredder->setLogger($this->logger);
        $shredder->shredLine($line);
    }

    public function accountingLogProvider()
    {
        return $this->getLogFileTestCases('accounting-logs');
    }

    public function accountingLogWithJobArraysProvider()
    {
        return $this->getLogFileTestCases('accounting-logs-with-job-arrays');
    }

    public function accountingLogWithGpuGresProvider()
    {
        return $this->getLogFileTestCases('accounting-logs-with-gpu-gres');
    }
}
