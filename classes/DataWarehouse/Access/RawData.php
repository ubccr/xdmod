<?php

namespace DataWarehouse\Access;

use DataWarehouse\Data\RawStatisticsConfiguration;
use XDUser;
use Models\Services\Realms;

/*
 * Data access for raw data from the fact tables
 */
class RawData
{
    /**
     * Get all the realms for a user.
     *
     * @param \XDUser $user
     * @return array[] Raw data realm configurations enabled for user.
     */
    public static function getRawDataRealms(XDUser $user)
    {
        $config = RawStatisticsConfiguration::factory();
        $allowedRealms = Realms::getRealmsForUser($user);

        $realms = array_filter(
            $config->getRawDataRealms(),
            function ($realmConfig) use ($allowedRealms) {
                return in_array($realmConfig['name'], $allowedRealms);
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
        $realmlist = self::getRawDataRealms($user);

        foreach ($realmlist as $realmConfig) {
            if ($realm == $realmConfig['name']) {
                return true;
            }
        }
        return false;
    }
}
