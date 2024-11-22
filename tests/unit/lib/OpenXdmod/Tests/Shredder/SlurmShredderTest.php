<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace UnitTests\OpenXdmod\Tests\Shredder;

use DMS\PHPUnitExtensions\ArraySubset\Constraint\ArraySubset;
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

    /**
     * @dataProvider accountingLogWithJobArraysProvider
     */
    public function testJobArrayParsing($line, array $arrayIds)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
            ->onlyMethods(array('insertRow'))
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
     * Test how job records with non-ended job states are handled.
     *
     * @dataProvider nonEndedJobStateLogProvider
     */
    public function testNonEndedJobStateHandling($line, $messages)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs([$this->db])
            ->onlyMethods(['insertRow'])
            ->getMock();
        $shredder
            ->expects($this->never())
            ->method('insertRow');


        $logger = $this
            ->getMockBuilder('\CCR\Logger')
            ->setConstructorArgs(array('slurm-shredder-test'))
            ->onlyMethods(['debug', 'warning'])
            ->getMock();
        $logger
            ->expects($this->never())
            ->method('warning');

        // "withConsecutive" requires argument unpacking.
        call_user_func_array(
            [
                $logger->expects($this->exactly(count($messages['debug'])))
                    ->method('debug'),
                'withConsecutive'
            ],
            $this->convertLoggerArgumentsToAssertions($messages['debug'])
        );

        $shredder->setLogger($logger);
        $shredder->shredLine($line);
    }

    /**
     * Test how job records with unknown job states are handled.
     *
     * @dataProvider unknownJobStateLogProvider
     */
    public function testUnknownJobStateHandling($line, $messages)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs([$this->db])
            ->onlyMethods(['insertRow'])
            ->getMock();
        $shredder
            ->expects($this->never())
            ->method('insertRow');

        $logger = $this
            ->getMockBuilder('\CCR\Logger')
            ->setConstructorArgs(array('slurm-shredder-test'))
            ->onlyMethods(['debug', 'warning'])
            ->getMock();

        // "withConsecutive" requires argument unpacking.
        call_user_func_array(
            [
                $logger->expects($this->exactly(count($messages['debug'])))
                    ->method('debug'),
                'withConsecutive'
            ],
            $this->convertLoggerArgumentsToAssertions($messages['debug'])
        );

        // "withConsecutive" requires argument unpacking.
        call_user_func_array(
            [
                $logger->expects($this->exactly(count($messages['warning'])))
                    ->method('warning'),
                'withConsecutive'
            ],
            $this->convertLoggerArgumentsToAssertions($messages['warning'])
        );

        $shredder->setLogger($logger);
        $shredder->shredLine($line);
    }

    /**
     * Test parsing job names that contain multibyte UTF-8 characters.
     *
     * @dataProvider utf8MultibyteCharsLogProvider()
     */
    public function testUtf8MultibyteCharsParsing($line, $job)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs([$this->db])
            ->onlyMethods(['insertRow'])
            ->getMock();
        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with(new ArraySubset(['job_name' => $job['job_name']));

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

    public function nonEndedJobStateLogProvider()
    {
        return $this->getLogFileTestCases('non-ended-job-state');
    }

    public function unknownJobStateLogProvider()
    {
        return $this->getLogFileTestCases('unknown-job-state');
    }

    public function utf8MultibyteCharsLogProvider()
    {
        return $this->getLogFileTestCases('utf8-multibyte-chars');
    }

    /**
     * Convert test data to PHPUnit asserts.
     *
     * Transforms the test used to test log messages.  Input is an array of
     * strings that are regular expression.
     *
     * @param string[] $loggerPatterns
     * @return array[]
     */
    private function convertLoggerArgumentsToAssertions(array $logPatterns)
    {
        $assertions = [];
        foreach ($logPatterns as $pattern) {
            $assertions[] = [$this->matchesRegularExpression($pattern)];
        }
        return $assertions;
    }
}
