<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class Requests extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    request_id AS id,
                    primary_fos_id,
                    account_id
                FROM hpcdb_requests r
            ',
            'request',
            array(
                'id',
                'primary_fos_id',
                'account_id',
            )
        );
    }
}
