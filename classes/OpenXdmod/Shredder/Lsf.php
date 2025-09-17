<?php
/**
 * LSF shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;
use DateTime;
use DateTimeZone;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;

class Lsf extends Shredder
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'shredded_job_lsf';

    /**
     * @inheritdoc
     */
    protected static $tablePkName = 'shredded_job_lsf_id';

     /**
      * The column names needed from LSF as named in the database.
      *
      * @var array
      */
    protected static $columnNames = array(
        'job_id',
        'idx',
        'job_name',
        'resource_name',
        'queue',
        'user_name',
        'project_name',
        'submit_time',
        'start_time',
        'event_time',
        'num_processors',
        'num_ex_hosts',
        'exit_status',
        'exit_info',
        'node_list',
    );

    /**
     * The column names stored as array keys.
     *
     * This is used as an optimization to determine the values that need
     * to be inserted into the database.
     *
     * @see insertRow
     *
     * @var array
     */
    protected static $columnNamesAsKeys;

    /**
     * Fields in accounting file (lsb.acct) for JOB_FINISH events.
     *
     * @var array
     */
    protected static $fieldNames = array(
        'event_type',
        'version_number',
        'event_time',
        'job_id',
        'user_id',
        'options',
        'num_processors',
        'submit_time',
        'begin_time',
        'term_time',
        'start_time',
        'user_name',
        'queue',
        'res_req',
        'depend_cond',
        'pre_exec_cmd',
        'from_host',
        'cwd',
        #'sub_cwd',
        'in_file',
        'out_file',
        'err_file',
        'job_file',

        // Number of "asked_hosts" fields is the value from the
        // "num_asked_hosts" field.
        'num_asked_hosts',
        'asked_hosts',

        // Number of "exec_hosts" fields is the value from the
        // "num_ex_hosts" field.
        'num_ex_hosts',
        'exec_hosts',

        'j_status',
        'host_factor',
        'job_name',
        'command',

        // Resource usage from getrusage.
        'ru_utime',
        'ru_stime',
        'ru_maxrss',
        'ru_ixrss',
        'ru_ismrss',
        'ru_idrss',
        'ru_isrss',
        'ru_minflt',
        'ru_majflt',
        'ru_nswap',
        'ru_inblock',
        'ru_oublock',
        'ru_ioch',
        'ru_msgsnd',
        'ru_msgrcv',
        'ru_nsignals',
        'ru_nvcsw',
        'ru_nivcsw',
        'ru_exutime',

        'mail_user',
        'project_name',
        'exit_status',
        'max_num_processors',
        'login_shell',
        'time_event',
        'idx',
        'max_rmem',
        'max_rswap',
        'in_file_spool',
        'command_spool',
        'rsv_id',
        'sla',
        'except_mask',
        'additional_info',
        'exit_info',
        'warning_action',
        'warning_time_period',
        'charged_saap',
        'license_project',
        'app',
        'post_exec_cmd',
        'runtime_estimation',
        'job_group_name',
        'requeue_evalues',
        'options2',
        'resize_notify_cmd',
        'last_resize_time',
        'rsv_id_2',
        'job_description',

        // Assuming this field will be zero.
        'submit_ext_num',

        'options3',
        'bsub_w',

        // Assuming this field will be zero.
        'num_host_rusage',

        'effective_res_req',
        'total_provisional_time',
        'run_time',
    );

    /**
     * @var integer
     */
    protected static $fieldCount;

    /**
     * @inheritdoc
     */
    protected static $columnMap = array(
        'date_key'        => 'DATE(FROM_UNIXTIME(event_time))',
        'job_id'          => 'job_id',
        'job_id_raw'      => 'job_id',
        'job_array_index' => 'idx',
        'job_name'        => 'job_name',
        'resource_name'   => 'resource_name',
        'queue_name'      => 'queue',
        'user_name'       => 'user_name',
        'project_name'    => 'project_name',
        'pi_name'         => 'project_name',
        'start_time'      => 'start_time',
        'end_time'        => 'event_time',
        'submission_time' => 'submit_time',
        'wall_time'       => 'GREATEST(CAST(event_time AS SIGNED) - CAST(start_time AS SIGNED), 0)',
        'wait_time'       => 'GREATEST(CAST(start_time AS SIGNED) - CAST(submit_time AS SIGNED), 0)',
        'node_count'      => 'num_ex_hosts',
        'cpu_count'       => 'num_processors',
        'node_list'       => 'node_list',

        // Both the exit code and exit state are integers in LSF
        // accounting logs.  These values are converted to strings
        // because other resource managers do not use integers.  A colon
        // is appended to these fields to work around an issue where
        // zero ("0") is converted to the empty string during the
        // ingestion process.
        'exit_code'       => 'CONCAT(CAST(exit_status AS CHAR), \':\')',
        'exit_state'      => 'CONCAT(CAST(exit_info AS CHAR), \':\')',
    );

    /**
     * @inheritdoc
     */
    protected static $dataMap = array(
        'job_id'          => 'job_id',
        'start_time'      => 'start_time',
        'end_time'        => 'event_time',
        'submission_time' => 'submit_time',
        'walltime'        => 'walltime',
        'nodes'           => 'num_ex_hosts',
        'cpus'            => 'num_processors',
    );

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        parent::__construct($db);

        static::$columnNamesAsKeys = array_flip(static::$columnNames);
        static::$fieldCount = count(static::$fieldNames);
    }

    /**
     * @inheritdoc
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        // Check the first field so that only "JOB_FINISH" lines are
        // parsed.
        $firstSpacePos = strpos($line, ' ');

        if ($firstSpacePos === false) {
            $this->logger->error('Unexpected lsb.acct format', ['line'    => $line]);
            return;
        }

        $firstField = substr($line, 0, $firstSpacePos);

        $this->logger->debug("First field is '$firstField'");

        $firstField = trim($firstField, '\'"');

        if ($firstField != 'JOB_FINISH') {
            $this->logger->debug('Skipping non-JOB_FINISH line');
            return;
        }

        $job = $this->parseLine($line);

        // The command may use a non-UTF-8 encoding.  Therefore it can't be
        // included in the array passed to json_encode.  The command isn't
        // currently stored in the database so it doesn't need to be converted.
        $command = $job['command'];
        unset($job['command']);
        $this->logger->debug('Parsed data (excluding command): ' . json_encode($job));
        $this->logger->debug('Parsed command: ' . $command);
        $job['command'] = $command;

        if (
            $job['num_ex_hosts'] > 0
            && !$this->testHostFilter($job['exec_hosts'][0])
        ) {
            $this->logger->debug('Skipping line due to host filter');
            return;
        }

        // Build list of host names compatible with the
        // compressed host list format used by Slurm.  This list
        // is currently not compressed, but that could be
        // implemented in the future to reduce database storage
        // requirements.
        $job['node_list'] = implode(',', $job['exec_hosts']);

        # walltime = user time + system time.
        # This isn't necessarily correct, but it's only used if the
        # start and end times are inconsistent (start_time > end_time).
        $job['walltime']
            = ($job['ru_utime'] > 0 ? $job['ru_utime'] : 0)
            + ($job['ru_stime'] > 0 ? $job['ru_stime'] : 0);

        $this->logger->debug('Estimating walltime with data from rusage',
            [
                'ru_utime' => $job['ru_utime'],
                'ru_stime' => $job['ru_stime'],
                'walltime' => $job['walltime']
            ]
        );

        $job['resource_name'] = $this->getResource();

        $this->checkJobData($line, $job);

        $this->insertRow($job);
    }

    /**
     * Parse a line from lsb.acct
     *
     * @param string $line A single line from lsb.acct.
     *
     * @return array
     */
    protected function parseLine($line)
    {

        // The format of lsb.acct is essentially a CSV file, but the "\"
        // character may be used for a different purpose.
        $fields = str_getcsv($line, ' ', '"', "\0");

        $fieldCount = count($fields);

        $job = array();

        $fieldIdx = 0;
        $fieldNameIdx = 0;

        // Map numeric $fields array into a associative array.
        while ($fieldIdx < $fieldCount) {
            $fieldName       = static::$fieldNames[$fieldNameIdx];
            $job[$fieldName] = $fields[$fieldIdx];

            if ($fieldNameIdx == static::$fieldCount - 1) {
                if ($fieldIdx + 1 < $fieldCount) {
                    $extraFields = array_slice($fields, $fieldIdx + 1);
                    $msg = 'Extra fields: ' .  json_encode($extraFields);
                    $this->logger->debug($msg);

                    foreach ($extraFields as $key => $value) {
                        $job['unknown' . ($key + 1)] = $value;
                    }
                }

                break;
            }

            // These entries indicate that the next "num" fields are
            // all part of the next field
            if (
                   $fieldName == 'num_asked_hosts'
                || $fieldName == 'num_ex_hosts'
            ) {

                // Determine the last index to include in the array.
                $maxIdx = $fieldIdx + $fields[$fieldIdx];

                $fieldArray = array();

                while ($fieldIdx < $maxIdx) {
                    $fieldIdx++;
                    $fieldArray[] = $fields[$fieldIdx];
                }

                $fieldNameIdx++;
                $fieldName       = static::$fieldNames[$fieldNameIdx];
                $job[$fieldName] = $fieldArray;
            }

            $fieldIdx++;
            $fieldNameIdx++;
        }

        // Remove slots from formatted host name.
        // e.g. "16*exampleHost" is replaced with "exampleHost".
        $job['exec_hosts'] = array_map(
            function ($host) {
                if (preg_match('/^(?:\d+\\*)?(.*)$/', $host, $matches)) {
                    return $matches[1];
                } else {
                    return $host;
                }
            },
            $job['exec_hosts']
        );

        // Remove any duplicates from the host list and re-index keys.
        $job['exec_hosts'] = array_values(array_unique($job['exec_hosts']));

        // Override "num_ex_hosts" with the number of distinct hosts.
        $job['num_ex_hosts'] = count($job['exec_hosts']);

        return $job;
    }

    /**
     * @inheritdoc
     */
    protected function insertRow($values)
    {
        $columns = array_intersect(array_keys($values), static::$columnNames);

        $sql = $this->createInsertStatement(static::$tableName, $columns);

        $this->logger->debug("Insert statement: '$sql'");

        $columnValues = array_intersect_key(
            $values,
            static::$columnNamesAsKeys
        );

        $this->logger->debug('Column values: ', $columnValues);

        $this->db->insert($sql, array_values($columnValues));
    }
}

