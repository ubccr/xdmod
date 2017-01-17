<?php
/**
 * Sun Grid Engine shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;
use DateTime;
use OpenXdmod\Shredder;
use CCR\DB\iDatabase;

class Sge extends Shredder
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'shredded_job_sge';

    /**
     * @inheritdoc
     */
    protected static $tablePkName = 'shredded_job_sge_id';

    /**
     * All the columns in the job table, excluding the primary key.
     *
     * @var array
     */
    protected static $columnNames = array(
        'clustername',
        'qname',
        'hostname',
        'groupname',
        'owner',
        'job_name',
        'job_number',
        'account',
        'priority',
        'submission_time',
        'start_time',
        'end_time',
        'failed',
        'exit_status',
        'ru_wallclock',
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
        'ru_msgsnd',
        'ru_msgrcv',
        'ru_nsignals',
        'ru_nvcsw',
        'ru_nivcsw',
        'project',
        'department',
        'granted_pe',
        'slots',
        'task_number',
        'cpu',
        'mem',
        'io',
        'category',
        'iow',
        'pe_taskid',
        'maxvmem',
        'arid',
        'ar_submission_time',
        'resource_list_arch',
        'resource_list_qname',
        'resource_list_hostname',
        'resource_list_notify',
        'resource_list_calendar',
        'resource_list_min_cpu_interval',
        'resource_list_tmpdir',
        'resource_list_seq_no',
        'resource_list_s_rt',
        'resource_list_h_rt',
        'resource_list_s_cpu',
        'resource_list_h_cpu',
        'resource_list_s_data',
        'resource_list_h_data',
        'resource_list_s_stack',
        'resource_list_h_stack',
        'resource_list_s_core',
        'resource_list_h_core',
        'resource_list_s_rss',
        'resource_list_h_rss',
        'resource_list_slots',
        'resource_list_s_vmem',
        'resource_list_h_vmem',
        'resource_list_s_fsize',
        'resource_list_h_fsize',
        'resource_list_num_proc',
        'resource_list_mem_free',
    );

    /**
     * Entries in the accouting log file.
     *
     * NOTE: Univa Grid Engine now has a "job_class" field that may not
     * be present in other SGE accounting files, so it is not listed
     * here.
     *
     * @var array
     */
    protected static $entryNames = array(
        'qname',
        'hostname',
        'groupname',
        'owner',
        'job_name',
        'job_number',
        'account',
        'priority',
        'submission_time',
        'start_time',
        'end_time',
        'failed',
        'exit_status',
        'ru_wallclock',
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
        'ru_msgsnd',
        'ru_msgrcv',
        'ru_nsignals',
        'ru_nvcsw',
        'ru_nivcsw',
        'project',
        'department',
        'granted_pe',
        'slots',
        'task_number',
        'cpu',
        'mem',
        'io',
        'category',
        'iow',
        'pe_taskid',
        'maxvmem',
        'arid',
        'ar_submission_time',
    );

    /**
     * The minimum number of fields that must be present in a line from
     * the accounting logs.
     *
     * @var int
     */
    protected static $minimumEntryCount = 43;

    /**
     * Resource attribute list.
     *
     * @var array
     */
    protected static $resourceAttributes = array(
        'arch',
        'qname',
        'hostname',
        'notify',
        'calendar',
        'min_cpu_interval',
        'tmpdir',
        'seq_no',
        's_rt',
        'h_rt',
        's_cpu',
        'h_cpu',
        's_data',
        'h_data',
        's_stack',
        'h_stack',
        's_core',
        'h_core',
        's_rss',
        'h_rss',
        'slots',
        's_vmem',
        'h_vmem',
        's_fsize',
        'h_fsize',
        'num_proc',
        'mem_free',
    );

    /**
     * Columns that should be parsed and their expected format.
     *
     * @var array
     */
    protected static $columnFormats = array(
        's_data'   => 'memory',
        'h_data'   => 'memory',
        's_stack'  => 'memory',
        'h_stack'  => 'memory',
        's_core'   => 'memory',
        'h_core'   => 'memory',
        's_rss'    => 'memory',
        'h_rss'    => 'memory',
        's_vmem'   => 'memory',
        'h_vmem'   => 'memory',
        's_fsize'  => 'memory',
        'h_fsize'  => 'memory',
        'mem_free' => 'memory',
    );

    /**
     * Mapping from generic job table to PBS specific job table.
     *
     * @var array
     */
    protected static $columnMap = array(
        'date_key'        => 'FROM_UNIXTIME(MAX(end_time))',
        'job_id'          => 'job_number',
        'job_array_index' => 'task_number',
        'job_name'        => 'job_name',
        'resource_name'   => 'clustername',
        'queue_name'      => 'qname',
        'user_name'       => 'owner',
        'group_name'      => 'groupname',
        'account_name'    => 'account',
        'project_name'    => 'project',
        'pi_name'         => 'groupname',
        'start_time'      => 'MIN(start_time)',
        'end_time'        => 'MAX(end_time)',
        'submission_time' => 'MIN(submission_time)',
        'wall_time'       => 'GREATEST(CAST(end_time AS SIGNED) - CAST(start_time AS SIGNED), 0)',
        'wait_time'       => 'GREATEST(CAST(start_time AS SIGNED) - CAST(submission_time AS SIGNED), 0)',
        'node_count'      => 'COUNT(DISTINCT hostname)',
        'cpu_count'       => 'GREATEST(COALESCE(slots, 1), COALESCE(resource_list_num_proc, 1))',
    );

    /**
     * @inheritdoc
     */
    protected static $dataMap = array(
        'job_id'          => 'job_number',
        'start_time'      => 'start_time',
        'end_time'        => 'end_time',
        'submission_time' => 'submission_time',
        'walltime'        => 'ru_wallclock',
    );

    /**
     * Numeric fields that may also contain the value "NONE".
     *
     * @var array
     */
    protected static $mixedTypeFields = array(
        'pe_taskid',
    );

    /**
     * @inheritdoc
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        // Ignore comments.
        if (substr($line, 0, 1) == '#') {
            return;
        }

        // Ignore lines that contain one character or are blank.
        if (strlen($line) <= 1) {
            return;
        }

        $entries = explode(':', $line);

        // Attempt to determine if the line contains valid data by
        // examining the number of fields present.  Various versions of
        // SGE and GridEngine contain different fields, but all the
        // supported versions use fields that are a subset of later
        // versions.  The first 43 fields are present in the oldest
        // supported version (SGE 6.1).  Initial support was for the 45
        // fields listed in $entryNames, but at least one additional
        // field has been added since then.
        $entryCount = count($entries);

        $this->logger->debug("Line contains $entryCount fields");

        if ($entryCount < self::$minimumEntryCount) {
            $msg = sprintf(
                'Expected at least %d fields, found %d in line: %s',
                self::$minimumEntryCount,
                $entryCount,
                $line
            );
            $this->logger->err($msg);
            return;
        }

        $job = array();

        // Map numeric $entries array into a associative array.
        foreach (self::$entryNames as $index => $name) {
            // Not all supported versions contain all the entries, but
            // the current code expects a value for each entry.  Use
            // null to indicate the absence of a field.
            $job[$name]
                = array_key_exists($index, $entries)
                ? $entries[$index]
                : null;
        }

        foreach (self::$mixedTypeFields as $fieldName) {
            if ($job[$fieldName] === 'NONE') {
                $job[$fieldName] = null;
            }
        }

        $this->logger->debug('Parsed data: ' . json_encode($job));

        if (!$this->testHostFilter($job['hostname'])) {
            $this->logger->debug('Skipping line due to host filter');
            return;
        }

        $job = array_merge(
            $job,
            $this->getResourceLists($job, $job['category'])
        );

        if (!$this->hasResource()) {
            throw new Exception('Resource name required');
        }

        $this->checkJobData($line, $job);

        $date = DateTime::createFromFormat('U', $job['end_time']);

        $job['clustername'] = $this->getResourceForNode(
            $job['hostname'],
            $date->format('Y-m-d')
        );

        $this->insertRow($job);
    }

    /**
     * Returns an array of resource attributes and values.
     *
     * @param array $job
     * @param string $category
     *
     * @return array
     */
    protected function getResourceLists(array $job, $category)
    {
        if ($category == '' || $category == 'NONE') {
            return array();
        }

        // Split on flags, but don't remove the flags.
        $parts = preg_split('/\s+?(?=-\w+)/', $category);

        $resourceLists = array();

        foreach ($parts as $part) {
            $flagAndValue = preg_split('/\s+/', $part, 2);

            $flag  = count($flagAndValue) > 0 ? $flagAndValue[0] : '';
            $value = count($flagAndValue) > 1 ? $flagAndValue[1] : '';

            $resources = null;

            if ($flag == '-l') {
                $resources = $this->parseResourceListOptions($value);
            } elseif ($flag == '-pe') {
                $resources = $this->parseParallelEnvironmentOptions($value);
            }

            if ($resources !== null) {
                $resourceLists = array_merge($resourceLists, $resources);
            }
        }

        return $resourceLists;
    }

    /**
     * Parse the resource list (-l) flag options.
     *
     * @param string $optionString
     *
     * @return array
     */
    private function parseResourceListOptions($optionString)
    {
        $options = explode(',', $optionString);

        $resources = array();

        foreach ($options as $option) {
            list($key, $value) = explode('=', $option, 2);

            if (!in_array($key, self::$resourceAttributes)) {
                $this->logger->debug("Unknown resource attribute: '$key'");
                continue;
            }

            if (isset(self::$columnFormats[$key])) {
                $format = self::$columnFormats[$key];
                $parseMethod = 'parse' . ucfirst($format);

                $parsedValue = null;

                try {
                    $parsedValue = $this->$parseMethod($value);
                } catch (Exception $e) {
                    $msg = "Failed to parse '$key' value '$value': "
                        . $e->getMessage();
                    $this->logger->err($msg);
                }

                $value = $parsedValue;
            }

            $resources['resource_list_' . $key] = $value;
        }

        return $resources;
    }

    /**
     * Parse the parallel environment (-pe) flag options.
     *
     * @param string $optionString
     *
     * @return array
     */
    private function parseParallelEnvironmentOptions($optionString)
    {
        list($env, $slots) = preg_split('/\s+/', $optionString);

        return array('resource_list_slots' => $slots);
    }

    /**
     * Parse a memory quantity string.
     *
     * @param string $memory The quantity of memory.
     *
     * @return int The quantity of memory in bytes or 0 if the specified
     *     quantity is "INFINITY".
     */
    private function parseMemory($memory)
    {
        $this->logger->debug("Parsing memory '$memory'");

        if (preg_match('/^(\d*\.?\d+|INFINITY)(\D+)?$/', $memory, $matches)) {
            $quantity = $matches[1];

            if ($quantity == 'INFINITY') {
                return 0;
            }

            // SGE uses bytes by default.
            $unit = isset($matches[2]) ? $matches[2] : 'b';

            return $this->scaleMemory($quantity, $unit);
        } else {
            throw new Exception("Unknown memory format: '$memory'");
        }
    }

    /**
     * Scale the memory from the given unit to bytes.
     *
     * @param float $quantity The memory quantity.
     * @param string $unit The memory unit (b, kb, mb, gb).
     *
     * @return int The quantity of memory in bytes.
     */
    private function scaleMemory($quantity, $unit)
    {
        $this->logger->debug("Scaling memory '$quantity', '$unit'");

        switch ($unit) {
            case 'b':
                $bytes = $quantity;
                break;
            case 'k':
                $bytes = $quantity * 1000;
                break;
            case 'K':
                $bytes = $quantity * 1024;
                break;
            case 'm':
                $bytes = $quantity * 1000 * 1000;
                break;
            case 'M':
                $bytes = $quantity * 1024 * 1024;
                break;
            case 'g':
                $bytes = $quantity * 1000 * 1000 * 1000;
                break;
            case 'G':
                $bytes = $quantity * 1024 * 1024 * 1024;
                break;
            default:
                throw new Exception("Unknown memory unit: '$unit'");
                break;
        }

        return (int)floor($bytes / 1024);
    }

    /**
     * @inheritdoc
     */
    protected function getIngestorQuery($ingestAll)
    {
        $sql = parent::getIngestorQuery($ingestAll);

        $sql .= '
            AND start_time != 0
            GROUP BY job_number, task_number
        ';

        return $sql;
    }
}
