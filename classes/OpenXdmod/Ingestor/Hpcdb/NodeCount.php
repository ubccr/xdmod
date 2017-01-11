<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class NodeCount extends PDODBMultiIngestor
{
    function __construct(
        $dest_db,
        $src_db,
        $start_date = null,
        $end_date = null
    ) {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT DISTINCT
                    nodecount as id,
                    nodecount as nodes
                FROM hpcdb_jobs
                WHERE nodecount <> 0 AND nodecount IS NOT NULL
            ",
            'nodecount',
            array(
                'id',
                'nodes'
            ),
            array(),
            'nodelete'
        );
    }
}
