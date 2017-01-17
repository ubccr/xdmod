<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class PrincipalInvestigators extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    uup.union_user_pi_id AS person_id,
                    p.pi_id              AS request_id
                FROM staging_pi p
                JOIN staging_union_user_pi uup
                    ON p.pi_name = uup.union_user_pi_name
            ',
            'hpcdb_principal_investigators',
            array(
                'person_id',
                'request_id',
            ),
            array(
                'person_id',
                'request_id',
            )
        );
    }
}
