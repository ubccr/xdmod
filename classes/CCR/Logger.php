<?php

namespace CCR;

use Psr\Log\LoggerInterface;

/**
 * This class is meant to provide a shim between Monolog & our code so that we can continue providing arrays as log
 * messages while still utilizing Monolog but this will only be the case for loggers retrieved from CCR\Log::factory|singleton
 * or if code instantiates this class directly.
 *
 * @package CCR
 */
class Logger extends \Monolog\Logger implements LoggerInterface
{

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        return parent::log($level, $this->extractMessage($message), $context);
    }

    /**
     * @inheritDoc
     */
    public function addRecord($level, $message, array $context = array())
    {
        return parent::addRecord($level, $this->extractMessage($message), $context);
    }

    /**
     * This function was extracted from the class `\Log\Log_xdconsole` so that we can keep our log output the same.
     *
     * @param mixed $message
     *
     * @return string
     */
    protected function extractMessage($message)
    {
        if (is_array($message))
        {
            $parts = array();

            if (isset($message['message'])) {
                $parts[] = $message['message'];
                unset($message['message']);
            }

            if (count($message) > 0) {
                $nonMessageParts = array();

                while (list($key, $value) = each($message)) {
                    $nonMessageParts[] = "$key: $value";
                }

                $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
            }

            return implode(' ', $parts);
        }

        /* Otherwise, we assume the message is a string. */
        return $message;
    }
}