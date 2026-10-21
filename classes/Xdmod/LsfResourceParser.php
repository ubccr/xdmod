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
     * number may be followed by a reservation method.  "/host" means
     * the number is per host, so it is multiplied by the number of hosts
     * the job ran on.  "/job" means the number is the total for the job.
     * "/task" means the number is per task, but lsb.acct does not record
     * how many tasks a job ran, so it is treated as the total for the
     * job.  When there is no reservation method the number is also used
     * as the total for the job.  LSF documents "bsub -gpu num=" as per
     * host by default, so jobs that span hosts without specifying
     * "/host" may be undercounted.
     *
     * A duration or decay ("ngpus_physical=2/host:duration=1h") is
     * ignored.
     *
     * The GPU resource names used by older versions of LSF
     * ("ngpus_shared", "ngpus_excl_p" and "ngpus_excl_t") are not
     * supported.
     *
     * @see \Xdmod\LsfResourceParser::parseResourceRequirement
     * @see https://www.ibm.com/docs/SSWRJV_10.1.0/lsf_admin/usage_string.html
     *
     * @param array $rusage Parsed "rusage" data.
     * @param int $hostCount Number of distinct hosts the job ran on.
     * @return int The GPU count.
     */
    public function getGpuCountFromRusage(array $rusage, $hostCount)
    {
        if (!isset($rusage['ngpus_physical'])) {
            return 0;
        }

        // Remove any duration or decay, then split the number from the
        // reservation method.
        list($value) = explode(':', $rusage['ngpus_physical'], 2);
        $parts = explode('/', $value, 2);
        $gpuCount = (int)$parts[0];

        if (count($parts) > 1 && strtolower($parts[1]) === 'host') {
            return $gpuCount * $hostCount;
        }

        return $gpuCount;
    }
}
