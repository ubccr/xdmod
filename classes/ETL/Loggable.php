<?php
/* ------------------------------------------------------------------------------------------
 * Provide logging functionality using the PEAR Logger (http://pear.github.io/Log/).
 *
 * Extending classes can access the logger using $this->logger->logmethod() or using the __call()
 * functionality as $this->logmethod().
 *
 * ------------------------------------------------------------------------------------------
 */

namespace ETL;

// PEAR logger
use Log;
use Exception;
use PDOException;
use ETL\DataEndpoint\iDataEndpoint;

class Loggable
{
    // PEAR log class
    protected $logger = null;

    /* ------------------------------------------------------------------------------------------
     * Set the provided logger or instantiate a null logger if one was not provided.  The null handler
     * consumes log events and does nothing with them.
     *
     * @param $logger A PEAR Log object or null to use the null logger.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null)
    {
        $this->setLogger($logger);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Set the logger for this object.
     *
     * @param Log $logger A logger class or NULL to use the null logger
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLogger(Log $logger = null)
    {
        $this->logger = ( null === $logger ? Log::singleton('null') : $logger );
        return $this;
    }  // setLogger()

    /* ------------------------------------------------------------------------------------------
     * Set the logger for this object.
     *
     * @param Log $logger A logger class
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function getLogger()
    {
        return $this->logger;
    }  // getLogger()

    /**
     * Determine the file and line from which the statement that executed this method was called.
     * Since the backtrace includes the call to this method, we need to go back 2 steps in the
     * backtrace.
     *
     * @return array A 2-element array containing the file and line number of the statement that
     *   executed the calling function.
     */

    protected function getCallerInfo()
    {
        $backtrace = debug_backtrace();
        $caller = next($backtrace);
        return array($caller['file'], $caller['line']);
    } // getCallerInfo()

    /* ------------------------------------------------------------------------------------------
     * Helper function to log errors in a consistent format and provide a mechanism to
     * supply additional, optional, parameters for greater detail.
     *
     * @param $message An human-readable error message
     * @param $options An optional array of additional details. Information will typically
     *   be added to the exception message as well as the log file. Currently supported
     *   options are:
     *   'log_level' => Override the default PEAR_LOG_ERR with another log level
     *   'exception' => An exception that was caught allowing us to include the message and
     *                  stack trace
     *   'sql' => SQL query being executed when the error was caught
     *   'endpoint' => DataEndpoint being used when error was caught
     *
     * @throws Exception
     * ------------------------------------------------------------------------------------------
     */

    public function logAndThrowException($message, array $options = null)
    {
        $logMessage = array();
        $message = "{$this}: " . ( is_string($message) ? $message : "" );
        $logLevel = PEAR_LOG_ERR;
        $exceptionProvided = false;

        if ( null !== $options ) {

            if ( array_key_exists('exception', $options) && $options['exception'] instanceof Exception ) {
                $message .= " Exception: '" . $options['exception']->getMessage() . "'";
                $exceptionProvided = true;
            }

            if ( array_key_exists('sql', $options) && is_string($options['sql']) ) {
                $logMessage['sql'] = $options['sql'];
                if ( $exceptionProvided ) {
                    $logMessage['stacktrace'] = $options['exception']->getTraceAsString();
                }
            }

            if ( array_key_exists('endpoint', $options) && $options['endpoint'] instanceof iDataEndpoint ) {
                $message .= " Using DataEndpoint: '" . $options['endpoint'] . "'";
            }

            if ( array_key_exists('log_level', $options) && ! empty($options['log_level']) ) {
                $logLevel = $logMessage['log_level'];
            }
        }  // if ( null !== $options )

        $logMessage['message'] = $message;

        $this->logger->log($logMessage, $logLevel);
        throw new Exception($message);

    }  // logAndThrowException()

    /* ------------------------------------------------------------------------------------------
     * Generate a string representation of the endpoint. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return A string representation of the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return "(" . get_class($this) . ")";
    }  // __toString()
}  // class Loggable
