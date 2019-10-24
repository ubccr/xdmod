<?php
/* ==========================================================================================
 * Manage ETL lock files. In order to allow multiple ETL processes to be run concurrently,
 * the lock files are not solely pid-based, but also take into account the actions that
 * are being executed. This allows multiple ETL pipelines to be executed concurrently as
 * long as the actions being performed do not overlap.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-12-19
 * ==========================================================================================
 */

namespace ETL;

use Exception;
use Log;
use \CCR\Loggable;

class LockFile extends Loggable
{
    /**
     * Current process PID
     *
     * @var integer|null
     */

    protected $pid = null;

    /**
     * Directory where lock files are stored, read from the configuration file
     *
     * @var string|null
     */

    protected $lockDir = null;

    /**
     * Prefix for lock files. This is set to a reasonable default initially, but can be
     * overriden by passing a value to the constructor. Pass an empty string for no
     * prefix.
     *
     * @var string
     */

    protected $lockFilePrefix = 'etlv2_';

    /**
     * File handle to the current lockfile
     *
     * @var resource|null
     */

    protected $lockFileHandle = null;

    /**
     * Path to the current lockfile
     *
     * @var string|null
     */

    protected $lockFile = null;

    /** -----------------------------------------------------------------------------------------
     * Set the provided logger or instantiate a null logger if one was not provided.  The
     * null handler consumes log events and does nothing with them.
     *
     * @param Log $logger A PEAR Log object or null to use the null logger.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($lockDir, $lockPrefix = null, Log $logger = null)
    {
        parent::__construct($logger);

        if ( null === $lockDir || "" === $lockDir ) {
            $lockDir = sys_get_temp_dir();
            $this->logger->info("Empty lock directory specified, using temp directory: $lockDir");
        }

        $this->lockDir = $lockDir;
        $this->pid = getmypid();

        if ( null !== $lockPrefix ) {
            $this->lockFilePrefix = $lockPrefix;
        }

        if ( ! is_dir($this->lockDir) ) {

            if ( file_exists($this->lockDir) ) {
                $this->logAndThrowException(
                    sprintf("Cannot create lock directory '%s': File already exists", $this->lockDir)
                );
            }

            $this->logger->info("Creating lock directory '" . $this->lockDir . "'");
            if ( false === @mkdir($this->lockDir) ) {
                $error = error_get_last();
                $this->logAndThrowException(
                    sprintf("Error creating lock directory '%s': %s", $this->lockDir, $error['message'])
                );
            }
        }  // if ( ! is_dir($this->lockDir) )

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * Generate a lock file name.
     *
     * @param integer $pid An optional PID to use rather than the current PID
     *
     * @return string A fully qualified path to the lock file.
     * ------------------------------------------------------------------------------------------
     */

    protected function generateLockfileName($pid = null)
    {
        return sprintf(
            '%s/%s%d',
            $this->lockDir,
            $this->lockFilePrefix,
            ( null !== $pid ? $pid : $this->pid )
        );
    }  // generateLockfileName()

    /** -----------------------------------------------------------------------------------------
     * Generate a lock file for the current process and action list. If any of the actions
     * are present in any other lock files, we cannot generate the lock.
     *
     * @param array $actionList A list of action names to be executed by this ETL process.
     *
     * @return boolean TRUE if the lock was generated.
     *
     * @throws Exception If a process is already running with overlapping actions.
     * @throws Exception If the lock could not be obtained.
     * ------------------------------------------------------------------------------------------
     */

    public function lock(array $actionList)
    {
        $lockFile = $this->generateLockfileName();

        // Examine all existing lock files and make sure that no actions overlap existing processes.

        if ( false === ($dh = opendir($this->lockDir)) ) {
            $error = error_get_last();
            $this->logAndThrowException(
                sprintf("Error opening lock directory '%s': %s", $this->lockDir, $error['message'])
            );
        }

        // Cleanup any lockfiles not associated with a running process

        while ( ($file = readdir($dh) ) !== false ) {
            if ( '.' == $file || '..' == $file ) {
                continue;
            } elseif ( '' != $this->lockFilePrefix && 0 !== strpos($file, $this->lockFilePrefix) ) {
                // If set, the file must match the prefix
                continue;
            }

            $file = $this->lockDir . '/' . $file;

            // If the proecess is not running, remove this lock file and continue to the next.

            if ( $this->unlock($file) ) {
                continue;
            }

            // Ensure that there are no overlapping actions with a running ETL process

            $lockFileActionList = explode(PHP_EOL, file_get_contents($file));
            $pid = array_shift($lockFileActionList);
            $actionIntersection = array_intersect($lockFileActionList, $actionList);
            if ( 0 != count($actionIntersection) ) {
                $this->logAndThrowException(
                    sprintf(
                        "Cannot obtain lock. Process '%d' already running and executing overlapping actions (%s)",
                        $pid,
                        implode(", ", $actionIntersection)
                    )
                );
            }
        }  // while ( ($file = readdir($dh) ) !== false )

        closedir($dh);

        $this->logger->info("Obtaining lock file '$lockFile'");

        $contents = implode(PHP_EOL, array_merge(array($this->pid), $actionList));

        if ( false === ($fp = @fopen($lockFile, 'w')) ) {
            $error = error_get_last();
            $this->logAndThrowException(
                sprintf("Error creating lock file '%s': %s", $lockFile, $error['message'])
            );
        }

        // Obtain an advisory lock. This advisory will be memoved if the process dies or
        // the file is closed. This appears to be contrary to the PHP docs at
        // http://php.net/manual/en/function.flock.php stating "5.3.2 The automatic
        // unlocking when the file's resource handle is closed was removed. Unlocking now
        // always has to be done manually." because the OS releases the lock automatically
        // when the file is closed.

        if ( ! flock($fp, LOCK_EX | LOCK_NB) ) {
            $this->logAndThrowException(
                sprintf("Unexpected failure to obtain lock for process %d on file %s", $this->pid, $lockFile)
            );
        }
        fwrite($fp, $contents);
        fflush($fp);

        $this->lockFile = $lockFile;
        $this->lockFileHandle = $fp;

        return true;

    }  // lock()

    /** -----------------------------------------------------------------------------------------
     * Release the specified lock file. If no file is specified, release then lockfile for
     * the current current process.
     *
     * @param string $file The name of the file to release, or NULL to release the current
     *   lockfile.
     *
     * @return boolean TRUE if the lock was released, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function unlock($file = null)
    {

        if ( null === $file && null !== $this->lockFile ) {

            @flock($this->lockFileHandle, LOCK_UN);
            @fclose($this->lockFileHandle);
            $file = $this->lockFile;
            $this->lockFileHandle = null;
            $this->lockFile = null;

        } elseif ( null !== $file ) {

            if ( false === ($fp = @fopen($file, 'r')) ) {
                $error = error_get_last();
                $this->logger->warning(
                    sprintf("Error opening file '%s': %s", $file, $error['message'])
                );
                return false;
            }

            if ( flock($fp, LOCK_EX | LOCK_NB) ) {
                $pid = trim(fgets($fp));
                $this->logger->warning("Process '$pid' is not running, releasing lock file.");
                flock($fp, LOCK_UN);
                fclose($fp);
            } else {
                fclose($fp);
                return false;
            }
        }

        // Guard against the case that someone calls unlock() before lock()

        if ( null !== $file ) {
            $this->logger->info("Releasing lock file '$file'");
            if ( null !== $file && false === @unlink($file) ) {
                $error = error_get_last();
                $this->logger->warning(
                    sprintf("Error removing lock file '%s': %s", $file, $error['message'])
                );
                return false;
            }
        }
        return true;

    }  // unlock()
}  // class Lockfile
