<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class Allocations extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    al.allocation_id AS id,
                    al.resource_id,
                    al.account_id,
                    req.request_id,
                    pi.person_id AS principalinvestigator_person_id,
                    req.primary_fos_id AS fos_id
                FROM hpcdb_allocations al
                JOIN hpcdb_accounts acc
                    ON al.account_id = acc.account_id
                JOIN hpcdb_requests req
                    ON al.account_id = req.account_id
                JOIN hpcdb_principal_investigators pi
                    ON req.request_id = pi.request_id
            ",
            'allocation',
            array(
                'id',
                'resource_id',
                'account_id',
                'request_id',
                'principalinvestigator_person_id',
                'fos_id',
                'initial_end_date',
                'base_allocation',
                'remaining_allocation',
                'end_date',
                'end_date_ts',
                'allocation_type_id',
                'charge_number',
                'conversion_factor',
                'xd_su_per_hour',
                'long_name',
                'short_name',
                'order_id',
            )
        );
    }
}

