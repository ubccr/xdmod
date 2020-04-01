<?php
/**
 * Slurm GRES parser.
 */

namespace Xdmod;

/**
 * Contains functions related to Slurm's GRES data.
 */
class SlurmGresParser
{

    /**
     * Parse requested generic resource (GRES) scheduling field from sacct.
     *
     * Expected ReqGRES format is a list of resources separated by commas with
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
    public function parseReqGres($gres) {
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
     * Determine the GPU count from parsed ReqGRES data.
     *
     * @see \Xdmod\SlurmGresParser::parseReqGres
     *
     * @param array $gres Parsed ReqGRES data.
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
}
