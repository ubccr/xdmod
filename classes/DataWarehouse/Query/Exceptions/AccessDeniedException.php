<?php

namespace DataWarehouse\Query\Exceptions;

/**
 * Exception thrown when an unknown group by is specified.
 */
class AccessDeniedException extends QueryException
{
	/**
	 * The message used by this exception if none is provided.
	 */
	const defaultMessage = 'The role to which you are assigned does not have access to the information you requested.';

	/**
     * The code used by this exception if none is provided.
     */
	const defaultCode = \XDError::QueryAccessDenied;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->httpCode = 403;
	}
}

?>
