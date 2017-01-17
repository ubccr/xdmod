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
        $this->logger = ( null === $logger ? Log::singleton('null') : $logger );
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Set the logger for this object.
     *
     * @param Log $logger A logger class
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLogger(Log $logger)
    {
        $this->logger = $logger;
        return $this;
    }  // setLogger()

    /* ------------------------------------------------------------------------------------------
     * Helper function to log errors (including a stacktrace) in a consistent format and throw a
     * general Exception.
     *
     * @param $message An optional human-readable error message
     * @param $logLevel An optional level for the log message (default ERR)
     *
     * @throws Exception
     * ------------------------------------------------------------------------------------------
     */

    protected function logAndThrowException($message, $logLevel = PEAR_LOG_ERR)
    {
        $message = "{$this}: $message";
        $logMessage = array(
            'message'    => $message
            );
        $this->logger->log($logMessage, $logLevel);
        throw new Exception($message);
    }  // logAndThrowException

    /* ------------------------------------------------------------------------------------------
     * Helper function to log database errors (including a stacktrace) in a consistent format and
     * throw a general Exception.
     *
     * @param $sql The SQL statement
     * @param $e The exception object
     * @param $message An optional human-readable error message
     * @param $logLevel An optional level for the log message (default ERR)
     *
     * @throws Exception
     * ------------------------------------------------------------------------------------------
     */

    protected function logAndThrowSqlException($sql, PDOException $e, $message = null, $logLevel = PEAR_LOG_ERR)
    {
        $message = "{$this}: " . ( null !== $message ? "$message. " : "" ) . $e->getMessage();
        $logMessage = array(
            'message'    => $e->getMessage(),
            'sql'        => $sql,
            'stacktrace' => $e->getTraceAsString()
            );
        $this->logger->log($logMessage, $logLevel);
        throw new Exception($message);
    }  // logAndThrowSqlException

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
