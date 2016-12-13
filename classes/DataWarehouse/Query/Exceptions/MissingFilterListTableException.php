<?php

namespace DataWarehouse\Query\Exceptions;

/**
 * Exception thrown when a filter list table is missing.
 */
class MissingFilterListTableException extends QueryException
{
    /**
     * The message used by this exception if none is provided.
     */
    const defaultMessage = 'Filter data could not be found. If data has not been ingested and aggregated yet, this is to be expected. If Open XDMoD has been upgraded from a version before 5.6.0, please see the upgrade documentation.';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = \XDError::QueryMissingFilterListTable;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
