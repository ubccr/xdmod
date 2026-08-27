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
use Xdmod\LsfResourceParser;

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
        'gpu_count',
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
     * Fields at the start of a JOB_FINISH event in lsb.acct.
     *
     * The last field is the number of "asked hosts", which is followed
     * by that many host names.
     *
     * @var array
     */
    protected static $headerFieldNames = array(
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
        'num_asked_hosts',
    );

    /**
     * Fields between the "exec hosts" list and the submit extensions.
     *
     * The last field is the number of submit extensions, each of which
     * is a (key, value) pair.
     *
     * @var array
     */
    protected static $jobFieldNames = array(
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
        'submit_ext_num',
    );

    /**
     * Fields after the host rusage section.
     *
     * Fields that are not used by Open XDMoD are listed as null.  They
     * are skipped without being stored so that no meaning is implied for
     * values that have not been verified.
     *
     * @var array
     */
    protected static $tailFieldNames = array(
        'run_limit',
        null,
        null,
        'effective_res_req',
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        'run_time',
    );

    /**
     * True after warning about an unrecognized record layout.
     *
     * Used so that a single warning is logged for a file rather than one
     * warning per record.
     *
     * @var bool
     */
    protected static $warnedAboutLayout = false;

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
        'gpu_count'       => 'gpu_count',

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
     * @var \Xdmod\LsfResourceParser
     */
    protected $resourceParser;

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        parent::__construct($db);

        static::$columnNamesAsKeys = array_flip(static::$columnNames);
        $this->resourceParser = new LsfResourceParser();
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

        $this->logger->debug(
            'Estimating walltime with data from rusage',
            [
                'ru_utime' => $job['ru_utime'],
                'ru_stime' => $job['ru_stime'],
                'walltime' => $job['walltime']
            ]
        );

        $job['resource_name'] = $this->getResource();

        // The effective resource requirement is the resource requirement
        // string the user supplied expanded with the queue and
        // application defaults, including any "-gpu" options.
        $rusage = $this->resourceParser->parseResourceRequirement(
            isset($job['effective_res_req']) ? $job['effective_res_req'] : ''
        );
        $job['gpu_count'] = $this->resourceParser->getGpuCountFromRusage($rusage);

        $this->checkJobData($line, $job);

        $this->insertRow($job);
    }

    /**
     * Parse a line from lsb.acct
     *
     * A JOB_FINISH event contains four variable length sections, each of
     * which is preceded by the number of entries it contains.  Every
     * field that follows a section is misaligned if that section is not
     * skipped correctly.
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

        $job = array();

        // Index of the next field to read.  Passed by reference to all
        // the helper functions below.
        $idx = 0;

        $this->readFields($fields, $idx, static::$headerFieldNames, $job);

        // Both of the host count fields are followed by that many host
        // names.
        $job['asked_hosts'] = $this->readList(
            $fields,
            $idx,
            isset($job['num_asked_hosts']) ? $job['num_asked_hosts'] : 0
        );

        $numExHosts = $this->readField($fields, $idx);

        $job['exec_hosts'] = $this->readList($fields, $idx, $numExHosts);

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

        // Store "num_ex_hosts" as the number of distinct hosts.
        $job['num_ex_hosts'] = count($job['exec_hosts']);

        $this->readFields($fields, $idx, static::$jobFieldNames, $job);

        // Every submit extension is a (key, value) pair and every host
        // rusage entry is six values.  Neither is used by Open XDMoD,
        // but both must be skipped or every field that follows them is
        // misaligned.
        if (!$this->skipSection($fields, $idx, $job, 'submit_ext_num', 2)) {
            return $job;
        }

        $numHostRusage = $this->readField($fields, $idx);

        if ($numHostRusage !== null) {
            $job['num_host_rusage'] = $numHostRusage;
        }

        if (!$this->skipSection($fields, $idx, $job, 'num_host_rusage', 6)) {
            return $job;
        }

        $this->readFields($fields, $idx, static::$tailFieldNames, $job);

        $this->checkRecordLayout($job);

        if ($idx < count($fields)) {
            $this->logger->debug(
                'Unused fields: ' . json_encode(array_slice($fields, $idx))
            );
        }

        return $job;
    }

    /**
     * Read a run of consecutive fields into the parsed job data.
     *
     * Names may be null for fields that are present in the record but
     * are not used by Open XDMoD.  Those fields are skipped without
     * being stored.
     *
     * @param array $fields All the fields in the record.
     * @param int &$idx Index of the next field to read.
     * @param array $names Field names in the order they appear in the
     *   record.
     * @param array &$job Parsed job data.
     */
    protected function readFields(array $fields, &$idx, array $names, array &$job)
    {
        foreach ($names as $name) {
            $value = $this->readField($fields, $idx);

            if ($value === null) {
                break;
            }

            if ($name !== null) {
                $job[$name] = $value;
            }
        }
    }

    /**
     * Read a single field.
     *
     * @param array $fields All the fields in the record.
     * @param int &$idx Index of the next field to read.
     *
     * @return string|null The field value or null if the record does not
     *   contain any more fields.
     */
    protected function readField(array $fields, &$idx)
    {
        if ($idx >= count($fields)) {
            return null;
        }

        return $fields[$idx++];
    }

    /**
     * Read a list of values that is preceded by a count.
     *
     * @param array $fields All the fields in the record.
     * @param int &$idx Index of the next field to read.
     * @param string|null $count Number of values in the list.
     *
     * @return array
     */
    protected function readList(array $fields, &$idx, $count)
    {
        $list = array();

        for ($i = 0; $i < (int)$count; $i++) {
            $value = $this->readField($fields, $idx);

            if ($value === null) {
                break;
            }

            $list[] = $value;
        }

        return $list;
    }

    /**
     * Skip over a variable length section of a record.
     *
     * @param array $fields All the fields in the record.
     * @param int &$idx Index of the next field to read.
     * @param array $job Parsed job data.
     * @param string $countName Name of the field containing the number
     *   of entries in the section.
     * @param int $width Number of fields in each entry.
     *
     * @return bool True if the section was skipped, false if the rest of
     *   the record can't be parsed.
     */
    protected function skipSection(array $fields, &$idx, array $job, $countName, $width)
    {
        if (!isset($job[$countName])) {
            $this->logger->debug("Record ended before '$countName'");
            return false;
        }

        $count = $job[$countName];

        if (!ctype_digit((string)$count)) {
            $this->logger->warning(
                'Unexpected lsb.acct format',
                array('field' => $countName, 'value' => $count)
            );
            return false;
        }

        $idx += $count * $width;

        if ($idx > count($fields)) {
            $this->logger->warning(
                'Truncated lsb.acct record',
                array('field' => $countName, 'value' => $count)
            );
            return false;
        }

        return true;
    }

    /**
     * Check that the record layout appears to be correct.
     *
     * The effective resource requirement is the first field after the
     * variable length sections that has a recognizable format and is the
     * field the GPU count is taken from, so it is used to detect a
     * record that hasn't been parsed correctly.  Only a single warning
     * is logged because an accounting file may contain millions of
     * records.
     *
     * @param array $job Parsed job data.
     */
    protected function checkRecordLayout(array $job)
    {
        if (static::$warnedAboutLayout) {
            return;
        }

        if (!isset($job['effective_res_req'])) {
            return;
        }

        $resReq = $job['effective_res_req'];

        if ($resReq === '' || strpos($resReq, '[') !== false) {
            return;
        }

        static::$warnedAboutLayout = true;

        $this->logger->warning(
            'Unexpected lsb.acct format, the effective resource requirement'
            . ' is not a resource requirement string.  GPU counts will not be'
            . ' available.  The version of LSF that produced this file may not'
            . ' be supported.',
            array('effective_res_req' => $resReq)
        );
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
