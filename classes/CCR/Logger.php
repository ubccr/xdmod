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

    public function log($level, $message, array $context = array())
    {
        $message = !is_string($message) ? json_encode($message) : $message;

        return parent::log($level, $message, $context);
    }

    public function addRecord($level, $message, array $context = array())
    {
        $message = !is_string($message) ? json_encode($message) : $message;

        return parent::addRecord($level, $message, $context);
    }
}
