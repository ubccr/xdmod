<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class PIPeople extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {

        // For consistency, the long and short names must match those
        // used in \OpenXdmod\Ingestor\Hpcdb\People.
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT DISTINCT
                    p.person_id,
                    p.organization_id,
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
                FROM
                    hpcdb_people p,
                    hpcdb_organizations o,
                    hpcdb_principal_investigators pi
                WHERE
                    o.organization_id = p.organization_id
                    AND pi.person_id = p.person_id
                ORDER BY long_name ASC
            ",
            'piperson',
            array(
                'person_id',
                'organization_id',
                'long_name',
                'short_name',
                'order_id',
            ),
            array(
                "
                    INSERT INTO piperson (
                        person_id,
                        organization_id,
                        long_name,
                        short_name,
                        order_id
                    ) VALUES (
                        -1,
                        -1,
                        'Unknown',
                        'Unknown',
                        99999
                    )
                "
            )
        );
    }
}

