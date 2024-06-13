<?php
/**
 * PBS/TORQUE shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;
use DateTime;
use DateInterval;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use Xdmod\PbsResourceParser;

class Pbs extends Shredder
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'shredded_job_pbs';

    /**
     * @inheritdoc
     */
    protected static $tablePkName = 'shredded_job_pbs_id';

    /**
     * Regular expression for PBS accounting log lines.
     *
     * @var string
     */
    protected static $linePattern = '|
        ^
        ( \d{2}/\d{2}/\d{4} )    # Date
        \s
        ( \d{2}:\d{2}:\d{2} )    # Time
        ;
        ( \w )                   # Event type
        ;
        ( [^;]+ )                # Job ID
        ;
        ( .* )                   # Parameters
    |x';

    /**
     * All the columns in the job table, excluding the primary key.
     *
     * @var array
     */
    protected static $columnNames = array(
        'job_id',
        'job_array_index',
        'host',
        'queue',
        'user',
        'groupname',
        'ctime',
        'qtime',
        'start',
        'end',
        'etime',
        'exit_status',
        'session',
        'requestor',
        'jobname',
        'owner',
        'account',
        'session_id',
        'error_path',
        'output_path',
        'exec_host',
        'resources_used_vmem',
        'resources_used_mem',
        'resources_used_walltime',
        'resources_used_nodes',
        'resources_used_cpus',
        'resources_used_gpus',
        'resources_used_cput',
        'resource_list_nodes',
        'resource_list_procs',
        'resource_list_neednodes',
        'resource_list_pcput',
        'resource_list_cput',
        'resource_list_walltime',
        'resource_list_ncpus',
        'resource_list_nodect',
        'resource_list_mem',
        'resource_list_pmem',
        'node_list',
    );

    /**
     * Columns that should be parsed and their expected format.
     *
     * @var array
     */
    protected static $columnFormats = array(
        'resources_used_vmem'     => 'memory',
        'resources_used_mem'      => 'memory',
        'resources_used_walltime' => 'time',
        'resources_used_cput'     => 'time',
        'resource_list_pcput'     => 'time',
        'resource_list_cput'      => 'time',
        'resource_list_walltime'  => 'time',
        'resource_list_mem'       => 'memory',
        'resource_list_pmem'      => 'memory',
    );

    /**
     * @inheritdoc
     *
     * NOTE: "wall_time" uses end - start since resources_used_walltime
     * is occasionally incorrect.
     */
    protected static $columnMap = array(
        'date_key'        => 'DATE(FROM_UNIXTIME(end))',
        'job_id'          => 'job_id',
        'job_array_index' => 'NULLIF(job_array_index, -1)',
        'job_id_raw'      => 'job_id',
        'job_name'        => 'jobname',
        'resource_name'   => 'host',
        'queue_name'      => 'queue',
        'user_name'       => 'user',
        'group_name'      => 'groupname',
        'account_name'    => 'account',
        'pi_name'         => 'groupname',
        'start_time'      => 'start',
        'end_time'        => 'end',
        'submission_time' => 'ctime',
        'eligible_time'   => 'etime',

        // Appending a colon to the exit code due to a bug (most likely
        // in PDODBMultiIngestor) that converts zero ("0") to the empty
        // string.
        'exit_code'       => 'CONCAT(CAST(exit_status AS CHAR), \':\')',
        'wall_time'       => 'GREATEST(CAST(end AS SIGNED) - CAST(start AS SIGNED), 0)',
        'wait_time'       => 'GREATEST(CAST(start AS SIGNED) - CAST(ctime AS SIGNED), 0)',
        'node_count'      => 'resources_used_nodes',
        'cpu_count'       => 'resources_used_cpus',
        'gpu_count'       => 'resources_used_gpus',
        'cpu_req'         => 'resource_list_ncpus',
        'mem_req'         => 'CAST(resource_list_mem AS CHAR)',
        'timelimit'       => 'resource_list_walltime',
        'node_list'       => 'node_list',
    );

    /**
     * @inheritdoc
     */
    protected static $dataMap = array(
        'job_id'          => 'job_id',
        'start_time'      => 'start',
        'end_time'        => 'end',
        'submission_time' => 'ctime',
        'walltime'        => 'resources_used_walltime',
        'nodes'           => 'resources_used_nodes',
        'cpus'            => 'resources_used_cpus',
    );

    /**
     * @var \Xdmod\PbsResourceParser
     */
    private $resourceParser;

    /**
     * Initialize resource parser.
     */
    public function __construct(iDatabase $db)
    {
        parent::__construct($db);
        $this->resourceParser = new PbsResourceParser();
    }

    /**
     * @inheritdoc
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        $date = $node = $type = null;

        $job = array();

        if (preg_match(self::$linePattern, $line, $matches)) {
            $date = preg_replace(
                '#^(\d{2})/(\d{2})/(\d{4})$#',
                '$3-$1-$2',
                $matches[1]
            );

            $type   = $matches[3];
            $jobId  = $matches[4];
            $params = $matches[5];
        } else {
            throw new Exception("Malformed PBS accounting line: '$line'");
        }

        // Ignore all non-"end" events.
        if ($type != 'E') {
            $this->logger->debug("Ignoring event type '$type'");
            return;
        }

        $jobIdData = $this->getJobId($jobId);
        $job['job_id'] = $jobIdData['job_id'];
        if ($jobIdData['job_array_index'] !== null) {
            $job['job_array_index'] = $jobIdData['job_array_index'];
        }

        $paramList = preg_split('/\s+/', $params);

        foreach ($paramList as $param) {
            if (strpos($param, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $param, 2);

            $key = strtolower(str_replace('.', '_', $key));

            if ($key == 'exec_host') {
                $data = $this->parseExecHost($value);

                // The first node in the host list is used for the host
                // filter feature.
                $node = $data['host_list'][0]['node'];

                // Build list of host names compatible with the
                // compressed host list format used by Slurm.  This list
                // is currently not compressed, but that could be
                // implemented in the future to reduce database storage
                // requirements.
                $nodes = array();
                foreach ($data['host_list'] as $hostData) {
                    $nodes[] = $hostData['node'];
                }
                $job['node_list'] = implode(',', array_unique($nodes));

                $job['resources_used_nodes'] = $data['node_count'];
                $job['resources_used_cpus']  = $data['cpu_count'];
            } elseif (isset(self::$columnFormats[$key])) {
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

                $job[$key] = $parsedValue;
            } elseif ($key === 'group') {
                $job['groupname'] = $value;
            } else {
                $job[$key] = $value;
            }
        }

        if (array_key_exists('resource_list_nodes', $job)) {
            $nodesData = $this->resourceParser->parseResourceListNodes($job['resource_list_nodes']);
            $job['resources_used_gpus'] = $this->resourceParser->getGpuCountFromResourceListNodes($nodesData);
        }

        // Special cases for SDSC Comet and other versions of PBS.
        if (!array_key_exists('resources_used_gpus', $job) || $job['resources_used_gpus'] === 0) {
            if (array_key_exists('resource_list_gpus', $job)) {
                $job['resources_used_gpus'] = $job['resource_list_gpus'];
            } elseif (array_key_exists('resource_list_ngpus', $job)) {
                $job['resources_used_gpus'] = $job['resource_list_ngpus'];
            } elseif (array_key_exists('resource_list_nodect', $job)) {
                $nodesData = $this->resourceParser->parseResourceListNodes($job['resource_list_nodect']);
                $job['resources_used_gpus'] = $this->resourceParser->getGpuCountFromResourceListNodes($nodesData);
            }
        }

        $this->logger->debug('Parsed data: ' . json_encode($job));

        if (!$this->testHostFilter($node)) {
            $this->logger->debug('Skipping line due to host filter');
            return;
        }

        $job['host'] = $this->getResource();

        foreach (array_keys($job) as $key) {
            if (!in_array($key, self::$columnNames)) {
                $this->logger->debug("Ignoring unknown attribute '$key'");
                unset($job[$key]);
            }
        }

        $this->checkJobData($line, $job);

        $this->insertRow($job);
    }

    /**
     * Determine the ID and job array index for a job.
     *
     * @param string $id The PBS id_string.
     *
     * @return array An array containing:
     *                   job_id: The job's ID.
     *                   job_array_index: The index of the job in a job array,
     *                       or null if job not in an array or data not found.
     */
    public function getJobId($id)
    {
        $this->logger->debug("Parsing id_string '$id'");

        // id_string may be formatted as
        // "sequence_number.hostname" or "sequence_number"
        $sequence = strtok($id, '.');

        $jobId = $index = null;

        // If the job is part of a job array the sequence number may be
        // formatted as "job_id[array_index]" or "job_id-array_index".
        // If the sequence number represents the entire job array it may
        // be formatted as "job_id[]".
        if (preg_match('/ ^ (\d+) \[ (\d+)? \] $ /x', $sequence, $matches)) {
            $jobId = $matches[1];
            if (isset($matches[2])) {
                $index = $matches[2];
            }
        } elseif (preg_match('/^(\d+)-(\d+)$/', $sequence, $matches)) {
            $jobId = $matches[1];
            $index = $matches[2];
        } elseif (preg_match('/^\d+$/', $sequence, $matches)) {
            $jobId = $sequence;
        } else {
            $this->logger->warning("Unknown id_string format: '$id'");
            $jobId = $sequence;
        }

        return array(
            'job_id'          => $jobId,
            'job_array_index' => $index,
        );
    }

    /**
     * Determine the number of nodes and cpus used by a job.
     *
     * @param string $hosts A list of hostnames.
     *
     * @return array
     *
     * @see parseHosts
     */
    protected function parseExecHost($hosts)
    {
        $hostList = $this->parseHosts($hosts);

        // Key is the node name, value is the number of cpus.
        $nodeCpus = array();

        foreach ($hostList as $host) {
            $node = $host['node'];

            if (isset($nodeCpus[$node])) {
                $nodeCpus[$node]++;
            } else {
                $nodeCpus[$node] = 1;
            }
        }

        $nodeCount = 0;
        $cpuCount = 0;

        foreach ($nodeCpus as $node => $cpus) {
            $nodeCount++;
            $cpuCount += $cpus;
        }

        return array(
            'host_list'  => $hostList,
            'node_count' => $nodeCount,
            'cpu_count'  => $cpuCount,
        );
    }

    /**
     * Parse a time string.
     *
     * @param string $time The time in HH:MM:SS format or number of seconds.
     *
     * @return int The number of seconds past midnight.
     */
    protected function parseTime($time)
    {
        $this->logger->debug("Parsing time '$time'");

        if (strpos($time, ':') !== false) {
            list($h, $m, $s) = explode(':', $time);
            return $h * 60 * 60 + $m * 60 + $s;
        } else {
            return $time;
        }
    }

    /**
     * Parse a memory quantity string.
     *
     * @param string $memory The quantity of memory.
     *
     * @return int The quantity of memory in bytes.
     */
    protected function parseMemory($memory)
    {
        $this->logger->debug("Parsing memory '$memory'");

        if (preg_match('/^(\d*\.?\d+)(\D+)?$/', $memory, $matches)) {
            $quantity = $matches[1];

            // PBS uses kilobytes by default.
            $unit = isset($matches[2]) ? $matches[2] : 'kb';

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
     * @return int
     */
    protected function scaleMemory($quantity, $unit)
    {
        $this->logger->debug("Scaling memory '$quantity', '$unit'");

        switch ($unit) {
            case 'b':
                return (int)floor($quantity);
                break;
            case 'kb':
                return (int)floor($quantity * 1024);
                break;
            case 'mb':
                return (int)floor($quantity * 1024 * 1024);
                break;
            case 'gb':
                return (int)floor($quantity * 1024 * 1024 * 1024);
                break;
            case 'tb':
                return (int)floor($quantity * 1024 * 1024 * 1024 * 1024);
                break;
            case 'w':
                return (int)floor($quantity * 8);
                break;
            case 'kw':
                return (int)floor($quantity * 8 * 1024);
                break;
            case 'mw':
                return (int)floor($quantity * 8 * 1024 * 1024);
                break;
            case 'gw':
                return (int)floor($quantity * 8 * 1024 * 1024 * 1024);
                break;
            case 'tw':
                return (int)floor($quantity * 8 * 1024 * 1024 * 1024 * 1024);
                break;
            default:
                throw new Exception("Unknown memory unit: '$unit'");
                break;
        }
    }

    /**
     * Parse a hosts string.
     *
     * Currently supported formats include:
     * - Host and CPU: host1/0+host1/1 (host1 with cpu0 and cpu1)
     * - Host and multiple CPUs: host1/0,1 (host1 with cpu0 and cpu1)
     * - Host and CPU range: host1/0-2 (host1 with cpu0, cpu1 and cpu2)
     * - PBS Pro: vnode1/0*2 (vnode1 with 2 CPUs with unique index 0)
     *
     * @param string $hosts A list of hostnames.
     *
     * @return array An array of node name and cpu id pairs.
     */
    protected function parseHosts($hosts)
    {
        $this->logger->debug("Parsing hosts '$hosts'");

        $parts = explode('+', $hosts);

        $hostList = array();

        foreach ($parts as $part) {
            list($host, $cpuList) = explode('/', $part);

            $cpuParts = explode(',', $cpuList);

            foreach ($cpuParts as $cpuPart) {
                $cpus = array();

                if (strpos($cpuPart, '-') !== false) {

                    // The "-" indicates this is a range of CPU indexes.
                    list($min, $max) = explode('-', $cpuPart, 2);
                    $cpus = range($min, $max);

                } elseif (strpos($cpuPart, '*') !== false) {

                    // The number to the left of the "*" is a unique
                    // index and the number on the right is a CPU count
                    // for that index.
                    list($index, $count) = explode('*', $cpuPart, 2);

                    // The PBS Pro format doesn't specify a CPU number,
                    // so the index is used as a prefix to guarantee
                    // uniqueness.
                    $cpus = array_map(
                        function ($cpu) use ($index) {
                            return $index . '.' . $cpu;
                        },
                        range(1, $count)
                    );
                } else {

                    // Single CPU.
                    $cpus = array($cpuPart);
                }

                foreach ($cpus as $cpu) {
                    $hostList[] = array(
                        'node' => $host,
                        'cpu'  => $cpu,
                    );
                }
            }
        }

        return $hostList;
    }

    /**
     * PBS accounting files are named in the YYYYMMDD format.  The file
     * for the current date is not included because it may be in use.
     *
     * @inheritdoc
     */
    protected function getDirectoryFilePaths($dir)
    {
        $maxDate = $this->getJobMaxDate();

        if ($maxDate == null) {
            $this->logger->debug('No maximum date found in job table.');

            // Return default path list with paths that don't end in
            // file name consisting of eight digits (YYYYMMDD) removed.
            return array_filter(
                parent::getDirectoryFilePaths($dir),
                function ($path) {
                    return preg_match('#/\d{8}$#', $path);
                }
            );
        }

        $this->logger->debug('Max date: ' . $maxDate);

        $now  = new DateTime('now');
        $date = new DateTime($maxDate);

        $oneDay = new DateInterval('P1D');

        $date->add($oneDay);

        $paths = array();

        while ($date->diff($now)->days > 0) {
            $path = $dir . '/' . $date->format('Ymd');

            $date->add($oneDay);

            if (!is_file($path)) {
                $this->logger->debug("Skipping missing file '$dir'");
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }
}
