<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class UnionUserPIResource extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    user_name AS union_user_pi_name,
                    resource_name
                FROM shredded_job
                UNION
                SELECT DISTINCT
                    pi_name AS union_user_pi_name,
                    resource_name
                FROM shredded_job
            ',
            'staging_union_user_pi_resource',
            array(
                'union_user_pi_name',
                'resource_name',
            ),
            array(
                'union_user_pi_name',
                'resource_name',
            )
        );
    }
}

