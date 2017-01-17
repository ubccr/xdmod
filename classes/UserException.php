<?php

/**
 * Base class for user-related exceptions.
 */
abstract class UserException extends XDException
{
    /**
     * The message used by this exception if none is provided.
     */
    const defaultMessage = 'Unknown XDMoD Exception Occurred (User-Related)';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = XDError::UnknownXdmodException;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}//UserException
