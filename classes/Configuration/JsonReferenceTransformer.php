<?php
/** =========================================================================================
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

class JsonReferenceTransformer extends aUrlTransformer implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref';

    /** -----------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }  // keyMatches()

    /** -----------------------------------------------------------------------------------------
     * Transform the JSON pointer into the actual JSON that it references.
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

        $parsedUrl = null;
        $contents = $this->getContentsFromUrl($value, $config);
        $fragment = ( array_key_exists('fragment', $this->parsedUrl) ? $this->parsedUrl['fragment'] : '' );
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
}  // class JsonReferenceTransformer
