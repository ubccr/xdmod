<?php

namespace DataWarehouse\Query\Jobs;

class JobMetadata
{
    public function getJobMetadata($user, $jobid)
    {
        $job = $this->lookupJob($user, $jobid);
        if ($job == null) {
            return array();
        }

        return array(
            \DataWarehouse\Query\RawQueryTypes::ACCOUNTING => true
        );
    }

    public function getJobSummary($user, $jobid)
    {
        return array();
    }

    public function getJobExecutableInfo($user, $jobid)
    {
        return array();
    }

    public function getJobTimeseriesMetaData($user, $jobid)
    {
        return array();
    }

    public function getJobTimeseriesMetricMeta($user, $jobid, $metric)
    {
        return array();
    }

    public function getJobTimeseriesMetricNodeMeta($user, $jobid, $metric, $nodeid)
    {
        return array();
    }

    public function getJobTimeseriesData($user, $jobid, $tsid, $nodeid, $cpuid)
    {
        return array();
    }

    /*
     * Get the local_job_id, end_time, etc for the given job entry in the
     * database. This information is used to lookup the job summary/timeseries
     * data in the document store. (But see the to-do note below).
     */
    private function lookupJob($user, $jobid)
    {
        $query = new \DataWarehouse\Query\Jobs\JobDataset(array('primary_key' => $jobid));
        $query->setMultipleRoleParameters($user->getAllRoles(), $user);
        $stmt = $query->getRawStatement();

        $job = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (count($job) != 1) {
            return null;
        }
        return $job[0];
    }
}
