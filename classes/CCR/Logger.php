<?php

namespace CCR;

use Exception;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Monolog\Logger as MLogger;

/**
 * This class is meant to provide a shim between Monolog & our code so that we can continue providing arrays as log
 * messages while still utilizing Monolog but this will only be the case for loggers retrieved from CCR\Log::factory|singleton
 * or if code instantiates this class directly.
 *
 * Note: This logger supports string and array "messages". If an array is provided that contains objects, then this class
 * will use the __toString() function to convert the object to a string.
 *
 * @package CCR
 */
class Logger extends MLogger implements LoggerInterface
{
    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     * @throws \DateInvalidTimeZoneException
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }

        $levelName = static::getLevelName($level);

        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        reset($this->handlers);
        while ($handler = current($this->handlers)) {
            if ($handler->isHandling(array('level' => $level))) {
                $handlerKey = key($this->handlers);
                break;
            }

            next($this->handlers);
        }

        if (null === $handlerKey) {
            return false;
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        // php7.1+ always has microseconds enabled, so we do not need this hack
        if ($this->microsecondTimestamps && PHP_VERSION_ID < 70100) {
            $ts = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone);
        } else {
            $ts = new \DateTime('now', static::$timezone);
        }
        $ts->setTimezone(static::$timezone);
        if (is_array($message)) {
            $message = LogOutput::from($message);
        }
        $record = array(
            'message' => (string) $message,
            'context' => $context,
            'level' => $level,
            'level_name' => strtolower($levelName),
            'channel' => $this->name,
            'datetime' => $ts,
            'extra' => array('message' => $message),
        );

        try {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }

            while ($handler = current($this->handlers)) {
                if (true === $handler->handle($record)) {
                    break;
                }

                next($this->handlers);
            }
        } catch (Exception $e) {
            $this->handleException($e, $record);
        }

        return true;
    }
}
