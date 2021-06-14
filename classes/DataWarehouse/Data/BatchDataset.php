<?php

namespace DataWarehouse\Data;

use CCR\DB;
use CCR\Loggable;
use DataWarehouse\Query\RawQuery;
use Exception;
use Iterator;
use PDO;
use Psr\Log\LoggerInterface;
use XDUser;
use xd_utilities;

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
     * Export field definitions.
     *
     * @var array[]
     */
    private $fields = [];

    /**
     * Salt used during deidentification.
     *
     * @var string
     */
    private $hashSalt = '';

    /**
     * Cache for hashed values.
     *
     * @var array
     */
    private $hashCache = [];

    /**
     * @param mixed $name Description.
     * @param \XDUser $user
     * @param LoggerInterface $logger
     */
    public function __construct(RawQuery $query, XDUser $user, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        $this->query = $query;
        $this->dbh = DB::factory($query->_db_profile);

        try {
            $this->hashSalt = xd_utilities\getConfiguration(
                'data_warehouse_export',
                'hash_salt'
            );
        } catch (Exception $e) {
            $this->logger->warning('data_warehouse_export hash_salt is not set');
        }

        $rawStatsConfig = RawStatisticsConfiguration::factory();
        $this->fields = $rawStatsConfig->getBatchExportFieldDefinitions($query->getRealmName());
    }

    /**
     * Get the header row.
     *
     * @return string[]
     */
    public function getHeader()
    {
        return array_map(
            function ($field) {
                return $field['display'];
            },
            $this->fields
        );
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
        $this->logger->debug(sprintf(
            'Raw query string: %s',
            $this->sth->queryString
        ));
        $this->logger->debug(sprintf('Row count: %s', $this->sth->rowCount()));
        $this->currentRowIndex = 1;
        $this->currentRow = $this->getNextRow();
    }

    /**
     * Is this iterator valid?
     *
     * @return bool
     */
    public function valid()
    {
        return $this->currentRow !== false;
    }

    /**
     * Anonymize a field.
     *
     * @param string $field
     * @return string
     */
    private function anonymizeField($field)
    {
        if (array_key_exists($field, $this->hashCache)) {
            return $this->hashCache[$field];
        }

        $hash = sha1($field . $this->hashSalt);
        $this->hashCache[$field] = $hash;

        return $hash;
    }

    /**
     * Get the next row of data.
     *
     * @return array
     */
    private function getNextRow()
    {
        $rawRow = $this->sth->fetch(PDO::FETCH_ASSOC);

        if ($rawRow === false) {
            return false;
        }

        $row = [];

        foreach ($this->fields as $field) {
            $key = $field['alias'];
            $row[] = $field['anonymize']
                ? $this->anonymizeField($rawRow[$key])
                : $rawRow[$key];
        }

        return $row;
    }
}
