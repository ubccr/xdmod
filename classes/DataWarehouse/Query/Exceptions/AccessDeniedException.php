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
    const DEFAULT_MESSAGE = <<<EOF
Your user account does not have permission to view the requested data.  If you
believe that you should be able to see this information, then please select
"Submit Support Request" in the "Contact Us" menu to request access.
EOF;

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
