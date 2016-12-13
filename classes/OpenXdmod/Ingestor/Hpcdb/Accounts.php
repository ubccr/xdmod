<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class Accounts extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    account_id   AS id,
                    ''           AS charge_number,
                    -1           AS granttype_id,
                    account_name AS short_name,
                    account_name AS long_name
                FROM hpcdb_accounts
            ",
            'account',
            array(
                'id',
                'charge_number',
                'granttype_id',
                'short_name',
                'long_name',
            ),
            array(
                "
                    INSERT INTO account (
                        id,
                        parent_id,
                        charge_number,
                        creator_organization_id,
                        granttype_id,
                        long_name,
                        short_name,
                        order_id
                    ) VALUES (
                        -1,
                        NULL,
                        'Unknown',
                        NULL,
                        -1,
                        'Unknown Project',
                        'Unknown Project',
                        -1
                    )
                "
            )
        );
    }
}
