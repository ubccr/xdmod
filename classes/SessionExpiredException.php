<?php

/**
 * Exception thrown when a user's session has expired. Handled specially by the
 * global exception handler to provide a consistent response when a session
 * expires.
 */
class SessionExpiredException extends UserException
{
	/**
	 * The message used by this exception if none is provided.
	 */
	const defaultMessage = 'Session Expired';

	/**
     * The code used by this exception if none is provided.
     */
	const defaultCode = XDError::SessionExpired;

    public function __construct($message = self::defaultMessage, $code = self::defaultCode, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$site_address = \xd_utilities\getConfigurationUrlBase('general', 'site_address');

		$this->httpCode = 401;
		$this->headers['WWW-Authenticate'] = "XDMoDAuth realm=\"$site_address\"";
	}
}

?>
