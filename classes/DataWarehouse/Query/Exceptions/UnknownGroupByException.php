<?php

namespace DataWarehouse\Query\Exceptions;

/**
 * Exception thrown when an unknown group by is specified.
 */
class UnknownGroupByException extends QueryException
{
    /**
     * The message used by this exception if none is provided.
     */
    const defaultMessage = 'Query: Unknown Group By Specified';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = \XDError::QueryUnknownGroupBy;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->httpCode = 400;
    }
}
