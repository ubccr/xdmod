<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class EmailAddresses extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    union_user_pi_id AS person_id,
                    ""               AS email_address
                FROM staging_union_user_pi
            ',
            'hpcdb_email_addresses',
            'person_id',
            array(
                'person_id',
                'email_address',
            )
        );
    }
}
