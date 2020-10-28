<?php

namespace CCR;

use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements  LoggerInterface {

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
