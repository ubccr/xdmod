<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class ResourceTypes extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    type_id AS id,
                    type_abbr AS abbrev,
                    type_desc AS description
                FROM hpcdb_resource_types
            ',
            'resourcetype',
            array(
                'id',
                'abbrev',
                'description',
            ),
            array(
                "
                    INSERT INTO resourcetype (
                        id,
                        abbrev,
                        description
                    ) VALUES (
                        0,
                        'UNK',
                        'Unknown'
                    ) ON DUPLICATE KEY
                        UPDATE abbrev = 'UNK', description = 'Unknown'
                "
            )
        );
    }
}

