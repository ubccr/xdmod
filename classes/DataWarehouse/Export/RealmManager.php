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
            fn($realm) => $realm['name'],
            $this->config->getBatchExportRealms()
        );

        $realms = array_filter(
            Realms::getRealms(),
            fn($realm) => in_array($realm->getDisplay(), $exportable)
        );

        // Use array_values to remove gaps in keys that may have been
        // introduced by the use of array_filter.
        $values = array_values($realms);
        usort($values, fn($left, $right) => strcmp($left->getName(), $right->getName()) * -1);
        return $values;
    }

    /**
     * Get an array of all the batch exportable realms for a user.
     *
     * @return \Models\Realm[]
     */
    public function getRealmsForUser(XDUser $user)
    {
        $baseRealms = $this->getRealms();
        $realms = array_filter(
            $baseRealms,
            fn($realm) => BatchExport::realmExists($user, $realm->getDisplay())
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
