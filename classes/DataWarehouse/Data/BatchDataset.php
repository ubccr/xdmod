<?php

namespace DataWarehouse\Data;

use CCR\DB;
use DataWarehouse\Query\RawQuery;
use Iterator;
use PDO;
use XDUser;
use CCR\Loggable

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
     * @var string[]
     */
    private $anonymousFields = [];

    /**
     */
    public function __construct(RawQuery $query, XDUser $user)
    {
        $this->query = $query;
        $this->docs = $query->getColumnDocumentation();
        $this->dbh = DB::factory($query->_db_profile);

        foreach ($this->docs as $doc) {
            switch ($doc['batch_export']) {
                case true:
                    $this->header[] = $doc['name'];
                    break;
                case 'anonymize':
                    $this->anonymousFields[] = $doc['name'];
                    $this->header[] = $doc['name'];
                    break;
                case false:
                default:
                    // Don't include fields by default.
                    break;
            }
        }

    }

    /**
     * Get the header row.
     *
     * @return string[]
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Get the current row from the data set.
     *
     * @return mixed[]
     */
    public current()
    {
        return $this->currentRow;
    }

    /**
     * Get the current row index.
     *
     * @return int
     */
    public key()
    {
        return $this->currentRowIndex;
    }

    /**
     * Advance iterator to the next row.
     *
     * Fetches the next row.
     */
    public next()
    {
        $this->currentRowIndex++;
        $this->currentRow = $this->getNextRow();
    }

    /**
     * Rewind the iterator to the beginning.
     *
     * Executes the underlying raw query.
     */
    public rewind()
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
    public valid()
    {
        return $this->currentRow !== false;
    }

    private function getNextRow()
    {
        $rawRow = $this->sth->fetch(PDO::FETCH_ASSOC);
    }
}
