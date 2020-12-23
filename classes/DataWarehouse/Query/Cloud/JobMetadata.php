<?php

namespace DataWarehouse\Query\Cloud;

use \XDUser;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;

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
          \DataWarehouse\Query\RawQueryTypes::ACCOUNTING => true,
          \DataWarehouse\Query\RawQueryTypes::VM_INSTANCE => true
        );
    }

    /**
     * Note there is no job summary data available in the Cloud realm
     */
    public function getJobSummary(XDUser $user, $jobid)
    {
        return array();
    }

    /**
     * Note there is no job executable data available in the Cloud realm
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
        return array( "tsid" => 'instance', "text" => "Instance timeseries for VM", "leaf" => false );
    }

    public function getJobTimeseriesMetricMeta(XDUser $user, $jobid, $tsid)
    {
        return array();
    }

    public function getJobTimeseriesMetricNodeMeta(XDUser $user, $jobid, $tsid, $nodeid)
    {
        return array();
    }

    public function getJobTimeseriesData(XDUser $user, $jobid, $tsid, $nodeid, $cpuid)
    {
        $job = $this->lookupJob($user, $jobid);
        if ($job == null) {
            return array();
        }

        $ct = new \DataWarehouse\Query\Cloud\JobTimeseries(array());
        $timeseries =  $ct->get($jobid);

        return $timeseries;
    }

    /**
     * Lookup the job in the datawarehouse to check that it exists and the
     * user has permission to view it.
     *
     * @param XDUser $user The user to lookup the VM for.
     * @param $jobid the unique identifier for the VM.
     *
     * @return array() the accounting data for the job or null if no job exists or permission denied
     */
    private function lookupJob(XDUser $user, $jobid)
    {
        $query = new \DataWarehouse\Query\Cloud\JobDataset(array('primary_key' => $jobid));
        $query->setMultipleRoleParameters($user->getAllRoles(), $user);
        $stmt = $query->getRawStatement(1, 0);

        $job = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (count($job) != 1) {
            return null;
        }
        return $job[0];
    }
}
