<?php
/**
 * Slurm resource (GRES and TRES) parser.
 */

namespace Xdmod;

/**
 * Contains functions related to Slurm's GRES and TRES data.
 */
class SlurmResourceParser
{

    /**
     * Parse generic resource (GRES) scheduling fields from sacct.
     *
     * Expected GRES format is a list of resources separated by commas with
     * the each resource using the form name[:type:count]
     *
     * e.g. gpu:p100:2,bandwidth:1G
     *
     * @see https://slurm.schedmd.com/gres.html
     *
     * If the count (or anything else) contains pairs of parenthesis these will
     * be removed along with everything contained between them.
     *
     * e.g. gpu:p100:2(IDX:0-1),hbm:0 is treated as gpu:p100:2,hbm:0
     *
     * @see https://github.com/PySlurm/pyslurm/issues/104
     *
     * @param string $gres ReqGRES field from sacct.
     * @return array[] Parsed data. An array of arrays for each resource split
     *     on ":".
     */
    public function parseGres($gres) {
        if ($gres === '') {
            return [];
        }

        // Remove anything contained in parenthesis along with the parenthesis.
        $gres = preg_replace('/\(.*?\)/', '', $gres);

        return array_map(
            function ($resource) {
                return explode(':', $resource);
            },
            explode(',', $gres)
        );
    }

    /**
     * Determine the GPU count from parsed GRES data.
     *
     * @see \Xdmod\SlurmResourceParser::parseGres
     *
     * @param array $gres Parsed GRES data.
     * @return int The GPU count.
     */
    public function getGpuCountFromGres(array $gres)
    {
        foreach ($gres as $resource) {
            if ($resource[0] === 'gpu' && count($resource) > 1) {
                return (int)$resource[count($resource) - 1];
            }
        }

        return 0;
    }

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
            if ($resource[0] === 'gres/gpu' && count($resource) > 1) {
                return (int)$resource[1];
            }
        }

        return 0;
    }
}
