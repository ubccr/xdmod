<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class Organizations extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    organization_id AS id,
                    organization_abbrev AS abbrev,
                    organization_name AS name,
                    COALESCE(organization_abbrev, organization_name) AS short_name,
                    CASE
                        WHEN ISNULL(organization_abbrev) THEN organization_name
                        ELSE CONCAT(organization_abbrev, ' - ', organization_name)
                    END AS long_name
                FROM hpcdb_organizations
                ORDER BY long_name
            ",
            'organization',
            array(
                'id',
                'abbrev',
                'name',
                'short_name',
                'long_name',
                'order_id',
            )
        );
    }
}

