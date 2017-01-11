<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class PeopleUnderPI extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT DISTINCT
                    pi.person_id AS principalinvestigator_person_id,
                    pac.person_id
                FROM
                    hpcdb_accounts ac,
                    hpcdb_requests req,
                    hpcdb_principal_investigators pi,
                    hpcdb_people_on_accounts_history pac
                WHERE
                    ac.account_id = req.account_id
                    AND req.request_id = pi.request_id
                    AND pac.account_id = ac.account_id
            ',
            'peopleunderpi',
            array(
                'principalinvestigator_person_id',
                'person_id',
             )
        );
    }
}
