<?php

/* ==========================================================================================
 * Handling of ETL lock files. In order to allow multiple ETL processes to be run concurrently, the
 * lock files are not solely pid-based, but also take into account the actions that are being
 * executed. This allows multiple etl pipelines to be executed concurrently as long as the actions
 * being performed do not overlap.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-12-19
 * ==========================================================================================
 */

namespace ETL;

use \Exception;
use Log;

class LockFile extends Loggable
{
    // Process ID of the current process
    protected $pid = null;

    // Directory where lock files are stored, read from the configuration file
    protected $lockDir = null;

    // Optional prefix for lock files, read from the configuration file
    protected $lockFilePrefix = null;

    /* ------------------------------------------------------------------------------------------
     * Set the provided logger or instantiate a null logger if one was not provided.  The null handler
     * consumes log events and does nothing with them.
     *
     * @param $logger A PEAR Log object or null to use the null logger.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($lockDir, $lockPrefix = null, Log $logger = null)
    {
        parent::__construct($logger);

        if ( null === $lockDir || "" === $lockDir ) {
            $lockDir = getcwd();
            $msg = "Empty lock directory specified, using current directory '$lockDir'";
            $this->logger->info($msg);
        }

        $this->lockDir = $lockDir;
        $this->pid = getmypid();
        $this->lockFilePrefix = $lockPrefix;

        if ( ! is_dir($this->lockDir) ) {
            $this->logger->info("Creating lock directory '" . $this->lockDir . "'");
            if ( false === @mkdir($this->lockDir) ) {
                $error = error_get_last();
                $msg = "Error creating lock directory '" . $this->lockDir . "': " . $error['message'];
                $this->logAndThrowException($msg);
            }
        }  // if ( ! is_dir($this->lockDir) )

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Generate a lock file name.
     *
     * @param $pid An optional PID to use rather than the current PID
     *
     * @return A fully qualified path to the lock file.
     * ------------------------------------------------------------------------------------------
     */

    protected function generateLockfileName($pid = null)
    {
        return sprintf(
            '%s/%s%d',
            $this->lockDir,
            ( null !== $this->lockFilePrefix ? $this->lockFilePrefix : "" ),
            ( null !== $pid ? $pid : $this->pid )
        );
    }  // generateLockfileName()

    /* ------------------------------------------------------------------------------------------
     * Check if the specified process is running.
     *
     * @param $pid PID to check
     *
     * @return TRUE if a process with the specified PID is is running, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    protected function isProcessRunning($pid = null)
    {
        $pid = ( null === $pid ? $this->pid : $pid );
        $pidList = explode(PHP_EOL, shell_exec("ps -A | awk '{print $1}'"));

        if ( in_array($pid, $pidList) ) {
            return true;
        }

        return false;

    }  // isProcessRunning()

    /* ------------------------------------------------------------------------------------------
     * Generate a lock file for the current process and action list. If any of the actions are
     * present in any other lock files, we cannot generate the lock.
     *
     * @param $actionList An array of action names to be executed by this ETL process.
     *
     * @return TRUE if the lock was generated, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function lock(array $actionList)
    {
        $lockFile = $this->generateLockfileName();

        // Examine all existing lock files and make sure that no actions overlap existing processes.

        if ( false === ($dh = opendir($this->lockDir)) ) {
            $error = error_get_last();
            $msg = "Error opening lock directory '" . $this->lockDir . "': " . $error['message'];
            $this->logger->warning($msg);
            return false;
        }

        while ( ($file = readdir($dh) ) !== false ) {
            if ( '.' == $file || '..' == $file ) {
                continue;
            }

            $file = $this->lockDir . '/' . $file;

            // If the proecess is not running, remove this lock file and continue to the next.

            if ( $this->_cleanup($file) ) {
                continue;
            }

            $lockFileActionList = explode(PHP_EOL, file_get_contents($file));
            $pid = array_shift($lockFileActionList);
            $actionIntersection = array_intersect($lockFileActionList, $actionList);
            if ( 0 != count($actionIntersection) ) {
                $msg = "Cannot obtain lock. Process '$pid' already running and executing overlapping actions (" . implode(", ", $actionIntersection) . ")";
                $this->logAndThrowException($msg);
            }
        }  // while ( ($file = readdir($dh) ) !== false )

        closedir($dh);

        $this->logger->info("Obtaining lock file '$lockFile'");

        $contents = implode(PHP_EOL, array_merge(array($this->pid), $actionList));

        if ( false === @file_put_contents($lockFile, $contents) ) {
            $error = error_get_last();
            $msg = "Error creating lock file '$lockFile': " . $error['message'];
            $this->logger->warning($msg);
            return false;
        }

        return true;

    }  // lock()

    /* ------------------------------------------------------------------------------------------
     * Release the lock for the specified PID.
     *
     * @param $pid PID to check, NULL to use the PID of the current process.
     *
     * @return TRUE if the lock was released, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function unlock($pid = null)
    {
        $lockFile = $this->generateLockfileName($pid);
        $isRunning = $this->isProcessRunning($pid);

        $pid = ( null === $pid ? $this->pid : $pid );

        if ( file_exists($lockFile) ) {

            if ( ! $isRunning ) {
                $msg = "Process '$pid' is not running";
                $this->logger->warning($msg);
            }

            $this->logger->info("Releasing lock '$lockFile'");

            if ( false === @unlink($lockFile) ) {
                $error = error_get_last();
                $msg = "Error removing lock file '$lockFile': " . $error['message'];
                $this->logger->warning($msg);
                return false;
            }

        }  // if ( file_exists($lockFile) )

        return true;

    }  // unlock()

    /* ------------------------------------------------------------------------------------------
     * Clean up any lock files that do not have corresponding running processes.
     *
     * @return TRUE on success, FALSE if there was an error.
     * ------------------------------------------------------------------------------------------
     */

    public function cleanup()
    {

        if ( false === ($dh = opendir($this->lockDir)) ) {
            $error = error_get_last();
            $msg = "Error opening lock directory '" . $this->lockDir . "': " . $error['message'];
            $this->logger->warning($msg);
            return false;
        }

        while ( ($file = readdir($dh) ) !== false ) {
            if ( '.' == $file || '..' == $file ) {
                continue;
            }
            $file = $this->lockDir . '/' . $file;
            $this->_cleanup($file);
        } // while ( ($file = readdir($dh) ) !== false )

        closedir($dh);

        return true;

    }  // cleanup()

    /* ------------------------------------------------------------------------------------------
     * Check that the process associated with the lock file is running, if not then clean up the
     * lock file.
     *
     * @param $file Lock file to check
     *
     * @return TRUE if the lock was released, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    private function _cleanup($file)
    {
        $contents = explode(PHP_EOL, file_get_contents($file));
        $pid = array_shift($contents);

        if ( ! $this->isProcessRunning($pid) ) {
            return $this->unlock($pid);
        }

        return false;

    }  // _cleanup()

}  // class Lockfile
