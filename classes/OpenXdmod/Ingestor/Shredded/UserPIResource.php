<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class UserPIResource extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    user_name,
                    pi_name,
                    resource_name
                FROM shredded_job
            ',
            'staging_user_pi_resource',
            array(
                'user_name',
                'pi_name',
                'resource_name',
            ),
            array(
                'user_name',
                'pi_name',
                'resource_name',
            )
        );
    }
}
