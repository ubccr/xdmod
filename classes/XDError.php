<?php

/**
 * Provides definitions for all XDMoD error codes.
 */
abstract class XDError
{
	public const UnknownXdmodException = -1;

	public const NotAuthenticated = 1;
	public const SessionExpired = 2;

	public const QueryException = 100;
	public const QueryUnknownGroupBy = 101;
	public const QueryUnavailableTimeAggregationUnit = 102;
	public const QueryAccessDenied = 103;
	public const QueryMissingFilterListTable = 106;

	/**
	 * Get a mapping of error names to error codes by reflecting the constants
	 * defined by this class.
	 * 
	 * Returns:
	 *     An associative array mapping error names to error codes.
	 */
	public static function getErrorCodes() {
		$reflection = new ReflectionClass(self::class);
		return $reflection->getConstants();
	}
}
