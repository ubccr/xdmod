<?php

namespace DataWarehouse\Access;

use Configuration\XdmodConfiguration;
use XDUser;
use Models\Services\Realms;

/*
 * Data access for raw data from the fact tables
 */
class RawData
{
    public static function getRawDataRealms(XDUser $user)
    {
        $realms = array();

        $raw = XdmodConfiguration::factory('rawstatistics.json', CONFIG_DIR)->toStdClass();
         
        if (!property_exists($raw, 'realms')) {
            return $realms;
        }

        $allowedRealms = Realms::getRealmsForUser($user);

        foreach($raw->realms as $realmConfig)
        {
            if (property_exists($realmConfig, 'raw_data')) {
                if ($realmConfig->raw_data === false) {
                    continue;
                }
            }

            if (in_array($realmConfig->name, $allowedRealms)) {
                $realms[] = $realmConfig;
            }
        }

        return $realms;
    }

    public static function realmExists(XDUser $user, $realm)
    {
        $realmlist = self::getRawDataRealms($user);

        foreach ($realmlist as $realmConfig) {
            if ($realm == $realmConfig->name) {
                return true;
            }
        }
        return false;
    }
}
