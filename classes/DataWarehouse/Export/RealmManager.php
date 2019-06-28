<?php
/**
 * Manage data regarding realms available for batch export.
 */

namespace DataWarehouse\Export;

use CCR\DB;
use Configuration\XdmodConfiguration;
use Service\Realms;
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
     */
    public function getRawDataQueryClass($realm)
    {
    }
}
