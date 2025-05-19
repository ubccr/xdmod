<?php
/**
 * Ingestor that synchronizes data between two tables.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB\PDODB;
use CCR\Log;
use Psr\Log\LoggerInterface;

class PDODBSynchronizingIngestor implements Ingestor
{

    protected $insertColumns;

    /**
     * The columns in the destination table.
     *
     * @var array
     */
    protected $insertColumns;

    /**
     * Destination database.
     *
     * @var PDODB
     */
    protected $destDb;

    /**
     * Source database.
     *
     * @var PDODB
     */
    protected $srcDb;

    /**
     * Source query to produce data to be inserted.
     *
     * @var string
     */
    protected $srcQuery;

    /**
     * Destination table name.
     *
     * @var string
     */
    protected $destTable;

    /**
     * Columns that uniquely identify a row being ingested.
     *
     * This can contain a single primary key or multiple columns that
     * are a unique key.
     *
     * @var string
     */
    protected $uniqColumns;

    /**
     * Names of columns used.
     *
     * @var array
     */
    protected $columnMap;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param PDODB $destDb The destination database.
     * @param PDODB $srcDb The source database.
     * @param string $srcQuery The query to generate data.
     * @param string $destTable The destination table name.
     * @param string|array $uniqColumns Either a single primary key
     *   column or an array of columns that can be used to check for
     *   duplicates.
     * @param array $insertColumns The columns in the destination table.
     */
    public function __construct(
        PDODB $destDb,
        PDODB $srcDb,
        $srcQuery,
        $destTable,
        $uniqColumns,
        array $insertColumns
    ) {
        if (is_array($uniqColumns)) {
            $this->uniqColumns = $uniqColumns;
        } else {
            $this->uniqColumns = array($uniqColumns);
        }

        foreach ($this->uniqColumns as $column) {
            if (!in_array($column, $insertColumns)) {
                $msg = "'$column' must be in the column list.";
                throw new Exception($msg);
            }
        }

        $this->destDb        = $destDb;
        $this->srcDb         = $srcDb;
        $this->srcQuery      = $srcQuery;
        $this->destTable     = $destTable;
        $this->insertColumns = $insertColumns;

        $this->logger = Log::singleton('null');
    }

    /**
     * Perform the ingestion.
     */
    public function ingest()
    {
        $this->logger->info('Started ingestion for class: ' . get_class($this));

        $timeStart = microtime();

        $this->logger->debug("Source query: {$this->srcQuery}");
        $srcStmt = $this->srcDb->handle()->prepare($this->srcQuery);
        $srcStmt->execute();

        $uniqColumn = $this->uniqColumns;
        $columns    = $this->insertColumns;

        $insertSql = 'INSERT INTO ' . $this->destTable . ' ('
            . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?'))
            . ')';
        $this->logger->debug("Insert statement: $insertSql");
        $insertStmt = $this->destDb->handle()->prepare($insertSql);

        $destKeys = $this->getDestinationUniqueKeys();

        $insertedRows = 0;
        $sourceRows   = 0;

        while ($row = $srcStmt->fetch(PDO::FETCH_ASSOC)) {
            $sourceRows++;

            $key = $this->getKeyFromRow($row);

            if (in_array($key, $destKeys)) {
                $this->logger->debug('Skipping row: ' . json_encode($row));
                continue;
            }

            $insertData = array_map(
                function ($column) use ($row) { return $row[$column]; },
                $columns
            );

            $this->logger->debug('Inserting: ' . json_encode($insertData));

            $insertStmt->execute($insertData);
            $insertedRows++;
        }

        $timeEnd = microTime();
        $time = $timeEnd - $timeStart;

        // NOTE: This is needed for the log summary.
        $this->logger->notice(array(
            'message'           => 'Finished ingestion',
            'class'             => get_class($this),
            'start_time'        => $timeStart,
            'end_time'          => $timeEnd,
            'records_examined'  => $sourceRows,
            'records_loaded'    => $insertedRows,
        ));
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Retrieve all the primary keys from the destination table.
     *
     * @return array
     */
    private function getDestinationUniqueKeys()
    {
        $columns = $this->uniqColumns;

        $sql = 'SELECT '
            . implode(', ', $columns)
            . ' FROM '
            . $this->destTable;

        $this->logger->debug("Unique key query: $sql");

        $rows = $this->destDb->query($sql);

        $keys = array_map(array($this, 'getKeyFromRow'), $rows);

        return $keys;
    }

    /**
     * Generate a key given a row from the source or destination.
     *
     * @param array $row A row from the database.
     *
     * @return string
     */
    private function getKeyFromRow(array $row)
    {
        $columns = $this->uniqColumns;

        // Get the key values from the row and trim any trailing
        // whitespace then convert to lower case.
        //
        // See http://dev.mysql.com/doc/refman/5.5/en/char.html
        // All MySQL collations are of type PADSPACE.  This means that
        // all CHAR, VARCHAR, and TEXT values in MySQL are compared
        // without regard to any trailing spaces.
        $keyValues = array_map(
            function ($column) use ($row) {
                return strtolower(trim($row[$column]));
            },
            $columns
        );

        $escapedKeyValues = array_map(
            function ($key) {
                return str_replace('-', '\-', $key);
            },
            $keyValues
        );

        return implode('-', $escapedKeyValues);
    }
}
