<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL Directory Scanner Data Endpoints.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-05-31
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\DataEndpoint;

use Exception;
use CCR\Log;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class DirectoryScanner extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dataendpoint/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dataendpoint/output";
    private $logger = null;

    public function __construct()
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::WARNING
        );

        $this->logger = Log::factory('PHPUnit', $conf);
    }  // __construct()

    /**
     * Test passing options with the wrong types.
     *
     * @expectedException Exception
     */

    public function testInvalidOptions()
    {
        $config = array(
            'name' => 'Invalid Options',
            'type' => 'directoryscanner',
            'path' => '/dev/null',
            'file_pattern' => 1,
            'directory_pattern' => false,
            'recursion_depth' => '2',
            'last_modified_start' => 10,
            'last_modified_end' => 10,
            'handler' => (object) array(
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
    }  // testInvalidOptions()

    /**
     * Test trying to read a file instead of a directory.
     *
     * @expectedException Exception
     */

    public function testNotDirectory()
    {
        $config = array(
            'name' => 'Not a directory',
            'type' => 'directoryscanner',
            'path' => '/dev/null',
            'handler' => (object) array(
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();

    }  // testNotDirectory()

    /**
     * Test basic scanner with no filters and default handler for all file types. This
     * directory includes a file that is empty (has no records).
     */

    public function testBasicOptions()
    {
        $config = array(
            'name' => 'Euca files',
            'type' => 'directoryscanner',
            'path' => self::TEST_ARTIFACT_INPUT_PATH . '/directory_scanner',
            'handler' => (object) array(
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
        $scanner->connect();

        // The directory is scanned when connect() is called but each file isn't processed
        // until we iterate over it.

        foreach ( $scanner as $key => $val ) {
            $this->logger->info($key);
        }

        // One file in the directory contains no data.

        $this->assertEquals(3, $scanner->getNumFilesScanned());
        $this->assertEquals(6, $scanner->getNumRecordsParsed());

    }  // testBasicOptions()

    /**
     * Test trying to read a file filtered using the file_fileter regex.
     */

    public function testPatternFilters()
    {
        // Restrict to directories containing "_scanner" and files matching "euca*.json"

        $config = array(
            'name' => 'Euca files',
            'type' => 'directoryscanner',
            'path' => self::TEST_ARTIFACT_INPUT_PATH,
            'directory_pattern' => '/_scanner/',
            'file_pattern' => '/euca.*\.json/',
            'handler' => (object) array(
                'extension' => '.json',
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
        $scanner->connect();

        // The directory is scanned when connect() is called but each file isn't processed
        // until we iterate over it.

        foreach ( $scanner as $key => $val ) {
        }

        $this->assertEquals(3, $scanner->getNumFilesScanned());
        $this->assertEquals(6, $scanner->getNumRecordsParsed());

    }  // testPatternFilters()

    /**
     * Test trying to read a file modified between a particular start and end date.
     */

    public function testLastModifiedFilters()
    {
        // Restrict to files matching "euca*.json", and modified recently.

        $origFile = self::TEST_ARTIFACT_INPUT_PATH . '/euca_acct.json';
        $newFile = self::TEST_ARTIFACT_INPUT_PATH . '/directory_scanner/euca_my_new_file.json';
        $startDate = date('c');
        @copy($origFile, $newFile);
        sleep(1);
        $endDate = date('c');

        $config = array(
            'name' => 'Euca files',
            'type' => 'directoryscanner',
            'path' => self::TEST_ARTIFACT_INPUT_PATH,
            'file_pattern' => '/euca.*\.json/',
            'last_modified_start' => $startDate,
            'last_modified_end' => $endDate,
            'handler' => (object) array(
                'extension' => '.json',
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
        $scanner->connect();

        // The directory is scanned when connect() is called but each file isn't processed
        // until we iterate over it.

        foreach ( $scanner as $key => $val ) {
        }

        @unlink($newFile);
        $this->assertEquals(1, $scanner->getNumFilesScanned());
        $this->assertEquals(2, $scanner->getNumRecordsParsed());

    }  // testLastModifiedFilter()

    /**
     * Test catching a bad regex.
     *
     * @expectedException Exception
     */

    public function testLastModifiedBadRegex()
    {
        // Restrict to files matching "*.json", and modified in the specified range.

        $config = array(
            'name' => 'Files using regex',
            'type' => 'directoryscanner',
            'path' => sys_get_temp_dir(),
            'file_pattern' => '/\.json$/',
            'last_modified_start' => '2018-02-01',
            'last_modified_end' => '2018-03-31',
            'last_modified_file_regex' => 'badregex',
            'handler' => (object) array(
                'extension' => '.json',
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);

    }  // testLastModifiedFilterUsingRegex()

    /**
     * Test a bad regex that matches the file but is not a timestamp.
     */

    public function testLastModifiedRegexNotTimestamp()
    {
        $origFile = self::TEST_ARTIFACT_INPUT_PATH . '/euca_acct.json';
        $newDir = sys_get_temp_dir() . '/xdmod_test';
        @mkdir($newDir, 0750, true);

        $fileList = array();
        $startDate = '2018-02-01';
        $endDate = '2018-03-31';
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-2018-01-01.json'));
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-regex-not-timestamp.json'));

        $config = array(
            'name' => 'Files using regex that is not a timestamp',
            'type' => 'directoryscanner',
            'path' => $newDir,
            'file_pattern' => '/\.json$/',
            'last_modified_start' => '2018-02-01',
            'last_modified_end' => '2018-03-31',
            'last_modified_file_regex' => '/regex-not-timestamp/',
            'handler' => (object) array(
                'extension' => '.json',
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
        $scanner->connect();

        // The directory is scanned when connect() is called but each file isn't processed
        // until we iterate over it.

        foreach ( $scanner as $key => $val ) {
        }

        // Cleanup

        foreach ( $fileList as $file ) {
            @unlink($file);
        }
        rmdir($newDir);

        $this->assertEquals(0, $scanner->getNumFilesScanned(), "Number of files scanned using bad last modified regex");
    }  // testLastModifiedFilterRegexNotTimestamp()

    /**
     * Test trying to read a file modified between a particular start and end date using
     * a modification time parsed from the filename using a regex.
     */

    public function testLastModifiedFiltersUsingRegex()
    {
        // Restrict to files matching "*.json", and modified in the specified range.

        $origFile = self::TEST_ARTIFACT_INPUT_PATH . '/euca_acct.json';
        $newDir = sys_get_temp_dir() . '/xdmod_test';
        @mkdir($newDir, 0750, true);

        $fileList = array();
        $startDate = '2018-02-01';
        $endDate = '2018-03-31';
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-2018-01-01.json'));
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-2018-02-01.json'));
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-2018-03-01.json'));
        @copy($origFile, $fileList[] = sprintf('%s/%s', $newDir, 'file-2018-04-01.json'));

        $config = array(
            'name' => 'Files using regex',
            'type' => 'directoryscanner',
            'path' => $newDir,
            'file_pattern' => '/\.json$/',
            'last_modified_start' => '2018-02-01',
            'last_modified_end' => '2018-03-31',
            'last_modified_file_regex' => '/\d{4}-\d{2}-\d{2}/',
            'handler' => (object) array(
                'extension' => '.json',
                'type' => 'jsonfile',
                'record_separator' => "\n"
            )
        );
        $options = new DataEndpointOptions($config);
        $scanner = DataEndpoint::factory($options, $this->logger);
        $scanner->verify();
        $scanner->connect();

        // The directory is scanned when connect() is called but each file isn't processed
        // until we iterate over it.

        foreach ( $scanner as $key => $val ) {
        }

        // Cleanup

        foreach ( $fileList as $file ) {
            @unlink($file);
        }
        rmdir($newDir);

        $this->assertEquals(2, $scanner->getNumFilesScanned(), "Number of files scanned using last modified regex");
        $this->assertEquals(4, $scanner->getNumRecordsParsed(), "Number of records found using last modified regex");

    }  // testLastModifiedFilterUsingRegex()
}  // class DirectoryScannerTest
