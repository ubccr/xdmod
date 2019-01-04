<?php
/**
 * Generic cloud event data shredder.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use CCR\DB\iDatabase;

class Genericcloud extends aCloud
{
    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db){
        parent::__construct($db, ['jobs-cloud-ingest-generic']);
    }
}
