<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class PrincipalInvestigators extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    person_id,
                    request_id
                FROM hpcdb_principal_investigators
            ',
            'principalinvestigator',
            array(
                'person_id',
                'request_id',
            )
        );
    }
}

