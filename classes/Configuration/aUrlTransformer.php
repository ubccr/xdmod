<?php
/**
 * Abstract base class to encapsulate functionality common to transformers that operate on URLs,
 * such as a JSON pointer or file include.
 *
 */

namespace Configuration;

use Log;
use CCR\Loggable;

abstract class aUrlTransformer extends Loggable
{
    /** -----------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null)
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
     * @param array $parsedUrl Reference to a variable that will be populated with the value
     *   returned by parse_url()
     * @param Configuration $config The Configuration object that called this method
     *
     * @return The contents of the file referenced by the URL
     * @throws Exception if there was an error parsing the URL or accessing the file
     * ------------------------------------------------------------------------------------------
     */

    public function getContentsFromUrl($url, &$parsedUrl, Configuration $config)
    {
        $parsedUrl = parse_url($url);

        // Ensure the value contains a file path

        if ( empty($parsedUrl['path']) ) {
            $this->logAndThrowException(
                sprintf("(%s) Unable to extract path from URL: %s", get_class($this), $url)
            );
        }

        $path = $this->qualifyPath($parsedUrl['path'], $config);
        $this->logger->debug(
            sprintf("(%s) Resolved reference '%s' to '%s'", get_class($this), $parsedUrl['path'], $path)
        );

        // If no scheme was provided, default to the file scheme.

        $scheme = ( array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'file' );
        if ( 'file' == $scheme ) {
            $path = 'file://' . $path;
        }

        // Open the file and return the contents.

        $contents = @file_get_contents($path);
        if ( false === $contents ) {
            $error = error_get_last();
            $this->logAndThrowException(
                sprintf("Failed to open file '%s'%s", $path, (null !== $error ? ": " . $error['message'] : ""))
            );
        }

        return $contents;
    }
} // class aFilePathTransformer
