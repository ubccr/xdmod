<?php

namespace OpenXdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class PI extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            'SELECT DISTINCT pi_name FROM shredded_job',
            'staging_pi',
            'pi_name',
            array(
                'pi_name',
            )
        );
    }
}
