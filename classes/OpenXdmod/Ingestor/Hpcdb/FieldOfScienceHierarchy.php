<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class FieldOfScienceHierarchy extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    field_of_science_id AS id,
                    description,
                    parent_id,
                    parent_description,
                    directorate_id,
                    directorate_description,
                    directorate_abbrev
                FROM hpcdb_fields_of_science_hierarchy
                ORDER BY description ASC
            ",
            'fieldofscience_hierarchy',
            array(
                'id',
                'description',
                'parent_id',
                'parent_description',
                'directorate_id',
                'directorate_description',
                'directorate_abbrev',
                'order_id',
            )
        );
    }
}
