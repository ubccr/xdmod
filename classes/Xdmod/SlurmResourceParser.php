<?php
/**
 * Slurm resource (TRES) parser.
 */

namespace Xdmod;

use xd_utilities;

/**
 * Contains functions related to Slurm's TRES data.
 */
class SlurmResourceParser
{
    /**
     * Parse requested trackable resources (ReqTRES or AllocTres) field from sacct.
     *
     * Expected TRES format is a list of resources separated by commas with
     * the each resource using the form name=count
     *
     * e.g. billing=1,cpu=1,mem=100M,node=1
     *
     * @see https://slurm.schedmd.com/tres.html
     *
     * @param string $tres ReqTRES or AllocTRES field from sacct.
     * @return array[] Parsed data. An array of arrays for each resource split
     *     on "=".
     */
    public function parseTres($tres)
    {
        return array_map(
            function ($resource) {
                return explode('=', $resource, 2);
            },
            explode(',', $tres)
        );
    }

    /**
     * Determine the GPU count from parsed TRES data.
     *
     * @see \Xdmod\SlurmResourceParser::parseTres
     *
     * @param array $tres Parsed TRES data.
     * @return int The GPU count.
     */
    public function getGpuCountFromTres(array $tres)
    {
        foreach ($tres as $resource) {
            if (($resource[0] === 'gres/gpu'
                || xd_utilities\string_begins_with($resource[0], 'gres/gpu:')
                ) && count($resource) > 1
            ) {
                return (int)$resource[1];
            }
        }

        return 0;
    }
}
