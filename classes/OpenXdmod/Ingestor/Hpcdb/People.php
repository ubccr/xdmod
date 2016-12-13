<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class People extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {

        // For consistency, the long and short names must match those
        // used in \OpenXdmod\Ingestor\Hpcdb\PIPeople.
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    p.person_id AS id,
                    p.organization_id,
                    p.prefix,
                    COALESCE(TRIM(p.first_name), '') AS first_name,
                    COALESCE(TRIM(p.middle_name), '') AS middle_name,
                    COALESCE(TRIM(p.last_name), '') AS last_name,
                    p.department,
                    p.title,
                    em.email_address,
                    IF(
                        p.first_name IS NULL OR p.first_name = '',
                        p.last_name,
                        CONCAT(
                            p.last_name,
                            ', ',
                            p.first_name,
                            COALESCE(
                                CONCAT(' ', p.middle_name),
                                ''
                            )
                        )
                    ) AS long_name,
                    IF(
                        p.first_name IS NULL OR p.first_name = '',
                        p.last_name,
                        CONCAT(
                            p.last_name,
                            ', ',
                            SUBSTR(p.first_name, 1, 1)
                        )
                    ) AS short_name
                FROM hpcdb_people p
                JOIN hpcdb_organizations o
                    ON o.organization_id = p.organization_id
                LEFT OUTER JOIN hpcdb_email_addresses em
                    ON p.person_id = em.person_id
                ORDER BY long_name
            ",
            'person',
            array(
                'id',
                'organization_id',
                'prefix',
                'first_name',
                'middle_name',
                'last_name',
                'department',
                'title',
                'email_address',
                'long_name',
                'short_name',
                'order_id',
            ),
            array(
                "
                    INSERT INTO person (
                        id,
                        organization_id,
                        nsfstatuscode_id,
                        first_name,
                        last_name,
                        long_name,
                        short_name,
                        order_id
                    ) VALUES (
                        -1,
                        -1,
                        -1,
                        'Unknown',
                        'Unknown',
                        'Unknown',
                        'Unknown',
                        -1
                    ),
                    (
                        -2,
                        -2,
                        -2,
                        'unassociated',
                        'unassociated',
                        'unassociated',
                        'unassociated',
                        -2
                    )
                "
            )
        );
    }
}
