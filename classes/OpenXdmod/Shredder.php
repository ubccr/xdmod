<?php
/**
 * Shredder base class.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace OpenXdmod;

use CCR\Log;
use Configuration\XdmodConfiguration;
use Exception;
use PDOException;
use CCR\DB\iDatabase;
use PDODBMultiIngestor;
use Psr\Log\LoggerInterface;

class Shredder
{

    /**
     * The format name of the shredder.
     *
     * @var string
     */
    protected $format;

    /**
     * Name of the database table used to store shredded data.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var string
     */
    protected static $tableName = '';

    /**
     * Name of the primary key column of the database table used to
     * store shredded data.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var string
     */
    protected static $tablePkName = '';

    /**
     * The maximum primary key value at the time the shredder is
     * created.
     *
     * This value is used to determine what data needs to be ingested.
     *
     * @var int
     */
    protected $maxPk = 0;

    /**
     * Mapping from generic job table to resource manager specific job
     * table.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var array
     */
    protected static $columnMap = array();

    /**
     * Logger object.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Database connection.
     *
     * @var iDatabase
     */
    protected $db;

     /**
     * Host Filter for the file being shredded.
     *
     * @var string
     */
    protected $hostFilter;

    /**
     * Resource name for the file being shredded.
     *
     * @var string
     */
    protected $resource;

    /**
     * Resource information from configuration file.
     *
     * @var array
     */
    protected $resourceConfig;

    /**
     * The date that corresponds to the data currently being shredded.
     *
     * This is needed to determine the correct date to use with the node
     * map.
     *
     * @var string A date in YYYY-MM-DD format.
     */
    protected $dataDate;

    /**
     * Mapping from generic job names to resource manager specific job
     * keys or functions.  Used to error check parsed data.
     *
     * Keys should be "job_id", "start_time", "end_time",
     * "submission_time", "walltime", "nodes" and "cpus".  The "job_id"
     * key is required.  The rest are optional, but if they are missing,
     * the checkJobData function will not check be able to perform all
     * the possible checks.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @see \OpenXdmod\Shredder::checkJobData() Used in this method.
     *
     * @var array
     */
    protected static $dataMap = array();

    /**
     * Data from jobs that contain errors.
     *
     * @var array
     */
    protected $jobErrors = array();

    /**
     * Protected constructor to enforce factory pattern.
     *
     * @param iDatabase $db The database connection.
     */
    protected function __construct(iDatabase $db)
    {
        $this->db     = $db;
        $this->logger = Log::singleton('null');

        $classPath = explode('\\', strtolower(get_class($this)));
        $this->format = $classPath[count($classPath) - 1];

        $tableName   = static::$tableName;
        $tablePkName = static::$tablePkName;
        $sql  = "SELECT MAX($tablePkName) AS max_pk FROM $tableName";
        $rows = $this->db->query($sql);

        if (count($rows) > 0 && $rows[0]['max_pk'] != null) {
            $this->maxPk = $rows[0]['max_pk'];

            $this->logger->debug(
                "MAX($tableName.$tablePkName) = {$this->maxPk}"
            );
        }
    }

    /**
     * Factory method.
     *
     * @param string $format File format name.
     * @param iDatabase $db The database connection.
     *
     * @return Shredder
     */
    public static function factory($format, iDatabase $db)
    {
        $class = "OpenXdmod\\Shredder\\" . ucfirst(strtolower($format));

        if (!class_exists($class)) {
            throw new Exception("Class not found '$class'");
        }

        return new $class($db);
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger a Monolog Logger instance.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the host filter regex string for the files being shredded.
     *
     * @param string $hostFilter host regex filter.
     */
    public function setHostFilter($hostFilter)
    {
        $this->logger->debug("Setting host filter to '$hostFilter'");

        // Test the host filter to make sure it is a valid regular
        // expression.
        if (@preg_match($hostFilter, '') === false) {
            throw new Exception("Invalid host filter '$hostFilter'");
        }

        $this->hostFilter = $hostFilter;
    }

    /**
     * Return the host filter regex string.
     *
     * @return string The host regex filter.
     */
    public function getHostFilter()
    {
        return $this->hostFilter;
    }

    /**
     * Check if the host filter has been set.
     *
     * @return bool True if the host filter has been set.
     */
    public function hasHostFilter()
    {
        return isset($this->hostFilter);
    }

    /**
     * Test the host filter against a given host name.
     *
     * @param string $host The host name.
     *
     * @return bool True if the filter allows the host or the host
     *     filter has not been set.
     */
    public function testHostFilter($host)
    {
        if (!$this->hasHostFilter()) {
            return true;
        }

        $result = preg_match($this->getHostFilter(), $host);

        if ($result === false) {
            throw new Exception('Error applying host filter');
        } elseif ($result) {
            $this->logger->debug("Host filter allows '$host'");
            return true;
        } else {
            $this->logger->debug("Host filter disallows '$host'");
            return false;
        }
    }

    /**
     * Set the resource name for the files being shredded.
     *
     * @param string $resource The name of the resource.
     */
    public function setResource($resource)
    {
        $this->logger->debug("Setting resource to '$resource'");

        $this->resource       = $resource;
        $this->resourceConfig = $this->getResourceConfig($resource);

        if (isset($this->resourceConfig['pi_column'])) {
            $piCol = $this->resourceConfig['pi_column'];
            $this->logger->debug("Setting PI column to '$piCol'");
            $this->setPiColumn($piCol);
        }
    }

    /**
     * Return the name of the resource.
     *
     * @return string The name of the resource.
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Check if a resource name has been set.
     *
     * @return bool True if the resource name has been set.
     */
    public function hasResource()
    {
        return isset($this->resource);
    }

    /**
     * Set the column used to determine the PI.
     *
     * @param string $columnName
     */
    public function setPiColumn($columnName)
    {
        static::$columnMap['pi_name'] = $columnName;
    }

    /**
     * Shred files in a directory.
     *
     * @param string $dir The directory path.
     *
     * @return int The number of records shredded.
     */
    public function shredDirectory($dir)
    {
        $this->logger->notice("Shredding directory '$dir'");

        if (!is_dir($dir)) {
            $this->logger->err("'$dir' is not a directory");
            return false;
        }

        $paths = $this->getDirectoryFilePaths($dir);

        $recordCount = 0;
        $fileCount   = 0;

        foreach ($paths as $path) {
            $recordCount += $this->shredFile($path);
            $fileCount++;
        }

        $this->logger->notice("Shredded $fileCount files");
        $this->logger->notice("Shredded $recordCount records total");

        return $recordCount;
    }

    /**
     * Returns an array of paths to all the files in a directory that
     * should be shredded.
     *
     * @param string $dir The directory path.
     *
     * @return array
     */
    protected function getDirectoryFilePaths($dir)
    {
        $files = scandir($dir);

        $paths = array();

        foreach ($files as $file) {
            if (strpos($file, '.') === 0) {
                $this->logger->debug("Skipping hidden file '$file'");
                continue;
            }

            $paths[] = $dir . '/' . $file;
        }

        return $paths;
    }

    /**
     * Shred a file.
     *
     * @param string $file The file path.
     *
     * @return int The number of records shredded.
     */
    public function shredFile($file)
    {
        $this->logger->notice("Shredding file '$file'");

        if (!is_file($file)) {
            $this->logger->err("'$file' is not a file");
            return false;
        }

        $fh = fopen($file, 'r');

        if ($fh === false) {
            throw new Exception("Failed to open file '$file'");
        }

        $recordCount = 0;
        $duplicateCount = 0;

        $this->logger->info('Starting database transaction');
        $this->db->beginTransaction();

        try {
            $lineNumber = 0;

            while (($line = fgets($fh)) !== false) {
                $lineNumber++;

                // Remove trailing whitespace.
                $line = rtrim($line);

                // Remove control characters.
                $line = preg_replace('/[\x00-\x1F\x7F]/', '', $line);

                // skip empty lines
                if ($line === '') {
                    $this->logger->debug([
                        'message'     => 'Skipping blank line',
                        'file'        => $file,
                        'line_number' => $lineNumber
                    ]);
                    continue;
                }

                $recordCount++;

                try {
                    $this->shredLine($line);
                } catch (PDOException $e) {

                    // Ignore duplicate key errors.
                    if ($e->getCode() == 23000) {
                        $msg = 'Skipping duplicate data: ' . $e->getMessage();
                        $this->logger->debug(array(
                            'message'     => $msg,
                            'file'        => $file,
                            'line_number' => $lineNumber,
                            'line'        => $line,
                        ));
                        $duplicateCount++;
                        continue;
                    } else {
                        throw $e;
                    }
                }
            }

            $this->logger->info('Committing database transaction');
            $this->db->commit();
        } catch (Exception $e) {
            $this->logger->info('Rolling back database transaction');
            $this->db->rollBack();

            // Close file handle, but don't throw an exception if it
            // fails since an exception will be thrown below.
            fclose($fh);

            $msg = sprintf(
                'Failed to shred line %d of file %s "%s": %s',
                $lineNumber,
                $file,
                $line,
                $e->getMessage()
            );

            throw new Exception($msg, 0, $e);
        }

        if (fclose($fh) === false) {
            throw new Exception("Failed to close file '$file'");
        }

        $this->logger->notice("Shredded $recordCount records");

        if ($duplicateCount > 0) {
            $msg = "Skipped $duplicateCount duplicate records";
            $this->logger->info($msg);
        }

        return $recordCount;
    }

    /**
     * Shred a line from a log file and insert the data into the
     * database.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @param string $line A single line from a log file.
     */
    public function shredLine($line)
    {
        throw new Exception('Shredder subclass must implement shredLine');
    }

    /**
     * Insert a single row into the database.
     *
     * @param array $values The values to insert where the array keys
     *   correspond to the column names.
     */
    protected function insertRow($values)
    {
        $sql = $this->createInsertStatement(
            static::$tableName,
            array_keys($values)
        );

        $this->logger->debug("Insert statement: $sql");

        $this->db->insert($sql, array_values($values));
    }

    /**
     * Create a SQL statement with
     *
     * @param string $table The name of the table to insert into.
     * @param array $columns The table column names.
     *
     * @return string A SQL insert statement.
     */
    protected function createInsertStatement($table, array $columns)
    {
        $sql = "INSERT INTO $table ("
            . implode(', ', $columns)
            . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?'))
            . ')';

        return $sql;
    }

    /**
     * Truncate the shredder data table.
     */
    public function truncate()
    {
        $tableName = static::$tableName;
        $this->logger->info("Truncating table '$tableName'");
        $this->db->execute("TRUNCATE $tableName");
        $this->maxPk = 0;
    }

    /**
     * Creates a ingestor to populate the generic job table.
     *
     * @return Ingestor Description.
     */
    public function getJobIngestor($ingestAll = false)
    {
        $this->logger->debug('Creating ingestor');

        $sourceQuery     = $this->getIngestorQuery($ingestAll);
        $insertFields    = array_keys(static::$columnMap);
        $deleteStatement = $this->getIngestorDeleteStatement($ingestAll);

        $insertFields[] = 'source_format';

        $this->logger->debug("Ingestor source query: $sourceQuery");

        $this->logger->debug(
            'Ingestor insert fields: ' . implode(', ', $insertFields)
        );

        if ($deleteStatement != 'nodelete') {
            $this->logger->debug(
                "Ingestor delete statement: $deleteStatement"
            );
        } else {
            $this->logger->debug('No ingestor delete statement');
        }

        $ingestor = new PDODBMultiIngestor(
            $this->db,
            $this->db,
            array(),
            $sourceQuery,
            'shredded_job',
            $insertFields,
            array(),
            $deleteStatement
        );

        $ingestor->setLogger($this->logger);

        return $ingestor;
    }

    /**
     * Creates a SQL query for use by the job ingestor.
     *
     * @param bool $ingestAll True if the query should select all data
     *   in the table and not just new data.
     *
     * @return string
     */
    protected function getIngestorQuery($ingestAll)
    {
        $columns = array();

        foreach (static::$columnMap as $key => $value) {
            if ($key === $value) {
                $columns[] = $value;
            } else {
                $columns[] = "$value AS $key";
            }
        }

        $columns[] = "'{$this->format}' AS source_format";

        $sql = 'SELECT ' . implode(', ', $columns)
            . ' FROM ' . static::$tableName;

        if ($ingestAll) {
            $sql .= ' WHERE 1 = 1';
        } else {
            $sql .= ' WHERE ' . static::$tablePkName . ' > ' . $this->maxPk;
        }

        return $sql;
    }

    /**
     * Creates a SQL delete statement for use by the job ingestor.
     *
     * @return string
     */
    protected function getIngestorDeleteStatement($ingestAll)
    {
        if ($ingestAll) {
            return 'TRUNCATE shredded_job';
        } else {
            return 'nodelete';
        }
    }

    /**
     * Find and return the maximum end date of all shredded job data.
     *
     * @return string A date formatted as YYYY-MM-DD.
     */
    public function getJobMaxDate()
    {
        $sql = "
            SELECT DATE_FORMAT(MAX(date_key), '%Y-%m-%d') AS max_date
            FROM shredded_job
        ";

        $params = array();

        if ($this->hasResource()) {
            $sql .= ' WHERE resource_name = :resource';
            $params['resource'] = $this->getResource();
        }

        $this->logger->info('Querying for maximum end date');
        $this->logger->debug('Query: ' . $sql);

        list($row) = $this->db->query($sql, $params);

        return $row['max_date'];
    }

    /**
     * Find and return the maximum end date and time of all shredded job
     * data.
     *
     * @return string A datetime formatted as YYYY-MM-DD HH:MM:SS.
     */
    public function getJobMaxDateTime()
    {
        $sql = "
            SELECT
                DATE_FORMAT(
                    FROM_UNIXTIME(MAX(end_time)),
                    '%Y-%m-%d %H:%i:%s'
                ) AS max_datetime
            FROM shredded_job
        ";

        $params = array();

        if ($this->hasResource()) {
            $sql .= ' WHERE resource_name = :resource';
            $params['resource'] = $this->getResource();
        }

        $this->logger->info('Querying for maximum end datetime');
        $this->logger->debug('Query: ' . $sql);

        list($row) = $this->db->query($sql, $params);

        return $row['max_datetime'];
    }

    /**
     * Check job data for consistency.
     *
     * @param string $input The input used to generate the job data.
     * @param array $data Job data.
     */
    protected function checkJobData($input, array &$data)
    {
        $keys = array(
            'job_id',
            'start_time',
            'end_time',
            'submission_time',
            'walltime',
            'nodes',
            'cpus',
        );

        // Create array with the generic keys used in $dataMap.
        $dataMap = static::$dataMap;
        $mappedData = array();
        foreach ($keys as $key) {
            if (isset($dataMap[$key])) {
                if (!array_key_exists($dataMap[$key], $data)) {
                    $msg = "Missing data for '{$dataMap[$key]}'";
                    $this->logger->debug($msg);
                    $mappedData[$key] = null;
                } else {
                    $mappedData[$key] = $data[$dataMap[$key]];
                }
            }
        }

        $errorMessages = array();

        if (isset($dataMap['start_time']) && isset($dataMap['end_time'])) {
            list($valid, $messages) = $this->checkJobTimes(
                $mappedData['start_time'],
                $mappedData['end_time'],
                $mappedData['walltime']
            );

            if (!$valid) {
                $errorMessages = array_merge($errorMessages, $messages);

                if (isset($dataMap['walltime'])) {
                    $times = $this->fixJobTimes(
                        $mappedData['start_time'],
                        $mappedData['end_time'],
                        $mappedData['walltime']
                    );

                    if ($times !== null) {
                        list(
                            $data[$dataMap['start_time']],
                            $data[$dataMap['end_time']],
                            $data[$dataMap['walltime']]
                        ) = $times;
                    }
                }
            }
        }

        if (isset($dataMap['nodes']) && isset($dataMap['cpus'])) {
            list($valid, $messages) = $this->checkNodesAndCpus(
                $mappedData['nodes'],
                $mappedData['cpus']
            );
            if (!$valid) {
                $errorMessages = array_merge($errorMessages, $messages);
            }
        }

        if (count($errorMessages) > 0) {
            $this->logJobError(array(
                'job_id'   => $mappedData['job_id'],
                'input'    => $input,
                'messages' => $errorMessages,
            ));
        }
    }

    /**
     * Check job times for validity.
     *
     * @param int $startTime Job start time in epoch format.
     * @param int $endTime Job start time in epoch format.
     * @param int $walltime Job wall time in seconds.
     *
     * @return array First value in the array is a bool indicating the
     *   data is valid or not.  The second value is an array of error
     *   messages if the data is not valid.
     */
    protected function checkJobTimes($startTime, $endTime, $walltime)
    {
        $valid = true;
        $errorMessages = array();

        $valueForTime = array(
            'start time' => $startTime,
            'end time'   => $endTime,
            'wall time'  => $walltime,
        );
        foreach ($valueForTime as $time => $value) {
            if ($value === null) {
                $this->logger->debug("Missing $time.");
                $valid = false;

                // Don't add missing wall times to the list of error
                // messages since we prefer to calculate it using the
                // start and end times.
                if ($time == 'wall time') {
                    continue;
                }

                $errorMessages[] = "Job $time is missing.";
            }
        }

        if ($startTime !== null && $startTime == 0) {
            $this->logger->debug('Found 0 start time.');
            $valid = false;
            $errorMessages[] = 'Job start time is 0.';
        }

        if ($endTime !== null && $endTime == 0) {
            $this->logger->debug('Found 0 end time.');
            $valid = false;
            $errorMessages[] = 'Job end time is 0.';
        }

        if (
            $startTime !== null
            && $endTime !== null
            && $startTime > $endTime
        ) {
            $this->logger->debug('Found start time after end time.');
            $errorMessages[] = 'Job start time as after job end time.';
            $valid = false;
        }

        return array($valid, $errorMessages);
    }

    /**
     * Attempt to correct invalid job times.
     *
     * @param int $startTime Job start time in epoch format.
     * @param int $endTime Job start time in epoch format.
     * @param int $walltime Job wall time in seconds.
     *
     * @return mixed An array containing the corrected start, end and
     *   wall times.  Or null if the times cannot be corrected.
     */
    protected function fixJobTimes($startTime, $endTime, $walltime)
    {
        $this->logger->debug('Attempting to fix job times.', array(
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'walltime'   => $walltime,
        ));

        // Must have at least two of the three values to determine the
        // third.
        $invalidCount = 0;
        if ($startTime == 0)    { $invalidCount++; }
        if ($endTime   == 0)    { $invalidCount++; }
        if ($walltime === null) { $invalidCount++; }

        if ($invalidCount > 1) {
            $this->logger->err(array(
                'message'    => 'Failed to correct job times',
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'walltime'   => $walltime,
            ));

            return null;
        }

        if ($walltime === null) {
            $walltime = $endTime - $startTime;
            if ($walltime < 0) { $walltime = 0; }
            $this->logger->debug("Setting wall time to $walltime");
        }

        if ($startTime == 0) {
            $startTime = $endTime - $walltime;
            $this->logger->debug("Setting start time to $startTime");
        }

        if ($endTime == 0) {
            $endTime = $startTime + $walltime;
            $this->logger->debug("Setting end time to $endTime");
        }

        if ($startTime > $endTime) {

            // Assume the end time is correct.
            $startTime = $endTime - $walltime;
            $this->logger->debug("Setting start time to $startTime");
        }

        return array($startTime, $endTime, $walltime);
    }

    /**
     * Check job node and cpu counts for validity.
     *
     * @param int $nodes Node count.
     * @param int $cpus Cpu count.
     *
     * @return array First value in the array is a bool indicating the
     *   data is valid or not.  The second value is an array or error
     *   messages if the data is not valid.
     */
    protected function checkNodesAndCpus($nodes, $cpus)
    {
        $valid = true;
        $errorMessages = array();

        if ($nodes == 0) {
            $this->logger->debug('Found 0 node count.');
            $valid = false;
            $errorMessages[] = 'Job node count is 0.';
        }

        if ($cpus == 0) {
            $this->logger->debug('Found 0 cpu count.');
            $valid = false;
            $errorMessages[] = 'Job cpu count is 0.';
        }

        if ($nodes > $cpus) {
            $this->logger->debug('Found node count greater than cpu count.');
            $errorMessages[]
                = "Job node count greater than cpu count ($nodes > $cpus).";
            $valid = false;
        }

        return array($valid, $errorMessages);
    }

    /**
     * Attempt to correct invalid node and cpu counts..
     *
     * @param int $nodes Node count.
     * @param int $cpus Cpu count.
     *
     * @return array The corrected node and cpu counts.
     */
    protected function fixNodesAndCpus($nodes, $cpus)
    {
        $this->logger->debug('Attempting to node and cpu counts.', array(
            'nodes' => $nodes,
            'cpus'  => $cpus,
        ));

        if ($nodes == 0 && $cpus == 0) {
            $nodes = 1;
            $cpus = 1;
            $this->logger->debug("Setting node count to $nodes");
            $this->logger->debug("Setting cpu count to $cpus");
        }

        if ($cpus < $nodes) {
            $cpus = $nodes;
            $this->logger->debug("Setting cpu count to $cpus");
        }

        return array($nodes, $cpus);
    }

    /**
     * Log a job data error.
     *
     * @param array $jobInfo
     */
    protected function logJobError(array $jobInfo)
    {
        $this->jobErrors[] = $jobInfo;
    }

    /**
     * Check if the shredder has logged any job data errors
     *
     * @return bool True if job data errors have been logged.
     */
    public function hasJobErrors()
    {
        return count($this->jobErrors) > 0;
    }

    /**
     * Write job data errors to a file.
     *
     * @param string $file The path of a file to write to.
     */
    public function writeJobErrors($file)
    {
        $this->logger->debug("Opening file '$file'");
        $fh = fopen($file, 'w+');

        if ($fh === false) {
            throw new Exception("Failed to open file '$file'");
        }

        $this->logger->debug('Writing to file');
        fwrite($fh, str_repeat('=', 72) . "\n");
        fwrite($fh, 'Shredder end time: ' . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "Resource: {$this->resource}\n");
        fwrite($fh, "Format: {$this->format}\n");

        // TODO: Refactor to display input format for all resource
        //       managers.
        if ($this->format == 'slurm') {
            fwrite(
                $fh,
                'Input format: ' . implode('|', $this->getFieldNames()) . "\n"
            );
        }

        fwrite($fh, "\n");

        foreach ($this->jobErrors as $err) {
            fwrite($fh, str_repeat('-', 72) . "\n");

            fwrite($fh, "Input:\n{$err['input']}\n\n");

            foreach ($err['messages'] as $message) {
                fwrite($fh, "$message\n");
            }

            fwrite($fh, "\n");
        }
    }

    /**
     * Return the config data for a given resource.
     *
     * @param string $name The resource name.
     *
     * @return array The resource configuration.
     */
    protected function getResourceConfig($name)
    {
        $resources = XdmodConfiguration::assocArrayFactory(
            'resources.json',
            CONFIG_DIR,
            $this->logger
        );

        foreach ($resources as $resource) {
            if ($resource['resource'] === $name) {
                return $resource;
            }
        }

        $file = implode(
            DIRECTORY_SEPARATOR,
            array(
                CONFIG_DIR,
                'resources.json'
            )
        );

        throw new Exception("No config found for '$name' in '$file'");
    }
}

