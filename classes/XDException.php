<?php

/**
 * Base exception class for all custom XDMoD exceptions.
 */
abstract class XDException extends \Exception
{
	/**
	 * The message used by this exception if none is provided.
	 */
    const defaultMessage = 'Unknown XDMoD Exception Occurred';

    /**
     * The code used by this exception if none is provided.
     */
    const defaultCode = XDError::UnknownXdmodException;

    /**
     * An array of data related to the exception that occurred to be used
     * by clients. This can be structured as needed on a per-exception basis,
     * but it should always contain data that can be broken down into
     * whatever output format the client requests (JSON, etc.).
     *
     * @var array
     */
    public $errorData = array();

    /**
     * An HTTP status code to use when this exception is thrown.
     *
     * To be a code that's usable by the global exception handler, there must
     * be a corresponding header message that can be found by the handler.
     * If one cannot be found, it will fall back to a generic 500 code.
     *
     * @var integer
     */
    public $httpCode = 500;

    /**
     * A set of headers to be used when this exception is thrown.
     *
     * Specifying the HTTP status code should be done through httpCode and not
     * here.
     *
     * Headers should be stored as key-value pairs in this array corresponding
     * to the header key and header value.
     *
     * @var array
     */
    public $headers = array();

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

?>
