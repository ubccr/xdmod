<?php

namespace CCR;

require_once 'Log.php';

use xd_utilities;

/**
 * This log class creates console, file, db and mail loggers and returns
 * a composite with default configuration from the settings file.  It
 * also registers a shutdown function and logs fatal errors.
 */
class Log
{

    // Class constants so "Log.php" doesn't need to be required by users
    // of the Log class.
    const EMERG   = PEAR_LOG_EMERG;
    const ALERT   = PEAR_LOG_ALERT;
    const CRIT    = PEAR_LOG_CRIT;
    const ERR     = PEAR_LOG_ERR;
    const WARNING = PEAR_LOG_WARNING;
    const NOTICE  = PEAR_LOG_NOTICE;
    const INFO    = PEAR_LOG_INFO;
    const DEBUG   = PEAR_LOG_DEBUG;

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
     * @return Log
     */
    public static function factory(
        $ident = 'xdmod-logger',
        array $conf = array()
    ) {
        $conf['lineFormat']
            = isset($conf['lineFormat'])
            ? $conf['lineFormat']
            : '%{timestamp} [%{priority}] %{message}';

        $conf['timeFormat']
            = isset($conf['timeFormat'])
            ? $conf['timeFormat']
            :'%Y-%m-%d %H:%M:%S';

        $loggers = self::getLoggers($ident, $conf);

        $logger = \Log::singleton('composite');

        foreach ($loggers as $childLogger) {
            $logger->addChild($childLogger);

            // Unset variable to work around bug in some versions of the
            // PEAR Log class that store a reference.
            unset($childLogger);
        }

        // Catch fatal errors and log them.
        register_shutdown_function(function () use ($logger) {
            $e = error_get_last();

            $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
                | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;

            if ($e !== NULL && ($e['type'] & $mask) == 0) {
                $logger->crit(array(
                    'message' => $e['message'],
                    'file'    => $e['file'],
                    'line'    => $e['line'],
                    'type'    => $e['type'],
                ));
            }

            $logger->close();
        });

        return $logger;
    }

    protected static function getLoggers($ident, array $conf)
    {
        $loggerTypes = array(
            'console',
            'file',
            'db',
            'mail',
        );

        $loggers = array();

        foreach ($loggerTypes as $type) {

            // Skip logger types that have been disabled.
            if (isset($conf[$type]) && $conf[$type] === false) {
                continue;
            }

            $loggerAccessor = 'get' . ucfirst($type) . 'Logger';

            $loggers[] = call_user_func(
                array(get_called_class(), $loggerAccessor),
                $ident,
                $conf
            );
        }

        return $loggers;
    }

    protected static function getConsoleLogger($ident, array $conf)
    {
        $consoleLogLevel
            = isset($conf['consoleLogLevel'])
            ? $conf['consoleLogLevel']
            : self::getDefaultLogLevel('console');

        $consoleConf = array(
            'lineFormat' => $conf['lineFormat'],
            'timeFormat' => $conf['timeFormat'],
        );

        $consoleLogger = \Log::factory(
            'xdconsole',
            '',
            $ident,
            $consoleConf,
            $consoleLogLevel
        );

        return $consoleLogger;
    }

    protected static function getFileLogger($ident, array $conf)
    {
        $fileLogLevel
            = isset($conf['fileLogLevel'])
            ? $conf['fileLogLevel']
            : self::getDefaultLogLevel('file');

        $conf['file']
            = isset($conf['file'])
            ? $conf['file']
            : LOG_DIR . '/' . strtolower(preg_replace('/\W/', '_', $ident))
            . '.log';

        $fileConf = array(
            'append'     => true,
            'mode'       => 0644,
            'lineFormat' => $conf['lineFormat'],
            'timeFormat' => $conf['timeFormat'],
        );

        $fileLogger = \Log::factory(
            'xdfile',
            $conf['file'],
            $ident,
            $fileConf,
            $fileLogLevel
        );

        return $fileLogger;
    }

    protected static function getDbLogger($ident, array $conf)
    {
        $dbLogLevel
            = isset($conf['dbLogLevel'])
            ? $conf['dbLogLevel']
            : self::getDefaultLogLevel('db');

        $dbHost  = self::getConfiguration('host');
        $dbPort  = self::getConfiguration('port');
        $dbUser  = self::getConfiguration('user');
        $dbPass  = self::getConfiguration('pass');
        $dbName  = self::getConfiguration('database');
        $dbTable = self::getConfiguration('table');

        $dbConf = array(
            'dsn' => "mysql://$dbUser:$dbPass@$dbHost:$dbPort/$dbName",
            'identLimit' => 32,
        );

        $dbLogger = \Log::factory(
            'xdmdb2',
            $dbTable,
            $ident,
            $dbConf,
            $dbLogLevel
        );

        return $dbLogger;
    }

    protected static function getMailLogger($ident, array $conf)
    {
        $mailLogLevel
            = isset($conf['mailLogLevel'])
            ? $conf['mailLogLevel']
            : self::getDefaultLogLevel('mail');

        $conf['emailFrom']
            = isset($conf['emailFrom'])
            ? $conf['emailFrom']
            : self::getConfiguration('email_from');

        $conf['emailTo']
            = isset($conf['emailTo'])
            ? $conf['emailTo']
            : self::getConfiguration('email_to');

        $conf['emailSubject']
            = isset($conf['emailSubject'])
            ? $conf['emailSubject']
            : self::getConfiguration('email_subject');

        $mailConf = array(
            'subject'    => $conf['emailSubject'],
            'from'       => $conf['emailFrom'],
            'lineFormat' => $conf['lineFormat'],
            'timeFormat' => $conf['timeFormat'],
        );

        $mailLogger = \Log::factory(
            'xdmailer',
            $conf['emailTo'],
            $ident,
            $mailConf,
            $mailLogLevel
        );

        return $mailLogger;
    }

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

    protected static function getConfiguration($option)
    {
        return xd_utilities\getConfiguration('logger', $option);
    }
}

