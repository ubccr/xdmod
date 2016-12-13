<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class FieldOfScience extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    fos.field_of_science_id AS id,
                    fos.field_of_science_id AS fos_nsf_id,
                    fos.parent_id,
                    fos.description,
                    fos.abbrev              AS fos_nsf_abbrev,
                    h.directorate_id        AS directorate_fos_id
                FROM hpcdb_fields_of_science_hierarchy h
                JOIN hpcdb_fields_of_science fos
                    ON h.field_of_science_id = fos.field_of_science_id
            ',
            'fieldofscience',
            array(
                'id',
                'parent_id',
                'description',
                'fos_nsf_id',
                'fos_nsf_abbrev',
                'directorate_fos_id',
            )
        );
    }
}

