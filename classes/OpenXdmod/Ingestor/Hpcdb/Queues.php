<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class Queues extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT DISTINCT
                    COALESCE(queue, 'NA') AS id,
                    resource_id
                FROM hpcdb_jobs j
                WHERE j.nodecount <> 0
                    AND j.nodecount IS NOT NULL
            ",
            'queue',
            array(
                'id',
                'resource_id',
            )
        );
    }
}
