<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class Resource extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            'SELECT DISTINCT resource_name FROM shredded_job',
            'staging_resource',
            'resource_name',
            array(
                'resource_name',
            )
        );
    }
}

