<?php

namespace RegressionTests\Controllers;

use IntegrationTests\TestHarness\Utilities;
use IntegrationTests\TestHarness\XdmodTestHelper;

class UsageChartsTest extends \PHPUnit\Framework\TestCase
{
    const defaultCharReplacements = [
        '/' => '_',
        '=' => '-'
    ];

    /**
     * The path relative to this file that contains the expected hashes for this test case.
     *
     * @var string
     */
    public const HASH_DIR_REL_PATH = '/../../../artifacts/xdmod/regression/images';

    /**
     * Hash data JSON file absolute path or null.
     *
     * @var string|null
     */
    private static $hashFilePath = null;

    protected static $helper;

    // Used when running in hash-generation mode.
    protected static $imagehashes = [];

    protected static $testNames = [];

    /**
     * Determine which JSON file to use for expected hash data.
     */
    private static function getHashPath()
    {
        if (self::$hashFilePath !== null) {
            return self::$hashFilePath;
        }

        $osInfo = false;
        try {
            $osInfo = parse_ini_file('/etc/os-release');
        } catch (\Exception) {
            // if we don't have access to OS related info then that's fine, we'll just use the default expected.json
        }

        $hashFiles = [];

        // If we have OS info available to us then look for an OS specific expected output file based on this info.
        if ($osInfo !== false && isset($osInfo['VERSION_ID']) && isset($osInfo['ID'])) {
            $hashFiles[] = sprintf("expected-%s%s.json", $osInfo['ID'], $osInfo['VERSION_ID']);
        }
        // Otherwise try the default expected.json
        $hashFiles[] = 'expected.json';

        $artifactsDir = realpath(__DIR__ . self::HASH_DIR_REL_PATH);
        foreach ($hashFiles as $hashFile) {
            $hashFilePath = "$artifactsDir/$hashFile";
            if (file_exists($hashFilePath)) {
                self::$hashFilePath = $hashFilePath;
                break;
            }
        }

        if (self::$hashFilePath === null) {
            throw new \Exception('Failed to find expected data file.');
        }

        return self::$hashFilePath;
    }

    public static function tearDownAfterClass(): void
    {
        self::$helper->logout();
        if (!empty(self::$imagehashes)) {
            if (getenv('REG_TEST_FORCE_GENERATION') === '1') {
                // Overwrite test data.
                $expectedHashes = json_decode(file_get_contents(self::getHashPath()), true);
                foreach (self::$imagehashes as $testName => $hash) {
                    $expectedHashes[$testName] = $hash;
                }
                file_put_contents(self::getHashPath(), json_encode($expectedHashes, JSON_PRETTY_PRINT) . "\n");
            } else {
                // print to stdout rather than, e.g., overwriting
                // the expected results file.
                print json_encode(self::$imagehashes, JSON_PRETTY_PRINT);
            }
        }

        if (!empty(self::$testNames)) {
            $filePath = implode(DIRECTORY_SEPARATOR, [BASE_DIR,'reg_test_names.json']);
            file_put_contents($filePath, json_encode(self::$testNames, JSON_PRETTY_PRINT));
        }
    }

    private function phash($type, $imageData)
    {
        $out = "";

        if ($type === 'png' || $type === 'svg') {
            $command = '/root/bin/imagehash';
            if ($type == 'svg') {
                $command = 'rsvg-convert -f png | /root/bin/imagehash';
            }
            $pipes = [];
            $descriptor_spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $process = proc_open($command, $descriptor_spec, $pipes);
            if (!is_resource($process)) {
                throw new \Exception('Unable execute command Details: ' . print_r(error_get_last(), true));
            }
            fwrite($pipes[0], $imageData);
            fclose($pipes[0]);

            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $retval = proc_close($process);
            if (strlen($err) > 0 || $retval !== 0) {
                throw new \Exception("imagehash returned $retval stderr='$err'");
            }
        } else {
            $out = sha1($imageData);
        }

        return $out;
    }

    /**
     * @dataProvider chartSettingsProvider
     */
    public function testChartSettings($testName, $input, $expectedHash): void
    {
        $postvars = null;
        $response = self::$helper->post('/controllers/user_interface.php', $postvars, $input);

        $imageData = $response[0];
        $actualHash = $this->phash($input['format'], $imageData);

        if ($expectedHash === false || getenv('REG_TEST_FORCE_GENERATION') === '1') {
            self::$imagehashes[$testName] = $actualHash;
            $this->markTestSkipped('Created Expected output for ' . $testName);
        } else {
            if (trim($expectedHash) !== trim($actualHash)) {
                self::$testNames[] = $testName;
                $fileName = $this->massageName($testName);
                $filePath = implode(DIRECTORY_SEPARATOR, [BASE_DIR,sprintf('%s.%s', $fileName, $input['format'])]);
                file_put_contents($filePath, $imageData);
                fwrite(STDERR, sprintf("\nWriting Image to %s\n", $filePath));
            }
            $this->assertEquals(trim($expectedHash), trim($actualHash), $testName);
        }
    }

    private function massageName($testName, $includeParts = 3, $seperator = '-', $replacements = self::defaultCharReplacements)
    {
        $parts = array_slice(explode('/', $testName), 0, $includeParts);
        return implode(
            $seperator,
            array_map(
                fn($part) => strtr($part, $replacements),
                $parts
            )
        );
    }

    private function genoutput($reference, $settings, $expectedHashes)
    {
        $testName = '';
        foreach ($settings as $key => $value) {
            $reference[$key] = $value;
            $testName .= "${key}=${value}/";
        }

        $hash = false;
        if (isset($expectedHashes[$testName])) {
            $hash = $expectedHashes[$testName];
        }

        return [$testName, $reference, $hash];
    }

    public function chartSettingsProvider()
    {
        self::$helper = new XdmodTestHelper();
        self::$helper->authenticate('cd');

        $expectedHashes = json_decode(file_get_contents(self::getHashPath()), true);

        // Provide all the different combinations of chart settings except Guide Lines (which do not
        // work at all) and Hide Tooltip (which is an interactive-only setting)..
        //
        // Also we do not test and changes from the default for the following settings:
        //    legend location / off
        //    font size
        //    Chart Title override

        $reference = ['public_user' => 'false', 'realm' => 'Jobs', 'group_by' => 'pi', 'statistic' => 'total_cpu_hours', 'start_date' => '2016-12-22', 'end_date' => '2017-01-01', 'timeframe_label' => 'User Defined', 'scale' => 1, 'aggregation_unit' => 'Day', 'dataset_type' => 'timeseries', 'thumbnail' => 'n', 'query_group' => 'tg_usage', 'display_type' => 'line', 'combine_type' => 'side', 'limit' => '10', 'offset' => '0', 'log_scale' => 'n', 'show_guide_lines' => 'y', 'show_trend_line' => 'n', 'show_error_bars' => 'n', 'show_aggregate_labels' => 'n', 'show_error_labels' => 'n', 'hide_tooltip' => 'false', 'show_title' => 'y', 'width' => '916', 'height' => '484', 'legend_type' => 'bottom_center', 'font_size' => '3', 'none' => '-9999', 'format' => 'png', 'inline' => 'n', 'operation' => 'get_data', 'controller_module' => 'user_interface'];

        $statistics = ['job_count', 'total_cpu_hours', 'utilization'];
        $agg_err_stats = ['avg_waitduration_hours'];
        $errstatistics = ['avg_cpu_hours', 'avg_node_hours', 'avg_waitduration_hours'];

        $group_bys = ['none', 'person', 'resource', 'jobsize'];


        $timeseries = ['dataset_type' => ['timeseries'], 'statistic' => $statistics, 'group_by' => $group_bys, 'log_scale' => ['y', 'n'], 'format' => ['png', 'svg'], 'show_aggregate_labels' => ['y', 'n'], 'show_trend_line' => ['y', 'n'], 'display_type' => ['line', 'area', 'bar']];

        $aggregate = ['dataset_type' => ['aggregate'], 'statistic' => $statistics, 'group_by' => $group_bys, 'format' => ['png', 'svg'], 'log_scale' => ['y', 'n'], 'show_aggregate_labels' => ['y', 'n'], 'display_type' => ['line', 'h_bar', 'bar', 'pie']];

        $output = [];

        foreach (Utilities::getCombinations($timeseries) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        foreach (Utilities::getCombinations($aggregate) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        $timeseries['statistic'] = $errstatistics;
        $timeseries['show_error_bars'] = ['y', 'n'];
        $timeseries['show_error_labels'] = ['y', 'n'];

        $aggregate['statistic'] = $agg_err_stats;
        $aggregate['show_error_bars'] = ['y', 'n'];
        $aggregate['show_error_labels'] = ['y', 'n'];

        foreach (Utilities::getCombinations($timeseries) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }

        foreach (Utilities::getCombinations($aggregate) as $settings) {
            $output[] = $this->genoutput($reference, $settings, $expectedHashes);
        }
        if (getenv('REG_TEST_ALL') === '1') {
            return $output;
        } else {
            return array_intersect_key($output, array_flip(array_rand($output, 35)));
        }
    }
}
