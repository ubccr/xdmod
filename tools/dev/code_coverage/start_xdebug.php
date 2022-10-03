<?php
namespace CCR\CodeCoverage;

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

function end_coverage()
{
    $test_name = 'unknown_test';
    if (isset($_COOKIE['test_name']) && !empty($_COOKIE['test_name'])) {
        $test_name = $_COOKIE['test_name'];
    } elseif (getenv('test_name') !== false) {
        $test_name = getenv('test_name');
    }

    $test_name = preg_replace('/[\\\]+/', '_', $test_name);
    $test_name = preg_replace('/::/', '-', $test_name);

    try {
        xdebug_stop_code_coverage(false);
        $coverageName = '__CODE_COVERAGE_DIR__/coverage-' . $test_name . '-' . microtime(true);
        $codecoverageData = json_encode(xdebug_get_code_coverage());
        file_put_contents($coverageName . '.json', $codecoverageData);
    } catch (Exception $ex) {
        file_put_contents($coverageName . '.ex', $ex);
    }
}

class coverage_dumper
{
    public function __destruct()
    {
        try {
            end_coverage();
        } catch (Exception $ex) {
            echo (string)$ex;
        }
    }
}

$_coverage_dumper = new coverage_dumper();
