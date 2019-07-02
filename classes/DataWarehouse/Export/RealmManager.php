<?php
/**
 * Manage data regarding realms available for batch export.
 */

namespace DataWarehouse\Export;

use CCR\DB;
use Configuration\XdmodConfiguration;
use Exception;
use Models\Services\Realms;
use XDUser;

/**
 */
class RealmManager
{
    /**
     * Database handle for moddb.
     * @var \CCR\DB\iDatabase
     */
    private $dbh;

    /**
     * Raw statistics configuration.
     * @var \Configuration\XdmodConfiguration
     */
    private $config;

    /**
     */
    public function __construct()
    {
        $this->dbh = DB::factory('database');
        $this->config = XdmodConfiguration::assocArrayFactory(
            'rawstatistics.json',
            CONFIG_DIR
        );
    }

    /**
     * Get an array of all the batch exportable realms.
     *
     * @return \Models\Realm[]
     */
    public function getRealms()
    {
        // The "display" values from rawstatistics match those in
        // moddb.realms.display`, but the "name" values do not.
        $exportable = array_map(
            function ($realm) {
                return $realm['display'];
            },
            $this->config['realms']
        );

        return array_filter(
            Realms::getRealms(),
            function ($realm) use ($exportable) {
                return in_array($realm->getDisplay(), $exportable);
            }
        );
    }

    /**
     * Get an array of all the batch exportable realms for a user.
     *
     * @param \XDUser $user
     * @return \Models\Realm[]
     */
    public function getRealmsForUser(XDUser $user)
    {
        // Returns data from moddb.realms.display column.
        $userRealms = Realms::getRealmsForUser($user);

        return array_filter(
            $this->getRealms(),
            function ($realm) use ($userRealms) {
                return in_array($realm->getDisplay(), $userRealms);
            }
        );
    }

    /**
     * Get the raw data query class for the given realm.
     *
     * @param string $realmName The realm name used in moddb.realms.name.
     * @return string The fully qualified name of the query class.
     */
    public function getRawDataQueryClass($realmName)
    {
        // The query classes use the "name" from the rawstatistics
        // configuration, but the realm name is taken from moddb.realms.name.
        // These use the same "display" name so that is used to find the
        // correct class name.

        // Realm model.
        $realmObj = null;

        foreach ($this->getRealms() as $realm) {
            if ($realm->getName() == $realmName) {
                $realmObj = $realm;
                break;
            }
        }

        if ($realmObj === null) {
            throw new Exception(
                sprintf('Failed to find model for realm "%s"', $realmName)
            );
        }

        // Realm rawstatistics configuration.
        $realmConfig = null;

        foreach ($this->config['realms'] as $realm) {
            if ($realm['display'] == $realmObj->getDisplay()) {
                $realmConfig = $realm;
                break;
            }
        }

        if ($realmConfig === null) {
            throw new Exception(
                sprintf(
                    'Failed to find rawstatistics configuration for realm "%s"',
                    $realmName
                )
            );
        }

        return sprintf('\DataWarehouse\Query\%s\JobDataset', $realmConfig['name']);
    }
}
