<?php

namespace OpenXdmod\Ingestor\Staging;

use ArrayIngestor;
use Xdmod\Config;

class ResourceTypes extends ArrayIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $config = Config::factory();

        $types = array(
                array(0, 'UNKNOWN', 'Unknown resource type'),
        );

        foreach ($config['resource_types'] as $type) {
            $types[] = array(
                $type['id'],
                $type['abbrev'],
                $type['description'],
            );
        }

        parent::__construct(
            $dest_db,
            $types,
            'hpcdb_resource_types',
            array(
                'type_id',
                'type_abbr',
                'type_desc',
            )
        );
    }
}

