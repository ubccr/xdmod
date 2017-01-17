<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class SystemAccounts extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    system_account_id AS id,
                    person_id,
                    resource_id,
                    username,
                    ts,
                    uid
                FROM hpcdb_system_accounts
            ',
            'systemaccount',
            array(
                'id',
                'person_id',
                'resource_id',
                'username',
                'ts',
                'uid',
            )
        );
    }
}
