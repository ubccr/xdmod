<?php

namespace CCR;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use xd_utilities;

/**
 * This log class creates console, file, db and mail loggers and returns
 * a composite with default configuration from the settings file.  It
 * also registers a shutdown function and logs fatal errors.
 */
class Log
{

    const LINE_FORMAT = "%datetime% [%level_name%] %message%\n";
    const TIME_FORMAT = 'Y-m-d H:i:s';

    const EMERG   = 0;
    const ALERT   = 1;
    const CRIT    = 2;
    const ERR     = 3;
    const WARNING = 4;
    const NOTICE  = 5;
    const INFO    = 6;
    const DEBUG   = 7;

    private static $logLevels = array(
        self::EMERG => \Monolog\Logger::EMERGENCY,
        self::ALERT => \Monolog\Logger::ALERT,
        self::CRIT => \Monolog\Logger::CRITICAL,
        self::ERR => \Monolog\Logger::ERROR,
        self::WARNING => \Monolog\Logger::WARNING,
        self::NOTICE => \Monolog\Logger::NOTICE,
        self::INFO => \Monolog\Logger::INFO,
        self::DEBUG => \Monolog\Logger::DEBUG
    );

    private static $flippedLogLevels = array(
        \Monolog\Logger::EMERGENCY => self::EMERG,
        \Monolog\Logger::ALERT => self::ALERT,
        \Monolog\Logger::CRITICAL => self::CRIT,
        \Monolog\Logger::ERROR => self::ERR,
        \Monolog\Logger::WARNING => self::WARNING,
        \Monolog\Logger::NOTICE => self::NOTICE,
        \Monolog\Logger::INFO => self::INFO,
        \Monolog\Logger::DEBUG => self::DEBUG
    );

    /**
     * Holds the loggers instantiated as singletons.
     *
     * @var array => [ <string> => <LoggerInterface>]
     */
    private static $loggers = array();

    /**
     * Private constructor for factory pattern.
     */
    private function __construct()
    {
    }

    /**
     * Factory method.
     *
     * @param string $ident Log identifier
     * @param array $conf Configuration array.
     *   Uses the following keys:
     *   - lineFormat      => Line format for console, file and mail
     *                        logger.
     *   - timeFormat      => Time format for console, file and mail
     *                        logger.
     *   - console         => False for no console logging.
     *   - file            => File name for file logger or false for no
     *                        file logging.
     *   - mode            => File permissions mode (default 0660).
     *   - dirmode         => Directory permissions mode (default 0770).
     *   - db              => False for no database logging.
     *   - mail            => False for no email logging.
     *   - emailTo         => Mail logger recipient.
     *   - emailFrom       => Mail logger from.
     *   - emailSubject    => Mail logger subject
     *   - consoleLogLevel => Console logger log level.
     *   - fileLogLevel    => File logger log level.
     *   - dbLogLevel      => DB Logger log level.
     *   - mailLogLevel    => Mail logger log level.
     *
     * @return LoggerInterface
     * @throws Exception @see getLogger()
     */
    public static function factory(
        $ident = 'xdmod-logger',
        array $conf = array()
    ) {
        $conf['lineFormat'] = $conf['lineFormat'] ?? self::LINE_FORMAT;
        $conf['timeFormat'] = $conf['timeFormat'] ?? self::TIME_FORMAT;

        $logger = self::getLogger($ident, $conf);

        // Catch fatal errors and log them.
        register_shutdown_function(function () use ($logger) {
            $e = error_get_last();

            $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
                | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;

            if ($e !== null && ($e['type'] & $mask) == 0) {
                $logger->critical(LogOutput::from(
                    array(
                        'message' => $e['message'],
                        'file'    => $e['file'],
                        'line'    => $e['line'],
                        'type'    => $e['type'],
                    )
                ));
            }

            $logger->close();
        });

        return $logger;
    }

    /**
     * Attempt to retrieve a Logger for the provided $ident utilizing a static singleton pattern.
     *
     * @param string $ident         The string used to identify the requested logger uniquely.
     * @param array|array[] $config The configuration array to be used if a logger needs to be instantiated.
     * @return LoggerInterface      By default this returns a Monolog\Logger.
     *
     * @throws Exception            If there is a problem instantiating the requested log handlers.
     */
    public static function singleton($ident, array $config = array('null' => array()))
    {
        if (!array_key_exists($ident, self::$loggers)) {
            self::$loggers[$ident] = self::factory($ident, $config);
        }

        return self::$loggers[$ident];
    }

    /**
     * Retrieve a Logger w/ a handler for each type specified in $conf.
     *
     * @param string $ident    The unique string identifier for this logger.
     * @param array $conf      The configuration options to be used when instantiating this loggers handlers.
     *
     * @return LoggerInterface By default, this returns a Monolog\Logger.
     *
     * @throws Exception       If there are any problems w/ instantiating the requested handlers.
     */
    protected static function getLogger($ident, array $conf)
    {
        $loggerTypes = array(
            'console',
            'file',
            'db',
            'mail'
        );

        $logger = new Logger($ident);

        // Short circuit the function if 'null' was asked for since this will be the only handler for the logger.
        if ($ident === 'null') {
            $logger->pushHandler(new NullHandler());
            return $logger;
        }

        foreach ($loggerTypes as $type) {

            // Skip logger types that have been disabled.
            if (isset($conf[$type]) && $conf[$type] === false) {
                continue;
            }

            $loggerAccessor = 'get' . ucfirst($type) . 'Handler';
            $handler = call_user_func(array(get_called_class(), $loggerAccessor), $ident, $conf);

            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Retrieve a new StreamHandler configured to output to stdout.
     *
     * This function utilizes the following $conf keys:
     *   - consoleLogLevel: The log level at which this handler will produce a log entry. If none is provided then value
     *                      of the 'default_level_console' property in the logger section of portal_settings.ini will be used.
     *   - lineFormat:      The line format to be used when this handler writes a log entry.
     *   - timeFormat:      The time format to be used when this handler writes a log entry.
     *
     * @param string $ident The unique string identifier for this handler's logger.
     * @param array  $conf  The configuration to be used when constructing this handler.
     *
     * @return HandlerInterface
     *
     * @throws Exception if a StreamHandler to `php://stdout` cannot be instantiated.
     */
    protected static function getConsoleHandler($ident, array $conf)
    {
        $consoleLogLevel = $conf['consoleLogLevel'] ?? self::getDefaultLogLevel('console');

        $handler = new StreamHandler('php://stdout', self::convertToMonologLevel($consoleLogLevel));
        $handler->setFormatter(new CCRLineFormatter($conf['lineFormat'], $conf['timeFormat'], true));

        return $handler;
    }

    /**
     * Retrieve a new StreamHandler configured to write to a file.
     *
     * This function utilizes the following $conf keys:
     *   - fileLogLevel: The log level at which this handler will generate an entry.
     *   - file:         The file that log entries are to be written to.
     *   - mode:         The file permissions that the log file should be created with.
     *   - lineFormat:      The line format to be used when this handler writes a log entry.
     *   - timeFormat:      The time format to be used when this handler writes a log entry.
     *
     * @param string $ident The unique string identifier for this handlers Logger.
     * @param array  $conf  The configuration to be used when constructing this handler.
     *
     * @return HandlerInterface
     *
     * @throws Exception If there is a problem instantiating the StreamHandler to the requested file.
     */
    protected static function getFileHandler($ident, array $conf)
    {
        $fileLogLevel = $conf['fileLogLevel'] ?? self::getDefaultLogLevel('file');
        $file = $conf['file'] ?? LOG_DIR . '/' . strtolower(preg_replace('/\W/', '_', $ident)) . '.log';
        $filePermission = $conf['mode'] ?? 0660;

        $handler = new StreamHandler($file, self::convertToMonologLevel($fileLogLevel), true, $filePermission);
        $handler->setFormatter(new CCRLineFormatter($conf['lineFormat'], $conf['timeFormat']));

        return $handler;
    }

    /**
     * Retrieve a concrete implementation of Monolog's HandlerInterface ( by default, \CCR\CCRDBHandler ) that will
     * write log entries to a database.
     *
     * This function utilizes the following $conf keys:
     *   - dbLogLevel: The log level at which this handler will generate an entry.
     *
     * @param string $ident The unique string identifier for this handlers Logger.
     * @param array  $conf  The configuration to be used when constructing this handler.
     *
     * @return HandlerInterface
     *
     * @throws Exception @see CCRDBHandler::__construct
     */
    protected static function getDbHandler($ident, array $conf)
    {
        $dbLogLevel = $conf['dbLogLevel'] ?? self::getDefaultLogLevel('db');

        $handler = new CCRDBHandler(null, null, null, self::convertToMonologLevel($dbLogLevel));

        // This is one of the important changes to support the new version of Monolog, by setting the `format` to use
        // the `%formatted%` variable that is populated by the processor we're adding to the handler we can ensure that
        // we don't change the "normal" format and still get the json formatted information we expect to be logged to
        // the db.
        $handler->setFormatter(new LineFormatter('%formatted%'));

        // This processor checks to see if a value has been set in the log records `extra` section called `message`.
        // This key is populated by `CCR\Logger` and is the unchanged `$message` being logged. This let's us have access
        // to the "raw" $message that was passed to the logger.
        $processor = function ($record) {
            $extraMessage = $record['extra']['message'] ?? null;

            // Make sure that the contents of $record['formatted'] is json formatted for DBHandlers.
            if ($extraMessage instanceof LogOutput) {
                $record['formatted'] = json_encode($extraMessage->data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_array($extraMessage)) {
                $record['formatted'] = json_encode($extraMessage, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $record['formatted'] = json_encode(['message' => $record['message']], JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return $record;
        };
        $handler->pushProcessor($processor);

        return $handler;
    }

    /**
     * Retrieve a Monolog NativeMailHandler to facilitate logging directly to an email.
     *
     * This function utilizes the following $conf keys:
     *   - mailLogLevel:   The log level at which this handler will generate an email.
     *   - emailFrom:      The value to be used as the 'from' field.
     *   - emailTo:        The value to be used as the 'to'  field.
     *   - emailSubject:   The value to be used as the 'subject' field.
     *   - maxColumnWidth: The maximum column width that the message lines will have.
     *
     * @param string $ident The unique string identifier for this handlers Logger.
     * @param array  $conf  The configuration to be used when constructing this handler.
     *
     * @return HandlerInterface
     *
     * @throws Exception @see self::getConfiguration()
     */
    protected static function getMailHandler($ident, array $conf)
    {
        $mailLogLevel = $conf['mailLogLevel'] ?? self::getDefaultLogLevel('mail');
        $from = $conf['emailFrom'] ?? self::getConfiguration('email_from');
        $to = $conf['emailTo'] ?? self::getConfiguration('email_to');
        $subject = $conf['emailSubject'] ?? self::getConfiguration('email_subject');
        $maxColumnWidth = array_key_exists('maxColumnWidth', $conf) ? $conf['maxColumnWidth'] : 70;

        return new NativeMailerHandler($to, $subject, $from, self::convertToMonologLevel($mailLogLevel), true, $maxColumnWidth);
    }

    /**
     * Retrieves the 'default_level_$logType' property from portal_settings.ini and then returns the class constant that
     * corresponds this value. If the property is not found, or there is not a corresponding class constant, then
     * self::WARNING will be returned.
     *
     * i.e.
     * $logType = 'console';
     *
     * portal_settings.ini
     * ```ini
     * [logger]
     * default_level_console = "NOTICE"
     * ```
     *
     * returns self::NOTICE;
     *
     * @param string $logType The log handler type to be used when retrieving the default log level.
     * @return int that corresponds w/ this class' constants. i.e. EMERG, ALERT, CRIT, ERR, WARNING, NOTICE, INFO, DEBUG
     */
    protected static function getDefaultLogLevel($logType)
    {
        $option = 'default_level_' . $logType;

        try {
            $levelName = self::getConfiguration($option);
            $level = constant(get_class() . '::' . strtoupper($levelName));
        } catch (Exception $e) {
            $level = self::WARNING;
        }

        return $level;
    }

    /**
     * Convert a \Monolog\Logger log level value to a CCR\Log log level.
     *
     * @param int $monologLevel the Monolog log level to be converted to a CCR log level.
     * @return int the CCR log level that corresponds to the provided $monologLevel.
     * @throws Exception if the provided $monologLevel is not found.
     */
    public static function convertToCCRLevel($monologLevel)
    {
        if (array_key_exists($monologLevel, self::$flippedLogLevels)) {
            return self::$flippedLogLevels[$monologLevel];
        }
        throw new Exception('Unknown Log Level');
    }

    /**
     * Convert a \CCR\Log log level value to a \Monolog\Logger log level.
     *
     * @param int $ccrLevel
     * @return int the Monolog log level that corresponds to the provided $ccrLevel
     * @throws Exception if the provided $ccrlLevel is not found.
     */
    public static function convertToMonologLevel($ccrLevel)
    {
        if (array_key_exists($ccrLevel, self::$logLevels)) {
            return self::$logLevels[$ccrLevel];
        }
        throw new Exception('Unknown Log Level');
    }

    /**
     * Retrieves the specified $option from the logger section of portal_settings.ini
     *
     * @param string $option The option to be retrieved from portal_settings.ini
     * @return mixed         The value of $option in the logger section of portal_settings.ini.
     *
     * @throws Exception    If $option is not found in the logger section.
     * @throws Exception    If the value of $option is empty.
     */
    protected static function getConfiguration($option)
    {
        return xd_utilities\getConfiguration('logger', $option);
    }
}
