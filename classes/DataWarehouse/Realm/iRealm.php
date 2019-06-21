<?php
/**
 * A Realm is a collection of data, descriptive attributes, and statistics.
 */

namespace Realm;

use Log as Logger;  // PEAR logger

interface iRealm
{
    /**
     * Instantiate a Realm class using the specified options or realm name.
     *
     * @param mixed $specificaiton A stdClass contaning the realm definition or a string specifying
     *   a realm name.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return Realm A Realm class.
     *
     * @throws Exception if there was an error creating the object.
     */

    public static function factory($specification, Logger $logger = null);

    /**
     * List the realms that have been defined in the database.
     *
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return array An associative array of Realm names where the keys are realm ids and the values
     *   are realm names.
     */

    public static function enumerate(Log $logger = null);

    /**
     * Initialize data for all realms from the definition source. This can mean constructing the
     * list of realms from a configuration file, loading from the database or an object cache, or
     * some other mechanism. This method must be called once, either directly or indirectly, before
     * a Realm object can be accessed.
     *
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @throws Exception If there was an error loading the realm data.
     */

    public static function initialize(Logger $logger = null);

    /**
     * Save the Realm into a data store.
     *
     * @throws Exception if there was an error while saving.
     */

    public function save();

    /**
     * @return string The short internal identifier.
     */

    public function getId();

    /**
     * @return string The isplay name.
     */

    public function getName();

    /**
     * @param boolean $includeSchema TRUE to include the schema in the table name.
     *
     * @return string The aggregate table that the Realm draws its data from.
     */

    public function getAggregateTable($includeSchema = true);

    /**
     * @return string The data source or sources used to generate the Realm data. For example,
     *   XDCDB, XDMoD Data Warehouse, etc.
     */

    public function getDatasource();

    /**
     * @return string The name of the module that defined this Realm. The default is "xdmod", the
     *   core XDMoD module.
     */

    public function getModuleName();

    /**
     * @return boolean TRUE if the realm is disabled and should not be visible iat all to the
     *   system. Note that this is different than being disabled or hidden.
     */

    public function isDisabled();

    /**
     * @return TRUE if the realm is hidden and should not be displayed in the UI/
     */

    public function isHidden();

    /**
     * @return TRUE if the realm is restricted and access is controlled by the ACL infrastructure.
     */

    public function isRestricted();

    /**
     * @return int The order to advise how realms should be displayed visually in reference to one
     *   another.
     */

    public function getOrder();

    /**
     * @return array An array of GroupBy obects available to this realm.
     */

    public function getGroupBys();

    /**
     * @return array An array of Statistic objects available to this realm.
     */

    public function getStatistics();

    /**
     * @param string $id The internal GroupBy identifier to locate.
     *
     * @return GroupBy|null The GroupBy class associated with this realm that has the specified
     * identifier or NULL if the identifier was not found.
     */

    public function getGroupBy($id);

    /**
     * @param string $id The internal Statistic identifier to locate.
     *
     * @return Statistic|null The Statistic class associated with this realm that has the specified
     * identifier or NULL if the identifier was not found.
     */

    public function getStatistic($id);

    /**
     * @return string The default weighting statistic for this realm. This can be overriden by
     *   individual statistics.
     */

    public function getDefaultWeighgtStatName();

    /**
     * @return string The smallest aggregation unit that this realm supports. Some realms simply do
     *   not have as fine of a granularity as others.
     */

    public function getMinimumAggregationUnit();

    /**
     * @return string A string representation of this object.
     */

    public function __toString();
}
