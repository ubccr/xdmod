<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Ingestor\Staging;

use DateTime;
use ArrayIngestor;
use Xdmod\Config;

/**
 * Ingest resource specs.
 *
 * Combines data from mod_shredder.staging_resource with data from
 * resource_specs.json.
 */
class ResourceSpecs extends ArrayIngestor
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

            $comment
                = array_key_exists('comments', $spec)
                ? $spec['comments']
                : null;

            $resourceSpecs[] = array(
                $id,
                $startDateTs,
                $endDateTs,
                $spec['nodes'],
                $spec['processors'],
                $spec['ppn'],
                $comment,
            );
        }

        parent::__construct(
            $dest_db,
            $resourceSpecs,
            'hpcdb_resource_specs',
            array(
                'resource_id',
                'start_date_ts',
                'end_date_ts',
                'node_count',
                'cpu_count',
                'cpu_count_per_node',
                'comments',
            )
        );
    }
}
