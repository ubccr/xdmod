<?php

namespace OpenXdmod\Ingestor\Staging;

use ArrayIngestor;

class PeopleOnAccountsHistory extends ArrayIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            array(),
            'hpcdb_people_on_accounts_history',
            array(
                'state_id',
                'person_id',
                'resource_id',
                'account_id',
                'activity_time',
            )
        );
    }
}

