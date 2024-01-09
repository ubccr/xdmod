<?php
/**
 * Update database from version 10.0.0 to 10.5.0.
 */

namespace OpenXdmod\Migration\Version1050To1100;

use OpenXdmod\Migration\Etlv2Migration as AbstractEtlv2Migration;
use FilterListBuilder;

/**
 * Migrate databases from version 10.0.0 to 10.5.0.
 */
class Etlv2Migration extends AbstractEtlv2Migration
{
    public function execute()
    {
        parent::execute();

        $filterListBuilder = new FilterListBuilder();
        $filterListBuilder->setLogger($this->logger);
        $filterListBuilder->buildRealmLists('ResourceSpecifications');
    }
}
