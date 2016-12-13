<?php

namespace OpenXdmod\Ingestor\Staging;

use DateTime;
use ArrayIngestor;
use Xdmod\Config;

class ResourceAllocated extends ArrayIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $sql = 'SELECT resource_id, resource_name FROM staging_resource';
        $rows = $src_db->query($sql);

        $idForResource = array();

        foreach ($rows as $row) {
            $idForResource[$row['resource_name']] = $row['resource_id'];
        }

        $resourceSpecs = array();

        $config = Config::factory();

        foreach ($config['resource_specs'] as $spec) {
            $resource = $spec['resource'];

            # Skip resources that aren't in the database.
            if (!array_key_exists($resource, $idForResource)) {
                continue;
            }

            $id = $idForResource[$resource];

            if (array_key_exists('start_date', $spec)) {
                $date = new DateTime($spec['start_date']);
                $startDateTs = $date->getTimestamp();
            } else {
                $startDateTs = 0;
            }

            if (array_key_exists('end_date', $spec)) {
                $date = new DateTime($spec['end_date']);
                $date->setTime(23, 59, 59);
                $endDateTs = $date->getTimestamp();
            } else {
                $endDateTs = null;
            }

            $percentAlloc
                = array_key_exists('percent_allocated', $spec)
                ? $spec['percent_allocated']
                : 100;

            $resourceSpecs[] = array(
                $id,
                $startDateTs,
                $endDateTs,
                $percentAlloc,
            );
        }

        parent::__construct(
            $dest_db,
            $resourceSpecs,
            'hpcdb_resource_allocated',
            array(
                'resource_id',
                'start_date_ts',
                'end_date_ts',
                'percent',
            )
        );
    }
}
