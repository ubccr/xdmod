<?php

namespace OpenXdmod\Ingestor\Staging;

use ArrayIngestor;
use Xdmod\Config;

class Organizations extends ArrayIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $config = Config::factory();

        $organizations = array(
         array(
            1,
            $config['organization']['name'],
            $config['organization']['abbrev'],
         ),
        );

        parent::__construct(
            $dest_db,
            $organizations,
            'hpcdb_organizations',
            array(
            'organization_id',
            'organization_name',
            'organization_abbrev',
            )
        );
    }
}
