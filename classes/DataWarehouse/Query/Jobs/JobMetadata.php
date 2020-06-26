<?php

namespace DataWarehouse\Query\Jobs;

use \XDUser;

/**
 * @see DataWarehouse::Query::iJobMetadata
 */
class JobMetadata implements \DataWarehouse\Query\iJobMetadata
{
    public function getJobMetadata(XDUser $user, $jobid)
    {
        $job = $this->lookupJob($user, $jobid);
        if ($job == null) {
            return array();
        }

        return array(
            \DataWarehouse\Query\RawQueryTypes::ACCOUNTING => true
        );
    }

    /**
     * Note there is no job summary data available in the Jobs realm
     */
    public function getJobSummary(XDUser $user, $jobid)
    {
        return array();
    }

    /**
     * Note there is no job executable data available in the Jobs realm
     */
    public function getJobExecutableInfo(XDUser $user, $jobid)
    {
        return array();
    }

    /**
     * Note there is no job timeseries data available in the Jobs realm
     */
    public function getJobTimeseriesMetaData(XDUser $user, $jobid)
    {
        return array();
    }

    /**
     * Note there is no job timeseries data available in the Jobs realm
     */
    public function getJobTimeseriesMetricMeta(XDUser $user, $jobid, $tsid)
    {
        return array();
    }

    /**
     * Note there is no job timeseries data available in the Jobs realm
     */
    public function getJobTimeseriesMetricNodeMeta(XDUser $user, $jobid, $tsid, $nodeid)
    {
        return array();
    }

    /**
     * Note there is no job timeseries data available in the Jobs realm
     */
    public function getJobTimeseriesData(XDUser $user, $jobid, $tsid, $nodeid, $cpuid)
    {
        return array();
    }

    /**
     * Lookup the job in the datawarehouse to check that it exists and the
     * user has permission to view it.
     *
     * @param XDUser $user The user to lookup the job for.
     * @param $jobid the unique identifier for the job.
     *
     * @return array() the accounting data for the job or null if no job exists or permission denied
     */
    private function lookupJob(XDUser $user, $jobid)
    {
        $query = new \DataWarehouse\Query\Jobs\JobDataset(array('primary_key' => $jobid));
        $query->setMultipleRoleParameters($user->getAllRoles(), $user);
        $stmt = $query->getRawStatement(1, 0);

        $job = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (count($job) != 1) {
            return null;
        }
        return $job[0];
    }
}
