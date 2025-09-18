<?php

namespace UnitTests\ETL\DataEndpoint;

use CCR\Log;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WebServerLogFileTest extends TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/dataendpoint/input/webserverlogfile";

    /**
     * @var LoggerInterface
     */
    private static $logger = null;

    public static function setUpBeforeClass(): void
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::EMERG
        );

        self::$logger = Log::factory('PHPUnit', $conf);
    }

    /**
     * @dataProvider provideWebServerLogFile
     */
    public function testWebServerLogFile($filename, $logFormat, $expected)
    {
        $config = [
            'type' => 'directoryscanner',
            'name' => 'Web Server Logs',
            'path' => self::TEST_ARTIFACT_INPUT_PATH,
            'file_pattern' => "/$filename/",
            'handler' => (object)[
                'type' => 'webserverlog',
                'record_separator' => "\n",
                'log_format' => $logFormat
            ]
        ];
        $options = new DataEndpointOptions($config);
        $endpoint = DataEndpoint::factory($options, self::$logger);
        $endpoint->verify();
        $endpoint->connect();
        $numIterations = 0;
        foreach ($endpoint as $record) {
            $this->arrays_are_same($expected[$numIterations], $record);
            $numIterations++;
        }
        $this->assertSame(
            count($expected),
            $numIterations,
            'Did not parse correct number of records.'
        );
    }

    public function provideWebServerLogFile()
    {
        $logFormats = [
            '%h %l %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i"',
            '%h %l %u %t "%m %U %H" %>s %b "%{Referer}i" "%{User-Agent}i"'
        ];
        $tests = [];
        foreach ($logFormats as $logFormat) {
            array_push(
                $tests,
                [
                    'test.log',
                    $logFormat,
                    [
                        [
                            'host' => '127.0.0.0',
                            'logname' => '-',
                            'user' => 'testuser1',
                            'stamp' => 1625127426,
                            'time' => '01/Jul/2021:03:17:06 -0500',
                            'requestMethod' => 'GET',
                            'URL' => '/pun/sys/dashboard/apps/icon/jupyter_quantum_chem/sys/sys?foo=bar',
                            'requestProtocol' => 'HTTP/1.1',
                            'status' => '200',
                            'responseBytes' => '381',
                            'HeaderReferer' => 'https://ondemand.ccr.buffalo.edu/pun/sys/dashboard/batch_connect/sessions',
                            'HeaderUserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36',
                            'ua_family' => 'Chrome',
                            'ua_major' => '91',
                            'ua_minor' => '0',
                            'ua_patch' => '4472',
                            'ua_os_family' => 'Windows',
                            'ua_os_major' => '10',
                            'ua_os_minor' => null,
                            'ua_os_patch' => null,
                            'ua_device_family' => 'Other',
                            'ua_device_brand' => null,
                            'ua_device_model' => null,
                            'geo_city_name' => 'NA',
                            'geo_subdivision' => 'NA',
                            'geo_country' => 'NA'
                        ]
                    ]
                ]
            );
        }
        return $tests;
    }

    private function arrays_are_same( array $left, array $right, bool $exact = true)
    {
        if (count(array_diff(array_keys($left), array_keys($right))) > 0) {
            $this->fail('Keys are different');
        }
        $differences = [];
        foreach($left as $lkey => $lvalue) {
            $ltype = gettype($lvalue);
            $rtype = gettype($right[$lkey]);
            if ($ltype !== $rtype) {
                $differences []= sprintf("Expected $lkey to be %s got %s", $ltype, $rtype);
                if ($exact && $lvalue !== $right[$lkey]) {
                    $differences []= sprintf("Expected $lkey value to be %s got %s", $lvalue, $right[$lkey]);
                }
            }
        }
        if (count($differences) > 0) {
            $this->fail(sprintf(
                "Differences Found:\n%s",
                implode("\n", $differences)
            ));
        }
    }
}
