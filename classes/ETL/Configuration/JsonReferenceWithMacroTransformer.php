<?php
/* ==========================================================================================
 * Evaluate JSON references (https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03) where
 * the `$ref` gets logically replaced with the thing that it points to. Support path macros
 * in the reference value.  For example,
 *
 * { "$ref": "${definition_file_dir}/jobs/job_records.json" }
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-11
 * ==========================================================================================
 */

namespace ETL\Configuration;

class JsonReferenceWithMacroTransformer extends JsonReferenceTransformer implements iConfigFileKeyTransformer
{
    /* ------------------------------------------------------------------------------------------
     * Qualify the path using the base directory from the configuration object if it is
     * not already fully qualified. Also support macros based on keys present in the
     * "paths" object.
     *
     * @param string $path The path to qualify
     * @param Configuration $config $The configuration object that called the transformer
     *
     * @returns A fully qualified path
     * ------------------------------------------------------------------------------------------
     */

    protected function qualifyPath($path, EtlConfiguration $config)
    {
        $paths = $config->getPaths();

        if ( null !== $paths ) {
            $variableMap = array();
            foreach ( $paths as $var => $value ) {
                $variableMap[$var] = $value;
            }
            $path = Utilities::substituteVariables($path, $variableMap, $this);
        }

        return \xd_utilities\qualify_path($path, $config->getBaseDir());
    }  // qualifyPath()
}  // class JsonReferenceTransformer
