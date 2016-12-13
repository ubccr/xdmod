<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class PIResource extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    pi_name,
                    resource_name
                FROM shredded_job
            ',
            'staging_pi_resource',
            array(
                'pi_name',
                'resource_name',
            ),
            array(
                'pi_name',
                'resource_name',
            )
        );
    }
}

