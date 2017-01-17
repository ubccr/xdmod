<?php

/**
 * Provides definitions for all XDMoD error codes.
 */
abstract class XDError
{
    const UnknownXdmodException = -1;

    const NotAuthenticated = 1;
    const SessionExpired = 2;

    const QueryException = 100;
    const QueryUnknownGroupBy = 101;
    const QueryUnavailableTimeAggregationUnit = 102;
    const QueryAccessDenied = 103;
    const QueryBadRequest = 104;
    const QueryNotFound = 105;
    const QueryMissingFilterListTable = 106;

    /**
     * Get a mapping of error names to error codes by reflecting the constants
     * defined by this class.
     *
     * Returns:
     *     An associative array mapping error names to error codes.
     */
    public static function getErrorCodes()
    {
        $reflection = new ReflectionClass(__CLASS__);
        return $reflection->getConstants();
    }
}
