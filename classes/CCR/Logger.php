<?php

namespace CCR;

use Psr\Log\LoggerInterface;

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
class Logger extends \Monolog\Logger implements LoggerInterface
{

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array()): void
    {
        // This is so that when code calls $logger->log(\CCR\Log::DEBUG, "Message"); it doesn't bork.
        if ($level < \Monolog\Logger::DEBUG) {
            $level = Log::convertToMonologLevel($level);
        }

        parent::log($level, $this->extractMessage($message), $context);
    }

    /**
     * @inheritDoc
     */
    public function addRecord(int $level, string $message, array $context = array(), \MonoLog\DateTimeImmutable $datetime = null): bool
    {
        return parent::addRecord($level, $this->extractMessage($message), $context);
    }

    /**
     * This function was extracted from the class `\Log\Log_xdconsole` so that we can keep our log output the same.
     *
     * @param mixed $record
     *
     * @return string
     */
    protected function extractMessage($record)
    {
        if (is_array($record)) {
            return json_encode($this->recursivelyStringifyObjects($record), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        }
        return json_encode(array('message' => $record), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }

    /**
     * This function recursively iterates over the provided $array keys and values. If a value is an object it replaces
     * the object with it's cast string value.
     *
     * @param array $array The array to be recursively iterated over
     *
     * @return array returns the provided $array w/ any object values cast to strings.
     */
    protected function recursivelyStringifyObjects(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = (string)$value;
            } elseif (is_array($value)) {
                $array[$key] = $this->recursivelyStringifyObjects($value);
            }
        }
        return $array;
    }

    public function emergency($message, array $context = array()): void
    {
        parent::emergency($this->extractMessage($message), $context);
    }

    public function emerg($message, array $context = array()): void
    {
        parent::emergency($this->extractMessage($message), $context);
    }

    public function alert($message, array $context = array()): void
    {
        parent::alert($this->extractMessage($message), $context);
    }

    public function critical($message, array $context = array()): void
    {
        parent::critical($this->extractMessage($message), $context);
    }

    public function crit($message, array $context = array()): void
    {
        parent::critical($this->extractMessage($message), $context);
    }

    public function error($message, array $context = array()): void
    {
        parent::error($this->extractMessage($message), $context);
    }

    public function err($message, array $context = array()): void
    {
        parent::error($this->extractMessage($message), $context);
    }

    public function warning($message, array $context = array()): void
    {
        parent::warning($this->extractMessage($message), $context);
    }

    public function notice($message, array $context = array()): void
    {
        parent::notice($this->extractMessage($message), $context);
    }

    public function info($message, array $context = array()): void
    {
        parent::info($this->extractMessage($message), $context);
    }

    public function debug($message, array $context = array()): void
    {
        parent::debug($this->extractMessage($message), $context);
    }
}
