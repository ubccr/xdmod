<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class PeopleOnAccounts extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    poah.id,
                    poah.account_id,
                    poah.resource_id,
                    poah.person_id,
                    poah.state_id AS allocationstate_id,
                    poah.activity_time AS start_time,
                    (
                        SELECT MIN(poah2.activity_time) AS min
                        FROM hpcdb_people_on_accounts_history poah2
                        WHERE
                            poah2.account_id = poah.account_id
                            AND poah2.resource_id = poah.resource_id
                            AND poah2.person_id = poah.person_id
                            AND poah2.activity_time > poah.activity_time
                    ) AS end_time,
                    comments
                FROM hpcdb_people_on_accounts_history poah
            ',
            'peopleonaccount',
            array(
                'id',
                'account_id',
                'resource_id',
                'person_id',
                'allocationstate_id',
                'start_time',
                'end_time',
                'comments',
             )
        );
    }
}
