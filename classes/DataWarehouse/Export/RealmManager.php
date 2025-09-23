<?php

namespace DataWarehouse\Export;

use DataWarehouse\Access\BatchExport;
use DataWarehouse\Data\RawStatisticsConfiguration;
use Exception;
use Models\Services\Realms;
use XDUser;

/**
 * Manage data regarding realms available for batch export.
 */
class RealmManager
{
    /**
     * Raw statistics configuration.
     * @var \DataWarehouse\Data\RawStatisticsConfiguration
     */
    private $config;

    /**
     * Prepare database connection and load configuration.
     */
    public function __construct()
    {
        $this->config = RawStatisticsConfiguration::factory();
    }

    /**
     * Get an array of all the batch exportable realms.
     *
     * @return \Models\Realm[]
     */
    public function getRealms()
    {
        $exportable = array_map(
            function ($realm) {
                return $realm['name'];
            },
            $this->config->getBatchExportRealms()
        );

        $realms = array_filter(
            Realms::getRealms(),
            function ($realm) use ($exportable) {
                return in_array($realm->getName(), $exportable);
            }
        );

        // Use array_values to remove gaps in keys that may have been
        // introduced by the use of array_filter.
        $values = array_values($realms);

        // We force sorting in descending order due to the differences in sorting from PHP7.2 to PHP8.0
        usort($values, fn($left, $right) => strcmp($left->getName(), $right->getName()) * -1);

        return $values;
    }

    /**
     * Get an array of all the batch exportable realms for a user.
     *
     * @param \XDUser $user
     * @return \Models\Realm[]
     */
    public function getRealmsForUser(XDUser $user)
    {
        $realms = array_filter(
            $this->getRealms(),
            function ($realm) use ($user) {
                return BatchExport::realmExists($user, $realm->getName());
            }
        );

        // Use array_values to remove gaps in keys that may have been
        // introduced by the use of array_filter.
        return array_values($realms);
    }

    /**
     * Get the raw data query class for the given realm.
     *
     * @param string $realmName The realm name used in moddb.realms.name.
     * @return string The fully qualified name of the query class.
     */
    public function getRawDataQueryClass($realmName)
    {
        return sprintf('\DataWarehouse\Query\%s\JobDataset', $realmName);
    }
}
