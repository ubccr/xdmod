<?php
/**
 * Generic cloud event data shredder.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

class Genericcloud extends Cloud
{
    /**
     * @inheritdoc
     */
    public function shredDirectory($directory)
    {
        parent::shredDirectory($directory, ['jobs-cloud-ingest-eucalyptus']);
    }
}
