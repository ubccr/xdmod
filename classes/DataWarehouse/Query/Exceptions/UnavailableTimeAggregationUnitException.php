<?php

namespace DataWarehouse\Query\Exceptions;

/**
 * Exception thrown when an unavailable time aggregation unit is specified.
 */
class UnavailableTimeAggregationUnitException extends QueryException
{
    /**
     * The message used by this exception if none is provided.
     */
    const defaultMessage = 'Query: Unavailable Time Aggregation Unit Specified';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = \XDError::QueryUnavailableTimeAggregationUnit;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->httpCode = 400;
    }
}
