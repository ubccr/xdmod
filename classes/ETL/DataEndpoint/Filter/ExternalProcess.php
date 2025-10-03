<?php
/**
 * PHP stream filter implementation allowing data to be passed through an external process
 * (e.g., jq, awk, sed) for processing. The external application MUST support reading from
 * standard input and writing to standard output.
 *
 * Stream filters are added to a file handle and intercept data that is read from the file
 * (using standard file functions such as fread()). Filters process the data and pass it
 * along to the next filter and eventually to the application that requested the
 * data. This is transparent to the application that is operating on the file
 * handle. Stream filters must extend the php_user_filter class.
 *
 * @see http://php.net/manual/en/stream.filters.php
 * @see http://php.net/manual/en/class.php-user-filter.php
 */

namespace ETL\DataEndpoint\Filter;

use Psr\Log\LoggerInterface;

class ExternalProcess extends \php_user_filter
{
    /**
     * @const string Filter name, used when registering the stream filter.
     */

    const NAME = 'xdmod.external_process';

    /**
     * @const integer Number of bytes to read and write to the application pipes at once
     */

    const READ_SIZE = 1024;

    /**
     * @var string The name of the filter, populated by PHP
     */

    public $filtername = null;

    /**
     * @var object The parameters passed to this filter by stream_filter_prepend() or
     * stream_filter_append(), set by PHP. This is expected to be an object with the
     * following properties:
     *
     * path: The path to the external application. If a relative path is given, regular
     *       shell $PATH rules apply.
     * arguments: Optional argument string to be passed to the application
     * logger: Optional logger for displying error messages
     */

    public $params = null;

    /**
     * @var array An array containing file descriptors connected to the application. The following
     * indexes are expected:
     * 0: application stdin
     * 1: application stdout
     * 2: application stdout
     */

    private $pipes = null;

    /**
     * @var resource File handle returned by proc_open()
     */

    private $filterResource = null;

    /**
     * @var resource Temporary resource used when creating new buckets
     */

    private $tmpResource = null;

    /**
     * @var string The command that was executed, including arguments.
     */

    private $command = null;

    /**
     * Called when applying the filter. Move data from the input buckets to the output
     * buckets, filtering along the way.
     *
     * @param $in A resource pointing to a bucket brigade which contains one or more
     *   bucket objects containing data to be filtered.
     * @param $out A resource pointing to a second bucket brigade into which your modified
     *   buckets should be placed.
     * @param $consumed A reference to a value that should be incremented by the length of
     *   the data which your filter reads in and alters. In most cases this means you will
     *   increment consumed by $bucket->datalen for each $bucket.
     * @param $closing Set to TRUE if the stream is in the process of closing (and
     *   therefore this is the last pass through the filterchain).
     *
     * @return PSFS_PASS_ON If data has been copied to the $out brigade
     * @return PSFS_FEED_ME If the filter was successful but did not copy data to the $out brigade,
     * @return PSFS_ERR_FATAL On error.
     */

    public function filter($in, $out, &$consumed, $closing)
    {
        $retval = PSFS_FEED_ME;

        // Process all of the incoming buckets and write their data to the process
        // input pipe.

        while ( $bucket = stream_bucket_make_writeable($in) ) {
            $consumed += $bucket->datalen;
            fwrite($this->pipes[0], $bucket->data);
        }

        // Process any data that may have came out of the pipe and send it to the out
        // brigade.

        while ( ! feof($this->pipes[1]) && '' !=  ($data = fread($this->pipes[1], self::READ_SIZE)) ) {
            stream_bucket_append($out, stream_bucket_new($this->tmpResource, $data));
            $retval = PSFS_PASS_ON;
        }

        if ( $closing ) {

            // Close the process's input file so it can finish up

            @fclose($this->pipes[0]);
            $this->pipes[0] = null;

            // Process all data left on the pipe from the process

            while ( ! feof($this->pipes[1]) ) {
                $data = fread($this->pipes[1], self::READ_SIZE);
                if ( 0 != strlen($data) ) {
                    stream_bucket_append($out, stream_bucket_new($this->tmpResource, $data));
                }
            }

            $retval = PSFS_PASS_ON;

        }

        return $retval;
    }

    /**
     * Perform setup on filter instantiation. This includes setting up the external filter
     * application and opening read and write pipes to the application.
     */

    public function onCreate()
    {
        // Verify parameters

        if ( ! is_object($this->params) || ! isset($this->params->path) ) {
            fwrite(STDERR, __CLASS__ . ": Path parameter not set\n");
            return false;
        }

        if ( isset($this->params->logger) && ! $this->params->logger instanceof LoggerInterface ) {
            fwrite(STDERR, "Invalid logger, expected LoggerInterface and got " . gettype($this->params->logger) . "\n");
            return false;
        }

        $arguments = ( isset($this->params->arguments) ? " " . $this->params->arguments : "" );
        $this->command = $this->params->path . $arguments;

        if ( isset($this->params->logger) ) {
            $this->params->logger->debug(sprintf("Creating filter %s: %s", self::NAME, $this->command));
        }

        // stream_bucket_new() needs somewhere to store temporary data but the
        // documentation doesn't give any details:
        // http://php.net/manual/en/function.stream-bucket-new.php

        if ( false === ($this->tmpResource = @fopen('php://temp', 'w+')) ) {
            $errorMessage = 'Could not open php://temp for writing';
            if ( null !== ($err = error_get_last()) ) {
                $errorMessage = $err['message'];
            }
            $this->logError($errorMessage);
            return false;
        }

        // Start the process and open read and write pipes so we can interact with it. If
        // there is an error running the external process (e.g., bad arguments or syntax)
        // it may not manifest immediately because the process may take some time to set
        // up. We will need to check the exit status using proc_get_status().

        $this->filterResource = @proc_open(
            $this->command,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ),
            $this->pipes
        );

        if ( false === $this->filterResource ) {
            $errorMessage = 'Error executing command: ' . $this->command;
            if ( null !== ($err = error_get_last()) ) {
                $errorMessage = $err['message'];
            }
            $this->logError($errorMessage);
            return false;
        }

        // Set the output from the application to non-blocking mode or the fread() in
        // filter() may block while waiting for the external process to provide data
        // resulting in a deadlock.

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        return true;

    }

    /**
     * Cleanup after the filter is closed.
     */

    public function onClose()
    {
        if ($this->pipes[0]) {
            fclose($this->pipes[0]);
        }
        fclose($this->pipes[1]);

        // Read stderr stream to EOF before closing.
        $errorOutput = '';
        while (($buffer = fgets($this->pipes[2])) !== false) {
            $errorOutput .= $buffer;
        }
        fclose($this->pipes[2]);

        $exitStatus = proc_close($this->filterResource);
        fclose($this->tmpResource);

        if ($exitStatus !== 0) {
            $errorMessage = 'Error ' . $exitStatus . ' executing external filter process: ' . $errorOutput;
            $this->logError($errorMessage);
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Log error messages to stderr or the logger if it has been provided.
     *
     * @param string $message The log message
     */

    private function logError($message)
    {
        if ( isset($this->params->logger) ) {
            $this->params->logger->error($message);
        } else {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }
}
