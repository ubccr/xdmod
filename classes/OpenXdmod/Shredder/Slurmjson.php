<?php
/**
 * shredder for Slurm logs in JSON format.
 *
 */

namespace OpenXdmod\Shredder;

use Exception;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;

class Slurmjson extends Slurm
{
    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        parent::__construct($db);
    }

    /**
     * Shred a file.
     *
     * @param string $file The file path.
     *
     * @return int|false The number of records shredded or false on error
     */
    public function shredFile($file)
    {
        $this->logger->notice("Shredding file '$file'");

        if (!is_file($file)) {
            $this->logger->error("'$file' is not a file");
            return false;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new Exception("Failed to open file '$file'");
        }

        $data = json_decode($contents);

        if ($data === null || !isset($data->jobs)) {
            $this->logger->error("'$file' does not contain valid json");
            return false;
        }

        $recordCount = 0;
        $duplicateCount = 0;

        $this->logger->info('Starting database transaction');
        $this->db->beginTransaction();

        try {
            foreach($data->jobs as $jobrecord) {
                $job = $this->parseJobRecord($jobrecord);

                if ($job === null) {
                    continue;
                }

                $recordCount++;

                try {
                    $this->insertRow($job);
                } catch (\PDOException $e) {

                    // Ignore duplicate key errors.
                    if ($e->getCode() == 23000) {
                        $msg = 'Skipping duplicate data: ' . $e->getMessage();
                        $this->logger->debug(array( 'message' => $msg, 'file' => $file));
                        $duplicateCount++;
                        continue;
                    } else {
                        throw $e;
                    }
                }
            }
            $this->logger->info('Committing database transaction');
            $this->db->commit();

            if ($duplicateCount > 0) {
                $msg = "Skipped $duplicateCount duplicate records";
                $this->logger->info($msg);
            }

        } catch (Exception $e) {
            $this->logger->info('Rolling back database transaction');
            $this->db->rollBack();

            $msg = sprintf(
                'Failed to shred file "%s": %s',
                $file,
                $e->getMessage()
            );

            throw new Exception($msg, 0, $e);
        }

        return $recordCount;
    }

    /**
     * helper function to get a a TRES value from a slurm json accounting
     *   record. This follows a similar algorithm used by sacct. If the job
     *   has been allocated resource then the allocated value is used, otherwise
     *   the requested value is used. 0 is returned if no data found.
     */
    private function getTresValue($jobrecord, $rtype, $rname = null)
    {
        if (!isset($jobrecord->tres)) {
            return 0;
        }

        if (isset($jobrecord->tres->allocated)) {
            foreach($jobrecord->tres->allocated as $record) {
                if ($record->type == $rtype) {
                    if ($rname === null || $record->name == $rname) {
                        return $record->count;
                    }
                }
            }
        }

        if (isset($jobrecord->tres->requested)) {
            foreach($jobrecord->tres->requested as $record) {
                if ($record->type == $rtype) {
                    if ($rname === null || $record->name == $rname) {
                        return $record->count;
                    }
                }
            }
        }

        return 0;
    }

    private function getTimeLimit($jobrecord) {
        if (isset($jobrecord->time->limit)) {
            if (isset($jobrecord->time->limit->number)) {
                return $jobrecord->time->limit->number;
            }
            return $jobrecord->time->limit;
        }
        return 0;
    }

    private function getJobId($jobrecord) {
        if (isset($jobrecord->array) && isset($jobrecord->array->job_id) && $jobrecord->array->job_id != 0)
        {
            $array_index = $jobrecord->array->task_id->number ?? $jobrecord->array->task_id;

            return array($jobrecord->array->job_id, $array_index);
        }

        return array($jobrecord->job_id, -1);
    }

    private function getStartTime($jobrecord) {
        $start_ts = null;

        foreach($jobrecord->steps as $step) {
            $ts1 = $step->time->start->number ?? $step->time->start;

            if ($start_ts == null) {
                $start_ts = $ts1;
            } else {
                $start_ts = min($start_ts, $ts1);
            }
        }

        if ($start_ts === null) {
            if ($jobrecord->time->elapsed == 0) {
                $start_ts = $jobrecord->time->end;
            } else {
                $start_ts = $jobrecord->time->start;
            }
        }

        return $start_ts;
    }

    private function getExitCode($jobrecord) {

        $state = $this->getJobState($jobrecord);

        if ($state == 'FAILED') {
            return "1:0";
        }

        $derived = $jobrecord->derived_exit_code;
        $return_code = $derived->return_code->number ?? $derived->return_code;

        if (isset($derived->signal)) {
            $signal = $derived->signal->id->number ?? $derived->signal->signal_id;
        } else {
            $signal = 0;
        }

        if ($return_code === null) {
            $return_code = $signal;
            $signal = 0;
        }

        return "$return_code:$signal";
    }

    private function getJobState($jobrecord) {
        if (is_array($jobrecord->state->current)) {
            return $jobrecord->state->current[0];
        }

        return $jobrecord->state->current;
    }

    private function parseJobRecord($jobrecord) {

        // Skip jobs that haven't ended.
        if ($jobrecord->time->end == 0) {
            $this->logger->debug('Skipping job with unknown end time');
            return null;
        }

        // Skip jobs that have no nodes assigned.
        if ($jobrecord->nodes == 'None assigned') {
            $this->logger->debug('Skipping job with no nodes assigned');
            return null;
        }

        $jobState = $this->getJobState($jobrecord);

        if (!in_array($jobState, self::$endedJobStates)) {
            if (in_array($jobState, self::$nonEndedJobStates)) {
                $this->logger->debug(
                    sprintf(
                        'Skipping job with non-ended state "%s"',
                        $jobState
                    )
                );
                return null;
            }

            // Warn about an unknown job state the first time it is
            // encountered.
            if (!in_array($jobState, self::$unknownJobStates)) {
                $this->logger->warning(
                    sprintf(
                        'Found job with unknown state "%s", '
                        . 'all jobs with this state will be ignored',
                        $jobState
                    )
                );
                self::$unknownJobStates[] = $jobState;
            }
            $this->logger->debug(
                sprintf('Skipping job with unknown state "%s"', $jobState)
            );
            return null;
        }

        list($local_job_id, $local_job_array_index) = $this->getJobId($jobrecord);

        $job = array(
            'job_id' => $local_job_id,
            'job_array_index' => $local_job_array_index,
            'job_id_raw' => $jobrecord->job_id,
            'cluster_name' => $this->getResource(),
            'partition_name' => $jobrecord->partition,
            'qos_name' => $jobrecord->qos,
            'account_name' => $jobrecord->account,
            'group_name' => $jobrecord->group,
            'gid_number' => -1,
            'user_name' => $jobrecord->user,
            'uid_number' => -1,
            'submit_time' => $jobrecord->time->submission,
            'eligible_time' => $jobrecord->time->eligible,
            'start_time' => $this->getStartTime($jobrecord),
            'end_time' => $jobrecord->time->end,
            'elapsed' => $jobrecord->time->elapsed,
            'exit_code' => $this->getExitCode($jobrecord),
            'state' => $jobState,
            'nnodes' => $this->getTresValue($jobrecord, 'node'),
            'ncpus' => $this->getTresValue($jobrecord, 'cpu'),
            'ngpus' => $this->getTresValue($jobrecord, 'gres', 'gpu'),
            'req_cpus' => $jobrecord->required->CPUs,
            'req_mem' => $this->getTresValue($jobrecord, 'mem') * 1024 * 1024,
            'timelimit' => $this->getTimeLimit($jobrecord),
            'node_list' => $jobrecord->nodes,
            'job_name' => $jobrecord->name
        );

        $this->logger->debug(json_encode($job));

        return $job;
    }
}
