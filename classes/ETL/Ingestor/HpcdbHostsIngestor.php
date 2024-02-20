<?php
/**
 * Ingestor for Hosts from the HPCDB.
 *
 * This is done as a custom ingestor because the host node list needs
 * to be parsed and expanded.
 */
namespace ETL\Ingestor;

use ETL\EtlOverseerOptions;
use ETL\iAction;
use Xdmod\HostListParser;

use \PDO;
use \PDOException;

class HpcdbHostsIngestor extends pdoIngestor implements iAction
{
    /**
     * @see ETL\Ingestor\pdoIngestor::transform
     */
    public function transform(array $srcRecord, &$orderId)
    {
        $srcRecord = parent::transform($srcRecord, $orderId);
        $transformedRecord = array();
        /**
         * call HostListParser to expand host names and updates
         * this record to be able to be turned into something that
         * can then be used in hpcdb-xdw-ingest-jobs.job-hosts action to
         *  the job hosts table.
         * @see Xdmod\HostListParser
         */
        $parser = new HostListParser();
        $hosts = $parser->expandHostList($srcRecord[0]['hostnames']);
        $order_id = 0;
        foreach ($hosts as $host) {
            if(!empty($host)){
                $order_id++;
                $orderId++;
                $transformedRecord[] = array(
                    "job_id" => $srcRecord[0]['job_id'],
                    "resource_id" => $srcRecord[0]['resource_id'],
                    "hostnames" => $host,
                    "order_id" => $order_id,
                    "host_id" => (-1 * $order_id)
                );
            }
        }
        return $transformedRecord;
    }
}
