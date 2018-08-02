<?php
/* ==========================================================================================
 * Evaluate JSON references (https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03) where
 * the `$ref` gets logically replaced with the thing that it points to. For example,
 * { "$ref": "http://example.com/example.json#/foo/bar" }
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-11
 * ==========================================================================================
 */

namespace Configuration;

// PEAR logger
use Log;
use stdClass;
use CCR\Loggable;
use ETL\JsonPointer;

class JsonReferenceTransformer extends Loggable implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref';

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null)
    {
        parent::__construct($logger);
    }  // construct()

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }  // keyMatches()

    /* ------------------------------------------------------------------------------------------
     * Comments remove both the key and the value from the configuration and stop processing of the
     * key.
     *
     * @see iConfigFileKeyTransformer::transform()
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config)
    {
        // JSON references (see https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-0)
        // constitute an entire object, for example:
        // { "$ref": "http://example.com/example.json#/foo/bar" }
        //
        // Because a reference is replaced with the entity that it points to, it must be
        // the ONLY key in an object. Otherwise, we may end up with a mix of objects,
        // scalars, and arrays in the same object which is not valid JSON.  For example,
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        "$ref": "etl_tables.d/jobs/job_task.json#/table_definition/columns",
        //    }
        // }
        //
        // might resolve to
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        [ 0, 1, 2 ]
        //    }
        // }
        //
        // or
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        {
        //            "name": "job_tasks",
        //            "engine": "MyISAM",
        //            "comment": "Consumption for resources by a user
        //        }
        //    }
        // }

        if( count(get_object_vars($obj)) != 1 ) {
            $this->logAndThrowException(
                sprintf('References cannot be mixed with other keys in an object: "%s": "%s"', $key, $value)
            );
        }

        $parsedUrl = parse_url($value);
        $path = $this->qualifyPath($parsedUrl['path'], $config);
        $this->logger->debug(
            sprintf("(%s) Resolve JSON reference '%s' to file '%s'", get_class($this), $value, $path)
        );

        $fragment = ( array_key_exists('fragment', $parsedUrl) ? $parsedUrl['fragment'] : '' );

        // If no scheme was provided, default to the file scheme. Also ensure that the
        // file path is properly formatted.

        $scheme = ( array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'file' );
        if ( 'file' == $scheme ) {
            $path = 'file://' . $path;
        }

        // Open the file and return the contents.

        $contents = @file_get_contents($path);
        if ( false === $contents ) {
            $this->logAndThrowException('Failed to open file: ' . $path);
        }

        $key = null;

        JsonPointer::setLoggable($this);
        $value = JsonPointer::extractFragment($contents, $fragment);
        JsonPointer::setLoggable(null);

        if ( false === $value ) {
            $this->logAndThrowException(
                sprintf("Error processing JSON pointer: %s", $fragment)
            );
            return false;
        }

        return true;

    }  // transform()

    /* ------------------------------------------------------------------------------------------
     * Qualify the path using the base directory from the configuration object if it is
     * not already fully qualified.
     *
     * @param string $path The path to qualify
     * @param Configuration $config $The configuration object that called the transformer
     *
     * @returns A fully qualified path
     * ------------------------------------------------------------------------------------------
     */

    protected function qualifyPath($path, Configuration $config)
    {
        $path = $config->getVariableStore()->substitute(
            $path,
            "Undefined macros in JSON reference"
        );
        return \xd_utilities\qualify_path($path, $config->getBaseDir());
    }
}  // class JsonReferenceTransformer
