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
}
