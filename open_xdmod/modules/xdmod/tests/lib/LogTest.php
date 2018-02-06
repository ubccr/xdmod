<?php
/** -----------------------------------------------------------------------------------------
 * Tests for ubccr/Log class forked from pear/Log.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2018-02-05
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting;

use CCR\Log;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test various log levels including the newly added TRACE.
     *
     * @dataProvider logLevelProvider
     */

    public function testLogLevels($logLevel, $expectedLines)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), "xdmod_test_");

        $conf = array(
            'mail'         => false,
            'db'           => false,
            'console'      => false,
            'file'         => $tmpFile,
            'fileLogLevel' => $logLevel
        );

        // Note that we can't use setMask() to change the log mask because it will not propogate
        // to child loggers.

        $logger = Log::factory('', $conf);

        // Log messages at each level and compare the number of actual lines output to the number
        // expected based on the log level mask.

        $logger->emerg('Emergency');
        $logger->alert('Alert');
        $logger->crit('Critical');
        $logger->err('Error');
        $logger->warning('Warning');
        $logger->notice('Notice');
        $logger->info('Info');
        $logger->debug('Debug');
        $logger->trace('Trace');

        $logMessages = file($tmpFile);
        unlink($tmpFile);
        $this->assertEquals(
            $expectedLines,
            count($logMessages),
            sprintf("Logger output %d lines, expecting %d", count($logMessages), $expectedLines)
        );
    }

    /**
     * Provide data for the testLogLevels() function.  Return a list of tuples containing the max
     * level and the expected number of messages for that level.
     *
     * @return array An array containing tuples (log_level, num expected results)
     */

    public function logLevelProvider()
    {
        return array(
            array(Log::TRACE, 9),
            array(Log::WARNING, 5),
            array(Log::ALERT, 2)
        );
    }
}
