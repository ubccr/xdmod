<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class SystemAccounts extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    r.resource_id          AS resource_id,
                    uup.union_user_pi_id   AS person_id,
                    uup.union_user_pi_name AS username,
                    UNIX_TIMESTAMP()       AS ts
                FROM staging_union_user_pi_resource uupr
                LEFT JOIN staging_union_user_pi uup
                    ON uupr.union_user_pi_name = uup.union_user_pi_name
                LEFT JOIN staging_resource r
                    ON uupr.resource_name = r.resource_name
            ',
            'hpcdb_system_accounts',
            array(
                'resource_id',
                'person_id',
            ),
            array(
                'resource_id',
                'person_id',
                'username',
                'ts',
            )
        );
    }
}
