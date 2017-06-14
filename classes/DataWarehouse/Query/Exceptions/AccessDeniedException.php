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
    const DEFAULT_MESSAGE = 'The role to which you are assigned does not have access to the information you requested.';

    /**
     * The code used by this exception if none is provided.
     */
    const DEFAULT_CODE = \XDError::QueryAccessDenied;

    public function __construct($message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->httpCode = 403;
    }
}
