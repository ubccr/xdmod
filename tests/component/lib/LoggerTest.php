<?php

namespace ComponentTests;

use CCR\Log;
use CCR\DB;

class LoggerTest extends BaseTest
{
    public function provideFileOutput()
    {
        return array(
            array('debug', 'message field', array('other' => 1.2), '/\[DEBUG\] message field \(other: 1.2\)$/'),
            array('info', 'single line string', array(), '/\[INFO\] single line string$/'),
            array('warning', '', array('other' => 'comp123'), '/\[WARNING\] \(other: comp123\)$/'),
            array('error', '', array('exceptiontest' => new \Exception('Test Line Exception')), '/\[ERROR\] \(exceptiontest: .*' . str_replace('/', '\\/', __FILE__) . ':' . __LINE__ . '\)\W\[stacktrace\]/')
        );
    }

    /**
     * @dataProvider provideFileOutput
     * @param string $message to send to the logger
     * @param array $context to send to the logger
     * @param string $expectedRegex to match the file output content
     */
    public function testFileOutput($level, $message, $context, $expectedRegex)
    {
        $conf = array(
            'db' => false,
            'console' => false,
            'mail' => false,
            'file' => tempnam(sys_get_temp_dir(), 'component_logger_test_'),
            'fileLogLevel' => Log::DEBUG
        );

        $logger = Log::factory('log-test', $conf);
        $logger->$level($message, $context);

        $output = file_get_contents($conf['file']);
        $this->assertMatchesRegularExpression($expectedRegex, $output);
    }

    public function provideDbOutput()
    {
        return array(
            array('debug', 'message field', array('other' => 1.2), 7, '{"message":"message field","other":1.2}'),
            array('info', 'single line string', array(), 6, '{"message":"single line string"}'),
            array('warning', '', array('other' => 'comp123'), 4, '{"other":"comp123"}')
        );
    }

    /**
     * @dataProvider provideDbOutput
     * @param string $message to send to the logger
     * @param array $context to send to the logger
     * @param string $expectedPriority expected numerical priority value in the database
     * @param string $expectedJsonString expected json content string
     */
    public function testDbOutput($level, $message, $context, $expectedPriority, $expectedJsonString)
    {
        $conf = array(
            'console' => false,
            'mail' => false,
            'file' => false,
            'db' => true,
            'dbLogLevel' => Log::DEBUG
        );

        $db = DB::factory('logger');
        $initial_vals = $db->query("SELECT MAX(id) AS start_id FROM mod_logger.log_table");

        $logger = Log::factory('log-test', $conf);
        $logger->$level($message, $context);

        $logoutput = $db->query("SELECT priority, message FROM mod_logger.log_table WHERE ident = 'log-test' AND id > :start_id ORDER BY id ASC", $initial_vals[0]);
        $this->assertJsonStringEqualsJsonString($expectedJsonString, $logoutput[0]['message']);
        $this->assertEquals($expectedPriority, $logoutput[0]['priority']);
    }

    public function testDbExceptionFormat()
    {
        $ident = 'testDbExceptionFormat';

        $conf = array(
            'console' => false,
            'mail' => false,
            'file' => false,
            'db' => true,
            'dbLogLevel' => Log::DEBUG
        );

        $db = DB::factory('logger');
        $initial_vals = $db->query("SELECT MAX(id) AS start_id FROM mod_logger.log_table");

        $logger = Log::factory($ident, $conf);

        $logger->error('', array('other' => new \Exception('Test Exception')));
        $exline = __LINE__ - 1;

        $logoutput = $db->query("SELECT priority, message FROM mod_logger.log_table WHERE ident = '" . $ident . "' AND id > :start_id ORDER BY id ASC", $initial_vals[0]);

        $this->assertEquals('3', $logoutput[0]['priority']);
        $exceptionSerialization = json_decode($logoutput[0]['message'], true);

        $this->assertArrayNotHasKey('message', $exceptionSerialization);
        $this->assertArrayHasKey('other', $exceptionSerialization);
        $this->assertArrayHasKey('class', $exceptionSerialization['other']);
        $this->assertArrayHasKey('trace', $exceptionSerialization['other']);

        $this->assertEquals('Exception', $exceptionSerialization['other']['class']);
        $this->assertEquals('Test Exception', $exceptionSerialization['other']['message']);
        $this->assertEquals(__FILE__ . ":$exline", $exceptionSerialization['other']['file']);
    }

    public function testCombinedOutput()
    {
        $conf = array(
            'db' => true,
            'dbLogLevel' => Log::DEBUG,
            'console' => false,
            'mail' => false,
            'file' => tempnam(sys_get_temp_dir(), 'component_combined_test_'),
            'fileLogLevel' => Log::DEBUG
        );

        $logger = Log::factory('combined-test', $conf);

        $db = DB::factory('logger');
        $initial_vals = $db->query("SELECT MAX(id) AS start_id FROM mod_logger.log_table");

        $logger->debug('message portion', array('context' => 'portion'));

        $output = file_get_contents($conf['file']);
        $this->assertStringEndsWith("[DEBUG] message portion (context: portion)\n", $output);

        $logoutput = $db->query("SELECT priority, message FROM mod_logger.log_table WHERE ident = 'combined-test' AND id > :start_id ORDER BY id ASC", $initial_vals[0]);

        $this->assertEquals('{"message":"message portion","context":"portion"}', $logoutput[0]['message']);
        $this->assertEquals('7', $logoutput[0]['priority']);
    }
}
