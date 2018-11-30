<?php
/**
 * OpenStack cloud event data shredder.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

class OpenStack extends aCloud
{
    /**
     * @inheritdoc
     */
    public function shredDirectory($directory)
    {
        return parent::shredDirectory($directory, ['jobs-cloud-ingest-openstack']);
    }
}
