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
     *   SORT_ID, SORT_ALPHA_ID, SORT_ALPHA_NAME.
     *
     * @return array An associative array of realm ids and names, ordered as specified.
     */

    public static function getRealmNames($order = SORT_ON_ORDER);

    /**
     * Return an associative array where the array keys are realm short identifier (id) and the values
     * are the Realm objects associated with that key.
     *
     * @param int $order See getRealmNames() for a description of this parameter.
     *
     * @return array An associative array of realm ids and Realm objects, ordered as specified.
     */

    public static function getRealmObjects($order = SORT_ON_ORDER);

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
     *   system.
     */

    public function isDisabled();

    /**
     * @return int The order to advise how realms should be displayed visually in reference to one
     *   another.
     */

    public function getOrder();

    /**
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ID, SORT_ALPHA_ID, SORT_ALPHA_NAME.
     *
     * @return array An associative array of the GroupBy names available to this realm where the key
     *   is the short identifier and the value is the human readable name.
     */

    public function getGroupByNames($order = SORT_ON_ORDER);

    /**
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ID, SORT_ALPHA_ID, SORT_ALPHA_NAME.
     *
     * @return array An associative array of the Statistic names available to this realm where the
     *   key is the short identifier and the value is the human readable name.
     */

    public function getStatisticNames($order = SORT_ON_ORDER);

    /**
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ID, SORT_ALPHA_ID, SORT_ALPHA_NAME.
     *
     * @return array An associative array of the GroupBy obects available to this realm where the
     *   key is the GroupBy short identifier and the value is the associated object.
     */

    public function getGroupByObjects($order = SORT_ON_ORDER);

    /**
     * @param int $order A specification on how the realm list will be ordered. Possible values are:
     *   SORT_ID, SORT_ALPHA_ID, SORT_ALPHA_NAME.
     *
     * @return array An associative array of the Statistic obects available to this realm where the
     *   key is the Statistic short identifier and the value is the associated object.
     */

    public function getStatisticObjects($order = SORT_ON_ORDER);

    /**
     * @param string $id The internal GroupBy identifier to locate.
     *
     * @return GroupBy|null The GroupBy class associated with this realm that has the specified
     *   short identifier or NULL if the identifier was not found.
     */

    public function getGroupByObject($id);

    /**
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
     * @return string A string representation of this object.
     */

    public function __toString();
}
