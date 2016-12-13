<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class ServiceProvider extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT DISTINCT
                    o.organization_id,
                    COALESCE(o.organization_abbrev, o.organization_name) AS short_name,
                    CASE
                        WHEN ISNULL(o.organization_abbrev) THEN o.organization_name
                        ELSE CONCAT(o.organization_abbrev, ' - ', o.organization_name)
                    END AS long_name
                FROM
                    hpcdb_organizations o,
                    hpcdb_jobs j,
                    hpcdb_resources r
                WHERE
                    o.organization_id = r.organization_id
                    AND r.resource_id = j.resource_id
                ORDER BY long_name
            ",
            'serviceprovider',
            array(
                'organization_id',
                'long_name',
                'short_name',
                'order_id',
            )
        );
    }
}

