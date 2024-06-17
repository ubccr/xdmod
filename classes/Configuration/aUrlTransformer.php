<?php
/**
 * Abstract base class to encapsulate functionality common to transformers that operate on URLs,
 * such as a JSON pointer or file include.
 *
 */

namespace Configuration;

use CCR\Log;
use CCR\Loggable;
use Psr\Log\LoggerInterface;

abstract class aUrlTransformer extends Loggable
{
    /**
     * @var array $parsedUrl
     *
     * The results of the url parsed by parse_url()
     */

    protected $parsedUrl = null;

    /** -----------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /** -----------------------------------------------------------------------------------------
     * Qualify the path using the base directory from the configuration object if it is
     * not already fully qualified.
     *
     * @param string $path The path to qualify
     * @param Configuration $config $The configuration object that called the transformer
     *
     * @return A fully qualified path
     * ------------------------------------------------------------------------------------------
     */

    protected function qualifyPath($path, Configuration $config)
    {
        $path = $config->getVariableStore()->substitute(
            $path,
            "Undefined macros in file reference"
        );
        return \xd_utilities\qualify_path($path, $config->getBaseDir());
    }

    /** -----------------------------------------------------------------------------------------
     * For transformers that support a value that is a URL perform the following steps:
     * - Validate the URL
     * - Qualify the path to resolve any configuration variables that are used
     * - Extract the contents of the file
     *
     * Note: This method specifically supports file URLs.
     *
     * @param string $url The URL to process
     * @param Configuration $config The Configuration object that called this method
     * @param $exceptionLogLevel The level to use for logging any exceptions
     *
     * @return The contents of the file referenced by the URL
     * @throws Exception if there was an error parsing the URL or accessing the file
     * ------------------------------------------------------------------------------------------
     */

    public function getContentsFromUrl($url, Configuration $config, $exceptionLogLevel)
    {

        $url = $config->getVariableStore()->substitute(
            $url,
            "Undefined macros in URL reference"
        );

        $this->parsedUrl = parse_url($url);
        // We need to process variables on the url BEFORE parsing the URL...

        // Ensure the value contains a file path

        if ( empty($this->parsedUrl['path']) ) {
            $this->logAndThrowException(
                sprintf("(%s) Unable to extract path from URL: %s", get_class($this), $url),
                array('log_level' => $exceptionLogLevel)
            );
        }

        $path = \xd_utilities\qualify_path($this->parsedUrl['path'], $config->getBaseDir());

        // $path = $this->qualifyPath($this->parsedUrl['path'], $config);
        $this->logger->debug(
            sprintf("(%s) Resolved reference '%s' to '%s'", get_class($this), $this->parsedUrl['path'], $path)
        );

        // If no scheme was provided, default to the file scheme.

        $scheme = ( array_key_exists('scheme', $this->parsedUrl) ? $this->parsedUrl['scheme'] : 'file' );
        if ( 'file' == $scheme ) {
            $path = 'file://' . $path;
        }

        // Open the file and return the contents.

        $contents = @file_get_contents($path);
        if ( false === $contents ) {
            $error = error_get_last();
            $this->logAndThrowException(
                sprintf("Failed to open file '%s'%s", $path, (null !== $error ? ": " . $error['message'] : "")),
                array('log_level' => $exceptionLogLevel)
            );
        }

        return $contents;
    }
} // class aUrlTransformer
