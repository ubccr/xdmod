<?php
/**
 * Provide logging functionality using the Monolog Logger (https://github.com/Seldaek/monolog).
 *
 * Extending classes can access the logger using $this->logger->logmethod() or using the __call()
 * functionality as $this->logmethod().
 */

namespace CCR;

use Exception;
use Monolog\Logger;
use PDOException;
use ETL\DataEndpoint\iDataEndpoint;
use Psr\Log\LoggerInterface;

class Loggable
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Set the provided logger or instantiate a null logger if one was not provided.  The null handler
     * consumes log events and does nothing with them.
     *
     * @param LoggerInterface $logger A Monolog Logger object or null to use the null logger.
     */

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * Set the logger for this object.
     *
     * @param LoggerInterface $logger A Monolog Logger class or NULL to use the null logger
     *
     * @return Loggable This object for method chaining.
     */

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = ( null === $logger ? Log::singleton('null') : $logger );
        return $this;
    }

    /**
     * Set the logger for this object.
     *
     * @return LoggerInterface This object for method chaining.
     */

    public function getLogger()
    {
        return $this->logger;
    }

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
    }

    /**
     * Helper function to log errors in a consistent format and provide a mechanism to
     * supply additional, optional, parameters for greater detail.
     *
     * @param $message An human-readable error message
     * @param $options An optional array of additional details. Information will typically
     *   be added to the exception message as well as the log file. Currently supported
     *   options are:
     *   'log_level' => Override the default \CCR\Log::ERR with another log level
     *   'exception' => An exception that was caught allowing us to include the message and
     *                  stack trace
     *   'sql' => SQL query being executed when the error was caught
     *   'endpoint' => DataEndpoint being used when error was caught
     *
     * @throws Exception
     */

    public function logAndThrowException($message, array $options = null)
    {
        $logMessage = array();
        $message = "{$this}: " . ( is_string($message) ? $message : "" );
        $logLevel = Log::ERR;
        $exceptionProvided = false;
        $code = 0;

        if ( null !== $options ) {

            if ( array_key_exists('exception', $options) && $options['exception'] instanceof Exception ) {
                // Don't add the exception message if it is the same as the general message
                $exceptionMessage = $options['exception']->getMessage();
                if ( $message != $exceptionMessage ) {
                    $message .= sprintf(" Exception: '%s'", $exceptionMessage);
                }

                // PDOException uses a string exception code (typically a five characters alphanumeric
                // identifier defined in the ANSI SQL standard) while Exception uses an int. Use the
                // driver specific error code instead so that we can propagate the error code with
                // the newly thrown Exception.
                // See: https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html

                if ( $options['exception'] instanceof PDOException ) {
                    $code = $options['exception']->errorInfo[1];
                } else {
                    $code = $options['exception']->getCode();
                }

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
                $logLevel = $options['log_level'];
            }
        }

        $logMessage['message'] = $message;

        $this->logger->log(Log::convertToMonologLevel($logLevel), LogOutput::from($logMessage));
        throw new Exception($message, $code);

    }

    /**
     * Generate a string representation of this class.
     *
     * @return A string representation of the endpoint
     */

    public function __toString()
    {
        return "(" . get_class($this) . ")";
    }

    /**
     * Prepare the class for serialize()
     */

    public function __sleep()
    {
        // Do not allow serialization of the logger as it may contain a PDO resource, which cannot
        // be serialized.
        $vars = get_object_vars($this);
        unset($vars['logger']);
        return array_keys($vars);
    }

    /**
     * Set up the class when unserialize() is called.
     */

    public function __wakeup()
    {
        //  On unserialize() the logger is expected the be a Monolog Logger object so be sure to
        //  re-initialize it.
        $this->setLogger(null);
    }
}  // class Loggable
