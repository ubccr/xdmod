<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class ResourceAllocated extends PDODBMultiIngestor
{
    function __construct(
        $modwdb,
        $hpcdb,
        $start_date = '1997-01-01',
        $end_date = '2010-01-01'
    ) {
        parent::__construct(
            $modwdb,
            $hpcdb,
            array(),
            '
                SELECT
                    r.resource_id,
                    r.resource_name               AS name,
                    COALESCE(ra.start_date_ts, 0) AS start_date_ts,
                    ra.end_date_ts,
                    COALESCE(ra.percent, 100)     AS percent
                FROM hpcdb_resources r
                LEFT JOIN hpcdb_resource_allocated ra
                    ON r.resource_id = ra.resource_id
                ORDER BY r.resource_id, ra.start_date_ts
            ',
            'resource_allocated',
            array(
                'resource_id',
                'name',
                'start_date_ts',
                'end_date_ts',
                'percent',
            )
        );
    }
}
