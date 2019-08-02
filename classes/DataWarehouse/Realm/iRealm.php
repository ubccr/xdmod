<?php
/**
 * A Realm is a collection of data, descriptive attributes, and statistics.
 */

namespace Realm;

use Log as Logger;  // PEAR logger

interface iRealm
{
    /**
     * @var int Constants used to specify how various lists will be ordered including realm names,
     *   realm objects, statistic names, etc. Possible values are:
     *
     *  0 (SORT_ON_ORDER) = Numeric sort based on the realm order configuration option. If multiple
     *      realms have the same order hint their relative order is undefined. This is the default.
     *  1 (SORT_ON_SHORT_ID) = Natural sort based on the realm short identifier (id)
     *  2 (SORT_ON_NAME) = Natural sort based on the human-readbale realm name
     */

    const SORT_ON_ORDER = 0;
    const SORT_ON_SHORT_ID = 1;
    const SORT_ON_NAME = 2;

    /**
     * Instantiate a Realm class using the specified configuration or realm name.
     *
     * @param string $shortName The short internal identifier for the realm that will be
     *   instantiated.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return Realm A Realm class.
     *
     * @throws Exception if there was an error creating the object.
     */

    public static function factory($shortName, Logger $logger = null);

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
     * Return an associative array where the array keys are realm short identifier (id) and the values
     * are human-readable realm names.
     *
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return array An associative array of realm ids and names, ordered as specified.
     */

    public static function getRealmNames($order = self::SORT_ON_ORDER, Logger $logger = null);

    /**
     * Return an associative array where the array keys are realm short identifier (id) and the values
     * are the Realm objects associated with that key.
     *
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return array An associative array of realm ids and Realm objects, ordered as specified.
     */

    public static function getRealmObjects($order = self::SORT_ON_ORDER, Logger $logger = null);

    /**
     * @return string The short internal identifier.
     */

    public function getId();

    /**
     * @return string The display name.
     */

    public function getName();

    /**
     * @return string The schema where the aggregate table resides.
     */

    public function getAggregateTableSchema();

    /**
     * @param boolean $includeSchema TRUE to include the schema in the table name.
     *
     * @return string The aggregate table prefix that the Realm draws its data from. The aggregation
     *   period is expected to be added to this value.
     */

    public function getAggregateTablePrefix($includeSchema = true);

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
     *   system.
     */

    public function isDisabled();

    /**
     * @return int The order to advise how elements should be displayed visually in reference to one
     *   another.
     */

    public function getOrder();

    /**
     * @param string $id Statistic short identifier.
     *
     * @return TRUE if a Statistic with the specified ID exists and is not disabled.
     */

    public function statisticExists($id);

    /**
     * @param string $id GroupBy short identifier.
     *
     * @return TRUE if a Group By with the specified ID exists and is not disabled.
     */

    public function groupByExists($id);

    /**
     * Note that disabled GroupBy names are not included.
     *
     * @param int $order A specification on how the list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     *
     * @return array An associative array of the GroupBy names available to this realm where the key
     *   is the short identifier and the value is the human readable name.
     */

    public function getGroupByNames($order = self::SORT_ON_ORDER);

    /**
     * Note that disabled Statistic names are not included.
     *
     * @param int $order A specification on how the list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     *
     * @return array An associative array of the Statistic names available to this realm where the
     *   key is the short identifier and the value is the human readable name.
     */

    public function getStatisticNames($order = self::SORT_ON_ORDER);

    /**
     * Note that disabled GroupBy objects are not included.
     *
     * @param int $order A specification on how the list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     *
     * @return array An associative array of the GroupBy obects available to this realm where the
     *   key is the GroupBy short identifier and the value is the associated object.
     */

    public function getGroupByObjects($order = self::SORT_ON_ORDER);

    /**
     * Note that disabled Statistic objects are not included.
     *
     * @param int $order A specification on how the list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     *
     * @return array An associative array of the Statistic obects available to this realm where the
     *   key is the Statistic short identifier and the value is the associated object.
     */

    public function getStatisticObjects($order = self::SORT_ON_ORDER);

    /**
     * Note that disabled GroupBys are not available.
     *
     * @param string $id The internal GroupBy identifier to locate.
     *
     * @return GroupBy|null The GroupBy class associated with this realm that has the specified
     *   short identifier or NULL if the identifier was not found.
     */

    public function getGroupByObject($id);

    /**
     * Note that disabled Statistics are not available.
     *
     * @param string $id The internal Statistic identifier to locate.
     *
     * @return Statistic|null The Statistic class associated with this realm that has the specified
     *   short identifier or NULL if the identifier was not found.
     */

    public function getStatisticObject($id);

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
     * Generate a list of drilldowns that are available in this realm for the specified statistic
     * and group by.  This will include all of the group bys defined for this realm except those
     * marked as not available for drill down and the specified group by.
     *
     * @param string $statisticId The short identifier for the current statistic.
     * @param string $groupById The short identifier for the current group by.
     * @param int $order A specification on how the list will be ordered. Possible values are:
     *   SORT_ON_ORDER, SORT_ON_SHORT_ID, SORT_ON_NAME.
     */

    public function getDrillTargets($statisticId, $groupById, $order = self::SORT_ON_ORDER);

    /**
     * @return string A string representation of this object.
     */

    public function __toString();
}
