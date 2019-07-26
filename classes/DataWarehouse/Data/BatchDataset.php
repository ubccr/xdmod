<?php

namespace DataWarehouse\Data;

use CCR\DB;
use CCR\Loggable;
use DataWarehouse\Query\RawQuery;
use Exception;
use Iterator;
use PDO;
use XDUser;
use Log;

/**
 * Data set for batch export queries.
 *
 * Implements the Iterator interface to provide unbuffered access to query
 * results.
 */
class BatchDataset extends Loggable implements Iterator
{
    /**
     * @var \DataWarehouse\Query\RawQuery
     */
    private $query;

    /**
     * @var \CCR\DB\iDatabase
     */
    private $dbh;

    /**
     * @var array[]
     */
    private $docs;

    /**
     * Statement handle for data set query.
     * @var \PDOStatement
     */
    private $sth;

    /**
     * Number of current row returned from database query.
     * @var int
     */
    private $currentRowIndex;

    /**
     * Current row returned from database query or false if no row is available.
     * @var array|false
     */
    private $currentRow = false;

    /**
     * @var string[]
     */
    private $header = [];

    /**
     * Fields that need to be anonymized.
     *
     * Keys correspond to the field name.
     *
     * @var array
     */
    private $anonymousFields = [];

    /**
     * @param mixed $name Description.
     * @param \XDUser $user
     * @param \Log $logger
     */
    public function __construct(RawQuery $query, XDUser $user, Log $logger = null)
    {
        parent::__construct($logger);

        $this->query = $query;
        $this->docs = $query->getColumnDocumentation();
        $this->dbh = DB::factory($query->_db_profile);

        foreach ($this->docs as $key => $doc) {
            $export = isset($doc['batch_export']) ? $doc['batch_export'] : false;
            $name = $doc['name'];

            if (isset($doc['units']) && $doc['units'] === 'ts') {
                $name .= ' (Timestamp)';
            }

            if ($export === true) {
                $this->header[$key] = $name;
            } elseif ($export === 'anonymize') {
                $this->header[$key] = $name . ' (Deidentified)';
                $this->anonymousFields[$key] = true;
            } elseif ($export === false) {
                // Skip field.
            } else {
                throw new Exception(sprintf(
                    'Unknown "batch_export" option %s',
                    var_export($export, true)
                ));
            }
        }

        $this->logger->debug(sprintf('Header: %s', json_encode($this->header)));
        $this->logger->debug(sprintf('Anonymous fields: %s', json_encode($this->anonymousFields)));
    }

    /**
     * Get the header row.
     *
     * @return string[]
     */
    public function getHeader()
    {
        return array_values($this->header);
    }

    /**
     * Get the current row from the data set.
     *
     * @return mixed[]
     */
    public function current()
    {
        return $this->currentRow;
    }

    /**
     * Get the current row index.
     *
     * @return int
     */
    public function key()
    {
        return $this->currentRowIndex;
    }

    /**
     * Advance iterator to the next row.
     *
     * Fetches the next row.
     */
    public function next()
    {
        $this->currentRowIndex++;
        $this->currentRow = $this->getNextRow();
    }

    /**
     * Rewind the iterator to the beginning.
     *
     * Executes the underlying raw query.
     */
    public function rewind()
    {
        $this->logger->debug('Executing query');
        $this->sth = $this->query->getRawStatement();
        // Set query to be unbuffered so results are not all loaded into memory.
        // @see ETL\Ingestor\pdoIngestor::multiDatabaseIngest()
        /*
        $this->sth->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $result = $this->dbh->query(
            "SHOW SESSION VARIABLES WHERE Variable_name = 'net_write_timeout'"
        );

        $currentTimeout = 0;
        if ( 0 != count($result) ) {
            $currentTimeout = $result[0]['Value'];
            $this->logger->debug("Current net_write_timeout = $currentTimeout");
        }

        $newTimeout = $numDestinationTables * $this->netWriteTimeoutSecondsPerFileChunk;

        if ( $newTimeout > $currentTimeout ) {
            $sql = sprintf('SET SESSION net_write_timeout = %d', $newTimeout);
            $this->executeSqlList(array($sql), $this->sourceEndpoint);
        }
         */

        $this->currentRowIndex = 1;
        $this->currentRow = $this->getNextRow();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->currentRow !== false;
    }

    /**
     */
    private function anonymizeField($field)
    {
        return md5($field);
    }

    /**
     */
    private function getNextRow()
    {
        $rawRow = $this->sth->fetch(PDO::FETCH_ASSOC);

        if ($rawRow === false) {
            return false;
        }

        $row = [];

        foreach (array_keys($this->header) as $key) {
            $row[] = isset($this->anonymousFields[$key])
                ? $this->anonymizeField($rawRow[$key])
                : $rawRow[$key];
        }

        return $row;
    }
}
