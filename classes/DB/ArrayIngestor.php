<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
use CCR\DB\iDatabase;
use CCR\Log;
use Psr\Log\LoggerInterface;

class ArrayIngestor implements Ingestor
{
    protected $_dest_db = null;

    protected $_source_data = array();
    protected $_insert_table = null;
    protected $_insert_fields = null;

    protected $_post_ingest_update_statements = array();
    protected $_delete_statement = null;
    protected $_count_statement = null;

    protected $_logger = null;

    function __construct(
        iDatabase $dest_db,
        array $source_data = array(),
        $insert_table,
        array $insert_fields = array(),
        array $post_ingest_update_statements = array(),
        $delete_statement = null,
        $count_statement = null
    ) {
        $this->_dest_db =  $dest_db;

        $this->_source_data   = $source_data;
        $this->_insert_table  = $insert_table;
        $this->_insert_fields = $insert_fields;

        $this->_post_ingest_update_statements = $post_ingest_update_statements;
        $this->_delete_statement              = $delete_statement;

        $this->_logger = Log::singleton('xdconsole');
    }

    public function ingest()
    {
        $this->_logger->info('Started ingestion for class: ' . get_class($this));

        $time_start = microtime(true);

        $rowsAffected = 0;
        $sourceRows = count($this->_source_data);

        $this->_dest_db->handle()->beginTransaction();

        $this->_dest_db->handle()->prepare("SET FOREIGN_KEY_CHECKS = 0")->execute();

        if ($this->_delete_statement === null) {
            $this->_logger->debug("Truncating table '{$this->_insert_table}'");
            $this->_dest_db->handle()->prepare("TRUNCATE TABLE {$this->_insert_table}")->execute();
        } else {
            $this->_logger->debug("Delete statement: {$this->_delete_statement}");
            $this->_dest_db->handle()->prepare($this->_delete_statement)->execute();
        }

        $insertStatement = 'INSERT INTO ' . $this->_insert_table . ' ('
            . implode(', ', $this->_insert_fields) . ') VALUES ('
            . implode(', ', array_fill(0, count($this->_insert_fields), '?'))
            . ')';

        $this->_logger->debug("Insert statement: $insertStatement");
        $destStatementPrepared = $this->_dest_db->handle()->prepare($insertStatement);

        foreach ($this->_source_data as $srcRow) {
            try {
                $destStatementPrepared->execute($srcRow);
            } catch (PDOException $e) {
                $this->_logger->error($e->getMessage(),
                    [
                        'stacktrace' => $e->getTraceAsString(),
                        'source_row' => json_encode($srcRow)
                    ]
                );
            }
            $rowsAffected += $destStatementPrepared->rowCount();
        }

        foreach ($this->_post_ingest_update_statements as $updateStatement) {
            try {
                $this->_logger->debug("Post ingest update: $updateStatement");
                $this->_dest_db->handle()->prepare($updateStatement)->execute();
            } catch (PDOException $e) {
                $this->_logger->error($e->getMessage(), ['stacktrace' => $e->getTraceAsString()]);
                $this->_dest_db->handle()->rollback();
                return;
            }
        }

        $this->_dest_db->handle()->prepare("SET FOREIGN_KEY_CHECKS = 1")->execute();
        $this->_dest_db->handle()->commit();
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->_logger->notice('Finished ingestion',
            [
                'class'            => get_class($this),
                'records_examined' => $sourceRows,
                'records_loaded' => $rowsAffected,
                'start_time' => $time_start,
                'end_time' => $time_end,
                'duration' => number_format($time, 2) . ' s'
            ]
        );
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }
}

