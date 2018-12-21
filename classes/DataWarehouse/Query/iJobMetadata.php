<?php
/**
 * Interface defining the JobMetadata API. The JobMetadata API is used to provide the information
 * about a given job (or tracked entity) that is available to XDMoD but not stored in the datawarehouse
 * aggregate tables. See the JobDataset API for the access functions to retrieve information about
 * a job (or tracked entity) from the datawarehouse itself.
 *
 * All modules that implement realms that show information in the Job Viewer
 * tab must provide an implementation of this interface.
 */

namespace DataWarehouse\Query;

interface iJobMetadata
{
    /**
     * Return information about all of the data available for a given job. The supported
     * data categories are defined in \DataWarehouse\Query\RawQueryTypes
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     *
     * @returns array() of \DataWarehouse\Query\RawQueryTypes listing all of
     * the data types available for the requested job. or null if the job does
     * not exist.
     */
    public function getJobMetadata(\XDUser $user, $jobid);

    /**
     * Return information that will be displayed in the detailed metrics tab of the Job
     * Viewer. This function will be called if the job has the DETAILED_METRICS data type.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     *
     * @returns array() of metrics. Each metric should contain the summary statistics for the metric as well as the documentation, data type and units. Metrics may be nested. For example if a job provided a CPU User metric
     * then this call would return a php array with the following structure:
     *
     *  "cpu": {
     *      "user": {
     *          "std": 0.0066031459102555,
     *          "med": 0.0045116925711787,
     *          "skw": 0.79910187474516,
     *          "cov": 0.73753588228371,
     *          "max": 0.021206549192241,
     *          "min": 0.0033731199203248,
     *          "cnt": 16,
     *          "krt": -0.85522002996351,
     *          "avg": 0.0089529825854839,
     *          "documentation": "The CPU usage in user mode of the cores that were assigned to the job. This metric reports the overall usage of each core that the job was assigned rather than, for example, the CPU usage of the job processes themselves.",
     *          "type": "instant",
     *          "unit": "ratio"
     *      }
     *  }
     */
    public function getJobSummary(\XDUser $user, $jobid);

    /**
     * Return information that will be displayed in the executable info tab of the Job Viewer
     * This function will be called if the job has the EXECUTABLE data type.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     *
     * @returns array() of text with the information about the executable for the job.
     */
    public function getJobExecutableInfo(\XDUser $user, $jobid);

    /**
     * Return metadata about what timeseries information is available for the provided job.
     * This function will be called if the job has the TIMESERIES_METRICS data type.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     *
     * @returns array() containing timeseries metrics metadata. For example:
     *
     *  { "tsid": "cpu_user", "name": "CPU Usage", "leaf": false }
     *
     *  where tsid is an (internal) timeseries metric identifier. name is the string that will
     *  be displayed on the gui and leaf is whether the entry is a leaf node in the metric tree.
     */
    public function getJobTimeseriesMetaData(\XDUser $user, $jobid);

    /**
     * Return metadata about what timeseries information is available for the provided metric for a job.
     * This function will be called if the job has the TIMESERIES_METRICS data type.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     * @param $tsid  The identifier for the timeseries metric. This will be the tsid value returned from a previous
     * call to getJobTimeseriesMetaData().
     *
     * @returns array() containing timeseries metric metadata. for a given metric. For example:
     *
     * [{ "nodeid": "node0", "name": "Compute Node 0", "leaf": false },
     *  { "nodeid": "node1", "name": "Compute Node 1", "leaf": false }]
     *
     *  where nodeid is an (internal) timeseries metric identifier. name is the string that will
     *  be displayed on the gui and leaf is whether the entry is a leaf node in the metric tree.
     */
    public function getJobTimeseriesMetricMeta(\XDUser $user, $jobid, $tsid);

    /**
     * Return metadata about what timeseries information is available for the provided metric for a job.
     * This function will be called if the job has the TIMESERIES_METRICS data type.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     * @param $tsid The name of the metric. This will be the tsid value returned from a previous
     *                call to getJobTimeseriesMetricMeta().
     * @param $nodeid The name of the metric. This will be the tsid value returned from a previous
     *                call to getJobTimeseriesMetricMeta()
     *
     * @returns array() containing timeseries metric metadata. for a given metric. For example:
     *
     * [{"cpuid": "cpu0", "text": "CPU0", "leaf": true },
     *  {"cpuid": "cpu1", "text": "CPU1", "leaf": true },
     *  {"cpuid": "cpu2", "text": "CPU2", "leaf": true },
     *  {"cpuid": "cpu3", "text": "CPU3", "leaf": true }]
     *
     *  where cpuid is an (internal) timeseries metric identifier. name is the string that will
     *  be displayed on the gui and leaf is whether the entry is a leaf node in the metric tree.
     */
    public function getJobTimeseriesMetricNodeMeta(\XDUser $user, $jobid, $tsid, $nodeid);

    /**
     * Return timeseries data for the provided job. The data available is
     * obtained via the getJobTimeseriesMetaData(),
     * getJobTimeseriesMetricMeta() and getJobTimeseriesMetricNodeMeta() if the
     * data returned from any of these functions has leaf: true, then the corresponding call
     * to getJobTimeseriesData() will return timeseries data.
     *
     * @param XDUser $user The authenticated user.
     * @param $jobid The unique identifier for the job in the datawarehouse fact tables.
     * @param $tsid  The timeseires metric identifier.
     * @param $nodeid The timeseries node identifier.
     * @param $cpuid  The timeseries cpu identifier.
     *
     * @returns array() timeseries data
     */
    public function getJobTimeseriesData(\XDUser $user, $jobid, $tsid, $nodeid, $cpuid);
}
