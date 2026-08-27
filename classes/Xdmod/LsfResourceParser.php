<?php
/**
 * LSF resource requirement parser.
 */

namespace Xdmod;

/**
 * Contains functions related to LSF resource requirement strings.
 */
class LsfResourceParser
{
    /**
     * Parse the "rusage" section of an LSF resource requirement string.
     *
     * A resource requirement string is a series of sections, e.g.
     * "select[...] order[...] rusage[...] span[...]".  Only the "rusage"
     * section contains requested resource quantities.
     *
     * e.g. rusage[mem=6000.00,ngpus_physical=2.00]
     *
     * Compound ("1*{...} + 8*{...}") and alternative ("... || ...")
     * resource requirements describe more than one set of resources.
     * There is no way to tell how much of each resource the job
     * requested overall, so no data is returned for them.
     *
     * @see https://www.ibm.com/docs/en/spectrum-lsf/10.1.0?topic=requirements-rusage-string
     *
     * @param string $resReq An LSF resource requirement string.
     * @return array Requested resources as an associative array mapping
     *     resource name to the requested value.
     */
    public function parseResourceRequirement($resReq)
    {
        if (
            strpos($resReq, '{') !== false
            || strpos($resReq, '||') !== false
        ) {
            return array();
        }

        if (preg_match('/(?:^|\s)rusage\[([^\]]*)\]/', $resReq, $matches) !== 1) {
            return array();
        }

        $rusage = array();

        foreach (explode(',', $matches[1]) as $resource) {
            $parts = explode('=', $resource, 2);
            $rusage[trim($parts[0])] = count($parts) > 1 ? $parts[1] : '';
        }

        return $rusage;
    }

    /**
     * Determine the GPU count from parsed "rusage" data.
     *
     * "ngpus_physical" is the number of physical GPUs requested.  The
     * value may be an integer or a decimal and may be followed by a
     * duration or decay ("ngpus_physical=2:duration=1h") or by a "/task",
     * "/host" or "/job" qualifier ("ngpus_physical=2/task"), all of which
     * are ignored.
     *
     * The GPU resource names used by older versions of LSF
     * ("ngpus_shared", "ngpus_excl_p" and "ngpus_excl_t") are not
     * supported.
     *
     * @see \Xdmod\LsfResourceParser::parseResourceRequirement
     *
     * @param array $rusage Parsed "rusage" data.
     * @return int The GPU count.
     */
    public function getGpuCountFromRusage(array $rusage)
    {
        if (!isset($rusage['ngpus_physical'])) {
            return 0;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)/', $rusage['ngpus_physical'], $matches) !== 1) {
            return 0;
        }

        return (int)$matches[1];
    }
}
