<?php
/**
 * Slurm shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;
use DateTime;
use DateTimeZone;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use Xdmod\SlurmResourceParser;

class Slurm extends Shredder
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'shredded_job_slurm';

    /**
     * @inheritdoc
     */
    protected static $tablePkName = 'shredded_job_slurm_id';

    /**
     * The field names needed from Slurm as named by sacct.
     *
     * @var array
     */
    protected static $fieldNames = array(
        'jobid',
        'jobidraw',
        'cluster',
        'partition',
        'qos',
        'account',
        'group',
        'gid',
        'user',
        'uid',
        'submit',
        'eligible',
        'start',
        'end',
        'elapsed',
        'exitcode',
        'state',
        'nnodes',
        'ncpus',
        'reqcpus',
        'reqmem',
        'reqtres',
        'alloctres',
        'timelimit',
        'nodelist',
        'jobname',
    );

     /**
      * The field names needed from Slurm as named in the database.
      *
      * @var array
      */
    protected static $columnNames = array(
        'job_id',
        'job_id_raw',
        'cluster_name',
        'partition_name',
        'qos_name',
        'account_name',
        'group_name',
        'gid_number',
        'user_name',
        'uid_number',
        'submit_time',
        'eligible_time',
        'start_time',
        'end_time',
        'elapsed',
        'exit_code',
        'state',
        'nnodes',
        'ncpus',
        'req_cpus',
        'req_mem',
        'req_tres',
        'alloc_tres',
        'timelimit',
        'node_list',
        'job_name',
    );

    /**
     * The number of columns in input lines.
     *
     * @var int
     */
    protected static $columnCount;

    /**
     * @inheritdoc
     */
    protected static $columnMap = array(
        'date_key'        => 'DATE(FROM_UNIXTIME(end_time))',
        'job_id'          => 'job_id',
        'job_array_index' => 'NULLIF(job_array_index, -1)',
        'job_id_raw'      => 'job_id_raw',
        'job_name'        => 'job_name',
        'resource_name'   => 'cluster_name',
        'queue_name'      => 'partition_name',
        'qos_name'        => 'qos_name',
        'user_name'       => 'user_name',
        'uid_number'      => 'uid_number',
        'group_name'      => 'group_name',
        'gid_number'      => 'gid_number',
        'account_name'    => 'account_name',
        'pi_name'         => 'group_name',
        'start_time'      => 'start_time',
        'end_time'        => 'end_time',
        'submission_time' => 'submit_time',
        'eligible_time'   => 'eligible_time',
        'exit_code'       => 'exit_code',
        'exit_state'      => 'state',
        'wall_time'       => 'GREATEST(CAST(end_time AS SIGNED) - CAST(start_time AS SIGNED), 0)',
        'wait_time'       => 'GREATEST(CAST(start_time AS SIGNED) - CAST(submit_time AS SIGNED), 0)',
        'node_count'      => 'nnodes',
        'cpu_count'       => 'ncpus',
        'gpu_count'       => 'ngpus',
        'cpu_req'         => 'req_cpus',
        'mem_req'         => 'req_mem',
        'timelimit'       => 'timelimit',
        'node_list'       => 'node_list',
    );

    /**
     * @inheritdoc
     */
    protected static $dataMap = array(
        'job_id'          => 'job_id',
        'start_time'      => 'start_time',
        'end_time'        => 'end_time',
        'submission_time' => 'submit_time',
        'walltime'        => 'elapsed',
        'nodes'           => 'nnodes',
        'cpus'            => 'ncpus',
    );

    /**
     * The Slurm job states corresponding to jobs that are no longer
     * running.
     *
     * @var string[]
     */
    private static $endedJobStates = [
        'BOOT_FAIL',
        'CANCELLED',
        'COMPLETED',
        'FAILED',
        'NODE_FAIL',
        'PREEMPTED',
        'TIMEOUT',
        'OUT_OF_MEMORY',
        'DEADLINE',
        'REVOKED'
    ];

    /**
     * The Slurm job states corresponding to jobs that have not started or not
     * ended.
     *
     * @var string[]
     */
    private static $nonEndedJobStates = [
        'PENDING',
        'RUNNING',
        'REQUEUED',
        'RESIZING',
        'SUSPENDED'
    ];

    /**
     * Any job states that are not currently known to the shredder.
     *
     * @var string[]
     */
    private static $unknownJobStates = [];

    /**
     * Time zone used when parsing datetimes.
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * @var \Xdmod\SlurmResourceParser
     */
    private $resourceParser;

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        parent::__construct($db);

        self::$columnCount = count(self::$columnNames);

        $this->timeZone = new DateTimeZone('UTC');
        $this->resourceParser = new SlurmResourceParser();
    }

    /**
     * @inheritdoc
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        $fields = explode('|', $line, self::$columnCount);

        if (count($fields) != self::$columnCount) {
            throw new Exception("Malformed Slurm sacct line: '$line'");
        }

        $job = array();

        // Map numeric $fields array into a associative array.
        foreach (self::$columnNames as $index => $name) {
            $job[$name] = $fields[$index];
        }

        // Skip job steps.
        if (strpos($job['job_id'], '.') !== false) {
            $this->logger->debug('Skipping job step');
            return;
        }

        // Skip jobs that haven't ended.
        if ($job['end_time'] == 'Unknown') {
            $this->logger->debug('Skipping job with unknown end time');
            return;
        }

        // Skip jobs that have no nodes assigned.
        if ($job['node_list'] == 'None assigned') {
            $this->logger->debug('Skipping job with no nodes assigned');
            return;
        }

        // Split the job state because canceled jobs are reported as "CANCELLED
        // by ...".
        list($jobState) = explode(' ', strtoupper($job['state']), 2);

        if (!in_array($jobState, self::$endedJobStates)) {
            if (in_array($jobState, self::$nonEndedJobStates)) {
                $this->logger->debug(
                    sprintf(
                        'Skipping job with non-ended state "%s"',
                        $jobState
                    )
                );
                return;
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
            return;
        }

        $this->logger->debug('Parsed data: ' . json_encode($job));

        $node = $this->getFirstNode($job['node_list']);

        if (!$this->testHostFilter($node)) {
            $this->logger->debug('Skipping line due to host filter');
            return;
        }

        // Convert job name encoding.
        $job['job_name'] = mb_convert_encoding($job['job_name'], 'ISO-8859-1', 'UTF-8');

        // Convert datetime strings into unix timestamps.
        $dateKeys = array(
            'submit_time',
            'eligible_time',
            'start_time',
            'end_time',
        );

        foreach ($dateKeys as $key) {
            $job[$key] = $this->parseDateTime($job[$key]);
        }

        // Convert slurm time fields into number of seconds.
        $timeKeys = array(
            'elapsed',
            'timelimit',
        );

        foreach ($timeKeys as $key) {
            $job[$key] = $this->parseTimeField($job[$key]);
        }

        $tres = $this->resourceParser->parseTres($job['alloc_tres']);
        $job['ngpus'] = $this->resourceParser->getGpuCountFromTres($tres);

        $job['cluster_name'] = $this->getResource();

        $this->checkJobData($line, $job);

        // Check for job arrays.

        $underscorePos = strpos($job['job_id'], '_');

        if ($underscorePos !== false) {
            if (
                   $underscorePos == 0
                || $underscorePos == strlen($job['job_id']) - 1
            ) {
                $msg = "Unexpected underscore in job id '{$job['job_id']}'";
                throw new Exception($msg);
            }

            list($jobId, $arrayPart) =  explode('_', $job['job_id'], 2);

            try {
                $arrayIds = $this->parseJobArrayIndexes($arrayPart);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $msg = "Failed to parse job id '{$job['job_id']}'";
                throw new Exception($msg);
            }

            $job['job_id'] = $jobId;

            foreach ($arrayIds as $arrayId) {
                $job['job_array_index'] = $arrayId;
                $this->insertRow($job);
            }
        } else {
            $job['job_array_index'] = -1;
            $this->insertRow($job);
        }
    }

    /**
     * Returns the field names needed from Slurm as named by sacct.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return self::$fieldNames;
    }

    /**
     * Return the first node from a nodeset.
     *
     * Parses string like this:
     *     node[0-4]
     *     node[1,3,8]
     *     node5,other6
     *
     * @param string $nodeList
     *
     * @return string The name of the first node in the list.
     */
    private function getFirstNode($nodeList)
    {
        $bracketPos = strpos($nodeList, '[');
        $commaPos   = strpos($nodeList, ',');

        // If the nodeset doesn't contain a bracket "[" or if a comma is
        // preset in the nodeset before the bracket, return everything
        // before the first comma. (e.g. "node2,node1" returns "node1",
        // "node6,node[10-20]" returns "node6".)
        //
        // If the nodeset contains a bracket before any commas then take
        // everything before the bracket and append the first number
        // inside the brackets. (e.g. "node[10-20],node30" returns
        // "node10", "node[3,5]" returns "node3".)

        if (
                $bracketPos === false
            || ($commaPos !== false && $bracketPos < $commaPos)
        ) {
            $nodes = explode(',', $nodeList);
            return $nodes[0];
        } else {
            $parts = explode('[', $nodeList);
            list($range) = explode(']', $parts[1]);
            list($number) = preg_split('/[^0-9]/', $range);
            return $parts[0] . $number;
        }
    }

    /**
     * Parse a datetime from Slurm.
     *
     * Datetimes are expected to be in the UTC time zone.
     *
     * @param string $dateTimeStr Datetime formatted as YYYY-MM-DDTHH-MM-SS.
     *
     * @return int|null Unix timestamp representation of the datetime or null
     *     if parsing failed.
     */
    private function parseDateTime($dateTimeStr)
    {
        $dateTimeObj = DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $dateTimeStr,
            $this->timeZone
        );

        if ($dateTimeObj === false) {
            $this->logger->debug("Failed to parse datetime '$dateTimeStr'");
            return null;
        }

        return (int)$dateTimeObj->format('U');
    }

    /**
     * Parse a time field from Slurm.
     *
     * Time fields are represented as
     * [[days-]hours:]minutes:seconds.hundredths
     *
     * @param string $time
     *
     * @return int Time formatted in seconds.
     */
    private function parseTimeField($time)
    {
        if ($time === '') {
            return null;
        }

        if (strcasecmp($time, 'UNLIMITED') === 0) {
            return null;
        }

        $pattern = '
            /
            ^
            (?:
                (?:
                    (?<days> \d+ )
                    -
                )?
                (?<hours> \d+ )
                :
            )?
            (?<minutes> \d+ )
            :
            (?<seconds> \d+ )
            (?: \. \d+ )?
            $
            /x
        ';

        // Instead of adding a special case for every non-time formatted
        // time field value, return null if the value doesn't match the
        // expected pattern.  Time fields may be stored as strings in
        // future versions of Open XDMoD and then parsed later in the
        // ETL process.
        if (!preg_match($pattern, $time, $matches)) {
            return null;
        }

        $days = $hours = 0;

        if (!empty($matches['days'])) {
            $days = $matches['days'];
        }

        if (!empty($matches['hours'])) {
            $hours = $matches['hours'];
        }

        $minutes = $matches['minutes'];
        $seconds = $matches['seconds'];

        return $days * 24 * 60 * 60
            + $hours * 60 * 60
            + $minutes * 60
            + $seconds;
    }

    /**
     * Parse the array indexes part of a job id.
     *
     * A job ID that contains array indexes is expected to be a number
     * followed by an underscored followed by either a single number or
     * a comma delimited list of numbers and ranges. e.g.: 123_1,
     * 123_[1,2], 123_[1-3], 123_[1,4-9], 123_[2-5,17].
     *
     * @param string $arrayList The part of the job id that contains
     *   the array indexes (everything after the underscore).
     *
     * @return array An array containing all the job array IDs.
     *
     * @throws Exception If parsing fails.
     */
    private function parseJobArrayIndexes($arrayList)
    {
        if (preg_match('/^\d+$/', $arrayList)) {
            return array($arrayList);
        }

        $containsBrackets
            = strpos($arrayList, '[') === 0
            && strrpos($arrayList, ']') === strlen($arrayList) - 1;

        if ($containsBrackets) {

            // Remove brackets and split on comma.
            $arrayParts = explode(
                ',',
                substr($arrayList, 1, strlen($arrayList) - 2)
            );

            $arrayIds = array();

            foreach ($arrayParts as $arrayPart) {
                if (strpos($arrayPart, '-') !== false) {
                    list($min, $max) = explode('-', $arrayPart, 2);
                    $arrayIds = array_merge($arrayIds, range($min, $max));
                } else {
                    $arrayIds[] = $arrayPart;
                }
            }

            return $arrayIds;
        } else {
            $msg = "Failed to parse job array indexes '$arrayList'";
            throw new Exception($msg);
        }
    }
}
