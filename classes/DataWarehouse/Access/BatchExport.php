<?php

namespace DataWarehouse\Access;

use DataWarehouse\Data\RawStatisticsConfiguration;
use Models\Services\Realms;
use XDUser;

/**
 * Data access for batch exporting from fact tables.
 */
class BatchExport
{
    /**
     * Get all the realms for a user.
     *
     * @param \XDUser $user
     * @return array[] Batch export realm configurations enabled for user.
     */
    public static function getBatchExportRealms(XDUser $user)
    {
        $config = RawStatisticsConfiguration::factory();
        $allowedRealms = Realms::getRealmIdsForUser($user);

        $realms = array_filter(
            $config->getBatchExportRealms(),
            function ($realm) use ($allowedRealms) {
                return in_array($realm['name'], $allowedRealms);
            }
        );

        // Use array_values to remove gaps in keys that may have been
        // introduced by the use of array_filter.
        return array_values($realms);
    }

    /**
     * Check if a realm exists for a user.
     *
     * @param \XDUser $user
     * @param string $realm
     * @return boolean
     */
    public static function realmExists(XDUser $user, $realm)
    {
        foreach (self::getBatchExportRealms($user) as $realmConfig) {
            if ($realm == $realmConfig['name']) {
                return true;
            }
        }

        return false;
    }
}
