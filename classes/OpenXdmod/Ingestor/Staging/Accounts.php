<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class Accounts extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    pi_id   AS account_id,
                    pi_name AS account_name
                FROM staging_pi
            ',
            'hpcdb_accounts',
            'account_id',
            array(
                'account_id',
                'account_name',
            )
        );
    }
}
