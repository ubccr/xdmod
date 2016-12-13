<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Ingestor\Staging;

use ArrayIngestor;
use Xdmod\Config;

/**
 * Ingest resources.
 *
 * Combines data from mod_shredder.staging_resource with data from
 * resources.json.
 */
class Resources extends ArrayIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $config = Config::factory();
        $resourceConfig = $config['resources'];

        $configForResource = array();

        foreach ($resourceConfig as $id => $resource) {
            $configForResource[$resource['resource']] = $resource;
        }

        $sql = 'SELECT resource_id, resource_name FROM staging_resource';
        $rows = $src_db->query($sql);

        $resources = array();

        $defaultTimezone = date_default_timezone_get();

        foreach ($rows as $row) {
            $config = $configForResource[$row['resource_name']];

            $description
                = array_key_exists('description', $config)
                ? $config['description']
                : '';

            $sharedJobs
                = array_key_exists('shared_jobs', $config)
                ? $config['shared_jobs']
                : false;

            $timezone
                = array_key_exists('timezone', $config)
                ? $config['timezone']
                : $defaultTimezone;

            $resources[] = array(
                $row['resource_id'],
                $config['resource_type_id'] ?: 0,
                1,
                $config['name'],
                $config['resource'],
                $description,
                $sharedJobs,
                $timezone,
            );
        }

        parent::__construct(
            $dest_db,
            $resources,
            'hpcdb_resources',
            array(
                'resource_id',
                'resource_type_id',
                'organization_id',
                'resource_name',
                'resource_code',
                'resource_description',
                'resource_shared_jobs',
                'resource_timezone',
            )
        );
    }
}
