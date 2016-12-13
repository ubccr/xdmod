<?php
/**
 * PhantomJS functions.
 *
 * @author Ryan Gentner
 */

namespace xd_phantomjs;

use Exception;
use xd_utilities;

/**
 * Execute a PhantomJS command.
 *
 * @param string $command The command to execute.
 *
 * @return string The command output (STDOUT only).
 */
function phantomExecute($command)
{
    $descriptor_spec = array(
        0 => array('file', '/dev/null', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w'),
    );

    $phantomjs_path = xd_utilities\getConfiguration('reporting', 'phantomjs_path');

    $pipes = array();

    $process = proc_open(
        "$phantomjs_path $command",
        $descriptor_spec,
        $pipes,
        null,
        array('DISPLAY' => ':99')
    );

    if (is_resource($process)) {
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $return_value = proc_close($process);

        if (strlen($err) > 0) {
            error_log("phantomExecute $err");
        }

        if ($return_value != 0) {
            $msg = "PhantomJS returned $return_value, stdout: $out stderr: $err";
            throw new Exception($msg);
        }

        return $out;
    } else {
        throw new Exception('Unable to create phantomjs subprocess');
    }
}
