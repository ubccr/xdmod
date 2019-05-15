<?php

namespace DataWarehouse\Query\Exceptions;

/**
 * Base exception class for query-related exceptions.
 */
abstract class QueryException extends \XDException
{
    /**
     * The message used by this exception if none is provided.
     */
    const defaultMessage = 'Query: Unknown Problem';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = \XDError::QueryException;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
