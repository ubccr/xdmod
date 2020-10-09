<?php

namespace CCR;

use Monolog\Handler\MongoDBHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use \Psr\Log\InvalidArgumentException;

class Logging
{

    /**
     * Holds the
     *
     * @var array [ <string> => <LoggerInterface> ]
     */
    private static $loggers = array();

    /**
     * @param $name
     * @param array $config
     *
     * @return LoggerInterface
     *
     * @throws \Exception                If a missing directory is not buildable
     * @throws \InvalidArgumentException If `name` is not a resource or string
     */
    public static function factory($name, array $config = array('null' => array()))
    {
        $logger = new \Monolog\Logger($name);

        foreach ($config as $type => $typeConfig) {
            $bubble = array_key_exists('bubble', $typeConfig) ? $typeConfig['bubble'] : true;

            switch ($type) {
                case 'console':
                    $level = array_key_exists('level', $typeConfig)
                        ? $typeConfig['level']
                        : self::getLevel(\xd_utilities\getConfiguration('logger', 'default_level_console'), Logger::NOTICE);

                    $logger->pushHandler(new StreamHandler('php://stdout', $level, $bubble));
                    break;
                case 'file':
                    $level = array_key_exists('level', $typeConfig)
                        ? $typeConfig['level']
                        : self::getLevel(\xd_utilities\getConfiguration('logger', 'default_level_file'), Logger::WARNING);

                    $filePath = array_key_exists('file_path', $typeConfig) ? $typeConfig['file_path'] : implode(DIRECTORY_SEPARATOR, array(LOG_DIR, "$name.log"));
                    $filePermission = array_key_exists('file_permission', $typeConfig) ? $typeConfig['file_permission'] : null;
                    $useLocking = array_key_exists('use_locking', $typeConfig) ? $typeConfig['use_locking'] :  false;

                    $logger->pushHandler(new StreamHandler($filePath, $level, $bubble, $filePermission, $useLocking));
                    break;
                case 'email':
                    $level = array_key_exists('level', $typeConfig)
                        ? $typeConfig['level']
                        : self::getLevel(\xd_utilities\getConfiguration('logger', 'default_level_mail'), Logger::ERROR);

                    if (!isset($typeConfig['to'])) {
                        $typeConfig['to'] = \xd_utilities\getConfiguration('logger', 'email_from');
                    }
                    if (!isset($typeConfig['subject'])) {
                        $typeConfig['subject'] = \xd_utilities\getConfiguration('logger', 'email_subject');
                    }
                    if (!isset($typeConfig['from'])) {
                        $typeConfig['from'] = \xd_utilities\getConfiguration('logger', 'email_from');
                    }

                    $maxColumnWidth = array_key_exists('max_column_width', $typeConfig) ? $typeConfig['max_column_width'] : 70;

                    $to = $typeConfig['to'];
                    $subject = $typeConfig['subject'];
                    $from = $typeConfig['from'];

                    $logger->pushHandler(new NativeMailerHandler($to, $subject, $from, $level, $bubble, $maxColumnWidth));
                    break;
                case 'mysql':
                    $level = array_key_exists('level', $typeConfig)
                        ? $typeConfig['level']
                        : self::getLevel(\xd_utilities\getConfiguration('logger', 'default_level_db'), Logger::INFO);

                    $db = array_key_exists('db', $typeConfig) ? $typeConfig['db'] : null;
                    $schema = array_key_exists('schema', $typeConfig) ? $typeConfig['schema'] : null;
                    $table = array_key_exists('table', $typeConfig) ? $typeConfig['table'] : null;

                    $logger->pushHandler(new CCRDBHandler($db, $schema, $table, $level, $bubble));
                    break;
                case 'mongo':
                    $level = array_key_exists('level', $typeConfig)
                        ? $typeConfig['level']
                        : self::getLevel(\xd_utilities\getConfiguration('logger', 'default_level_db'), Logger::INFO);

                    if (!isset($typeConfig['client'])) {
                        throw new InvalidArgumentException("A mongodb log handler requires a client to be provided.");
                    }
                    if (!isset($typeConfig['database'])) {
                        throw new InvalidArgumentException("A mongodb log handler requires a database name be provided.");
                    }
                    if (!isset($typeConfig['collection'])) {
                        throw new InvalidArgumentException("A mongodb log handler requires a collection name be provided.");
                    }
                    $client = $typeConfig['client'];
                    $database = $typeConfig['database'];
                    $collection = $typeConfig['collection'];

                    $logger->pushHandler(new MongoDBHandler($client, $database, $collection, $level, $bubble));
                    break;
                case 'null':
                    $logger->pushHandler(new NullHandler());
                    break;
                default:
                    break;
            }
        }

        return $logger;
    }

    /**
     * @param string $name
     * @param array|array[] $config
     * @return LoggerInterface
     * @throws \Exception
     */
    public static function singleton($name, array $config = array('null' => array()))
    {
        if (!array_key_exists($name, self::$loggers)) {
            self::$loggers[$name] = self::factory($name, $config);
        }

        return self::$loggers[$name];
    }

    /**
     * @param string $name
     * @param int $defaultValue
     *
     * @return int
     */
    private static function getLevel($name, $defaultValue = Logger::DEBUG)
    {
        $levels = Logger::getLevels();
        foreach($levels as $levelName => $level) {
            if (strtolower($levelName) === strtolower($name)) {
                return $level;
            }
        }

        return $defaultValue;
    }
}
