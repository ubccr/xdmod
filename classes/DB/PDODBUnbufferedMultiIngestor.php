<?php

use CCR\DB\PDODB;

class PDODBUnbufferedMultiIngestor Extends PDODBMultiIngestor
{
    public function __construct(
        PDODB $dest_db,
        PDODB $source_db,
        array $pre_ingest_update_statements,
        $source_query,
        $insert_table,
        array $insert_fields = array(),
        array $post_ingest_update_statements = array(),
        $delete_statement = null,
        $count_statement = null
    ) {
        if ($source_db->_db_engine !== 'mysql') {
            $msg = "Unsupported db engine '{$source_db->_db_engine}'";
            throw new Exception($msg);
        }

        if ($count_statement === null) {
            $count_statement = preg_replace(
                '/^.*SELECT\b.*?\bFROM\b/is',
                'SELECT COUNT(*) AS row_count FROM',
                $source_query
            );

            if ($count_statement === $source_query) {
                $msg = 'Failed to determine count query for source query '
                    . "'$source_query'";
                throw new Exception($msg);
            }
            elseif ($count_statement === null) {
                throw new Exception('Error occurred during preg_replace');
            }
        }

        parent::__construct(
            $dest_db,
            $source_db,
            $pre_ingest_update_statements,
            $source_query,
            $insert_table,
            $insert_fields,
            $post_ingest_update_statements,
            $delete_statement,
            $count_statement
        );
    }

    public function ingest()
    {
        $pdo = $this->_source_db->handle();
        $buffered = $pdo->getAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        parent::ingest();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
    }
}

