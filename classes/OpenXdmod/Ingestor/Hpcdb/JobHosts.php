<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Ingestor\Hpcdb;

use Exception;
use CCR\DB\iDatabase;
use CCR\DB\PDODB;
use Ingestor;
use Xdmod\HostListParser;

/**
 * Ingest host list for jobs.
 */
class JobHosts implements Ingestor
{

    /**
     * Destination database.
     *
     * @var iDatabase
     */
    private $destDb;

    /**
     * Source database.
     *
     * @var iDatabase
     */
    private $srcDb;

    /**
     * Start date of data to ingest.
     *
     * @var string
     */
    private $startDate;

    /**
     * End date of data to ingest.
     *
     * @var string
     */
    private $endDate;

    /**
     * Helper for parsing host lists.
     *
     * @var HostListParser
     */
    private $parser;

    /**
     * Host primary keys as stored in the destination database.
     *
     * @var array
     *
     * @see getHostIds
     * @see getHostKey
     */
    private $hostIdForKey;

    /**
     * Logger.
     *
     * @var \Log
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param iDatabase $destDb Destination database.
     * @param iDatabase $srcDb Source database.
     * @param string $startDate Ingestion start date (YYYY-MM-DD).
     * @param string $endDate Ingestion end date (YYYY-MM-DD).
     */
    public function __construct(
        iDatabase $destDb,
        iDatabase $srcDb,
        $startDate = null,
        $endDate = null
    ) {

        // Only allow the use of PDO database instances so that the
        // database handle can be used directly to create a prepared
        // statement that is used multiple times during ingestion.
        if (!$destDb instanceof PDODB) {
            throw new Exception('Destination database must be \CCR\DB\PDODB');
        }

        $this->destDb = $destDb;
        $this->srcDb  = $srcDb;

        $this->startDate = $startDate;
        $this->endDate   = $endDate;

        $this->hostIdForKey = $this->getHostIds();

        $this->parser = new HostListParser();

        $this->logger = \Log::singleton('null');
    }

    /**
     * @inheritdoc
     */
    public function ingest()
    {
        $this->logger->info(
            'Started ingestion for class: ' . get_class($this)
        );

        $timeStart = microtime(true);

        $srcQuery = 'SELECT job_id, resource_id, node_list FROM hpcdb_jobs';

        // If start and end times are provided, re-ingest all the jobs
        // in that date range.  Otherwise, only ingest new jobs.
        // Nothing is ever deleted by this ingestor.  Deletion is
        // handled by the jobs ingestor.
        if ($this->startDate !== null || $this->endDate !== null) {
            if ($this->startDate === null || $this->endDate === null) {
                throw new Exception('Both start and end date are needed.');
            }

            $srcQuery .= "
                WHERE end_time >= UNIX_TIMESTAMP('$this->startDate 00:00:00')
                   AND end_time <= UNIX_TIMESTAMP('$this->endDate 23:59:59')
            ";
        } else {
            // Only ingest new jobs.
            $sql = "SELECT MAX(job_id) AS max_id FROM jobhosts";
            list($row) = $this->destDb->query($sql);
            if ($row['max_id'] != null) {
                $srcQuery .= ' WHERE job_id > ' . $row['max_id'];
            }
        }

        $jobs = $this->srcDb->query($srcQuery);

        $jobCount = 0;
        $insertCount = 0;

        $this->destDb->beginTransaction();

        try {
            $insertSql = '
                INSERT INTO jobhosts SET
                    job_id = :job_id,
                    host_id = :host_id,
                    order_id = :order_id
            ';
            $stmt = $this->destDb->handle()->prepare($insertSql);

            foreach ($jobs as $job) {
                $jobCount++;
                $orderId = 0;

                $hosts = $this->parser->expandHostList($job['node_list']);

                foreach ($hosts as $host) {
                    $orderId++;

                    $hostId = $this->getHostId($job['resource_id'], $host);

                    $stmt->execute(
                        array(
                            'job_id'   => $job['job_id'],
                            'host_id'  => $hostId,
                            'order_id' => $orderId,
                        )
                    );

                    $insertCount++;
                }
            }

            $this->destDb->commit();
        } catch (Exception $e) {
            $this->destDb->rollBack();

            $this->logger->err(array(
                'message'    => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString(),
            ));
        }

        $timeEnd = microtime(true);
        $timeElapsed = $timeEnd - $timeStart;

        $message = sprintf(
            '%s: Rows Processed: %d (Time Taken: %01.2f s)',
            get_class($this),
            $jobCount,
            $timeElapsed
        );
        $this->logger->info($message);

        // NOTE: This is needed for the log summary.
        $this->logger->notice(array(
            'message'          => 'Finished ingestion',
            'class'            => get_class($this),
            'start_time'       => $timeStart,
            'end_time'         => $timeEnd,
            'records_examined' => $jobCount,
            'records_loaded'   => $insertCount,
        ));
    }

    /**
     * @inheritdoc
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
        $this->parser->setLogger($logger);
    }

    /**
     * Return an array of all the host ids in the database.
     *
     * @see getHostKey
     *
     * @return array
     */
    private function getHostIds()
    {
        $sql = 'SELECT id, resource_id, hostname FROM hosts';
        $hostList = $this->destDb->query($sql);

        $hostIds = array();

        foreach ($hostList as $host) {
            $key = $this->getHostKey($host['resource_id'], $host['hostname']);
            $hostIds[$key] = $host['id'];
        }

        return $hostIds;
    }

    /**
     * Get the database primary key for a host.
     *
     * @param int $resourceId The resource id referencing the
     *     "resourcefact" table.
     * @param string $name The name of the host.
     *
     * @return int
     */
    private function getHostId($resourceId, $name)
    {
        $key = $this->getHostKey($resourceId, $name);

        if (!isset($this->hostIdForKey[$key])) {
            $id = $this->insertHost($resourceId, $name);
            $this->hostIdForKey[$key] = $id;
        }

        return $this->hostIdForKey[$key];
    }

    /**
     * Insert a host into the database.
     *
     * @param int $resourceId The resource id referencing the
     *     "resourcefact" table.
     * @param string $name The name of the host.
     *
     * @return int The primary key for the host.
     */
    private function insertHost($resourceId, $name)
    {
        $sql = '
            INSERT INTO hosts SET
                resource_id = :resource_id,
                hostname = :name
        ';

        return $this->destDb->insert(
            $sql,
            array(
                'resource_id' => $resourceId,
                'name'        => $name,
            )
        );
    }

    /**
     * Construct a unique key for the given host.
     *
     * @param int $resourceId The resource id referencing the
     *     "resourcefact" table.
     * @param string $name The name of the host.
     *
     * @return string A unique value to identify the host.
     */
    private function getHostKey($resourceId, $name)
    {

        // Trim any trailing whitespace from the host name then convert
        // to lower case.  This is necessary to be consistent with how
        // the host names are compared in the database.
        //
        // See http://dev.mysql.com/doc/refman/5.5/en/char.html
        // All MySQL collations are of type PADSPACE.  This means that
        // all CHAR, VARCHAR, and TEXT values in MySQL are compared
        // without regard to any trailing spaces.
        return $resourceId . '-' . strtolower(rtrim($name));
    }
}
