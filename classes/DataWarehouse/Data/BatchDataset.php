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
     * Maximum number of rows to return.
     *
     * @var int
     */
    private $limit;

    /**
     * Starting row index.
     *
     * @var int
     */
    private $offset;

    /**
     * The original setting of MYSQL_ATTR_USE_BUFFERED_QUERY before the query
     * is run. After the last row is fetched, the setting is set back to this
     * value.
     */
    private $originalBufferedQuerySetting;

    /**
     * @param RawQuery $query
     * @param XDUser $user
     * @param LoggerInterface $logger
     * @param array|null $fieldAliases
     * @param int|null $limit
     * @param int $offset
     */
    public function __construct(
        RawQuery $query,
        XDUser $user,
        LoggerInterface $logger = null,
        $fieldAliases = null,
        $limit = null,
        $offset = 0
    ) {
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
        $this->fields = $rawStatsConfig->getBatchExportFieldDefinitions(
            $query->getRealmName()
        );
        // If an array of field aliases has been provided,
        if (is_array($fieldAliases)) {
            // Validate the provided field aliases.
            $validFieldAliases = array_column($this->fields, 'alias');
            $invalidFieldAliases = array_diff(
                $fieldAliases,
                $validFieldAliases
            );
            if (count($invalidFieldAliases) > 0) {
                throw new Exception(
                    "Invalid fields specified: '"
                    . join("', '", $invalidFieldAliases)
                    . "'."
                );
            }
            // Filter out the fields whose aliases were not provided.
            $this->fields = array_filter(
                $this->fields,
                function ($field) use ($fieldAliases) {
                    return in_array($field['alias'], $fieldAliases);
                }
            );
            // Renumber the indexes.
            $this->fields = array_values($this->fields);
        }
        $this->limit = $limit;
        $this->offset = $offset;
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
        $this->originalBufferedQuerySetting = $this->dbh->handle()->getAttribute(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
        );
        $this->dbh->handle()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $this->logger->debug('Executing query');
        $this->sth = $this->query->getRawStatement($this->limit, $this->offset);
        $this->logger->debug(sprintf(
            'Raw query string: %s',
            $this->sth->queryString
        ));
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
            $this->dbh->handle()->setAttribute(
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                $this->originalBufferedQuerySetting
            );
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
