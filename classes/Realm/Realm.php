<?php
/**
 * A Realm is a collection of data, descriptive attributes, and statistics.
 *
 * ToDo: Use APCu to store a cache of realm objects and possibly group by and statistic objects.
 */

namespace Realm;

use Configuration\Configuration;
use DataWarehouse\Query\Exceptions\UnknownGroupByException;
use DataWarehouse\Query\Exceptions\UnavailableTimeAggregationUnitException;
use DataWarehouse\Query\TimeAggregationUnit;
use ETL\VariableStore;
use Psr\Log\LoggerInterface;

class Realm extends \CCR\Loggable implements iRealm
{
    /**
     * @var string The short identifier.
     */

    protected $id = null;

    /**
     * @var string The display name.
     */

    protected $name = null;

    /**
     * @var string The name of the data source to be displayed in the chart credits.
     */

    protected $datasource = null;

    /**
     * @var string Schema for the aggregates table used to display data for this realm.
     */

    protected $aggregateTableSchema = null;

    /**
     * @var string Prefix for the realm aggregate table. The aggregation period is appended to
     *   define the table (e.g., "jobfact_by_" with aggregation period "day" creates "jobfact_by_day")
     */

    protected $aggregateTablePrefix = null;

    /**
     * @var string Alias to be used for the aggregate alias. GroupBy and Statistic definitions
     * should use this alias when referring to the aggregate data table.
     */

    protected $aggregateTableAlias = 'agg';

    /**
     * @var int A numerical ordering hint as to how this realm should be displayed visually relative
     *   to other realms. Lower numbers are displayed first. If no order is specified, 0 is assumed.
     */

    protected $order = 0;

    /**
     * @var string Name of the module that defines this realm (default: "xdmod")
     */

    protected $module = 'xdmod';

    /**
     * @var string the minimum aggregation unit that this realm supports. Valid values are any
     *   aggregation unit (i.e., day, month, quarter, year).
     */

    protected $minAggregationUnit = null;

    /**
     * @var boolean Set to true if this realm should not be utilized.
     */

    protected $disabled = false;

    /**
     * @var boolean Set to false if this realm should not be shown to the user in the metric
     * catalog.
     */

    protected $showInCatalog = true;

    /**
     * @var string A category that the realm will be placed into. This is used for grouping multiple
     *   realms under a single category and was used for Value Analytics to present multiple realms
     *   as a single entry in the user interface. If a category is not provided, the realm id is
     *   used instead effectively placing each realm is in its own categoty.
     */

    protected $category = null;

    /**
     * @var stdClass|null An associative array of group by configuration objects where the key is
     *   the short identifier and the value is a stdClass containing the configuration. These are
     *   used to create GroupBy objects on demand.
     */

    protected $groupByConfigs = null;

    /**
     * @var stdClass|null An associative array of statistic configuration objects where the key is
     *   the short identifier and the value is a stdClass containing the configuration. These are
     *   used to create Statistic objects on demand.
     *
     * Note: The Realm id is added to the beginning of each statistic id so that the statistic
     *   names are guaranteed unique and can be used as identifiers in database queries and the UI.
     */

    protected $statisticConfigs = null;

    /**
     * @var array An associative array of one or more GroupBy objects where the key is the short
     * identifier and the value is the object.
     */

    protected $groupBys = array();

    /**
     * @var array An associative array of one or more Statistic objects where the key is the short
     * identifier and the value is the object.
     */

    protected $statistics = array();

    /**
     * @var Configuration Parsed Configuration object for datawarehouse.json
     */

    protected static $dataWarehouseConfig = null;

    /**
     * @var VariableStore Collection of variable names and values available for substitution in
     *   various properties.
     */

    protected $variableStore = null;

    /**
     * @see iRealm::initialize()
     */

    /**
     * Initialize data for all realms from the definition source. This can mean constructing the
     * list of realms from a configuration file, loading from the database or an object cache, or
     * some other mechanism. This method must be called once, either directly or indirectly, before
     * a Realm object can be accessed.
     *
     * @param LoggerInterface|null $logger A Monolog Logger instance that will be utilized during processing.
     * @param stdclass|null $options An object containing additional configuration options.
     *
     * @throws Exception If there was an error loading the realm data.
     *
     * @see iRealm::factory()
     */

    public static function initialize(LoggerInterface $logger = null, \stdClass $options = null)
    {
        $filename = ( isset($options->config_file_name) ? $options->config_file_name : 'datawarehouse.json' );
        $configDir = ( isset($options->config_base_dir) ? $options->config_base_dir : CONFIG_DIR );

        // When using a non-standard configuration file location we always re-load the configuration
        // class. Otherwise, tests that reference different locations or artifacts will fail
        // depending on the order that they are run in.

        $nonStandardConfig = ( isset($options->config_file_name) || isset($options->config_base_dir) );

        if ( null === self::$dataWarehouseConfig || $nonStandardConfig ) {
            self::$dataWarehouseConfig = Configuration::factory(
                $filename,
                $configDir,
                $logger
            );
        }
    }

    /**
     * @see iRealm::getRealmNames()
     */

    public static function getRealmNames($order = self::SORT_ON_ORDER, LoggerInterface $logger = null)
    {
        self::initialize($logger);
        return static::getSortedNameList(self::$dataWarehouseConfig->toStdClass(), $order);
    }

    /**
     * @see iRealm::getRealmIds()
     *
     * Essentially, call array_keys() on the list of realm names to get the ids. This keeps the code
     * in one place rather than littered throughout the codebase.
     */

    public static function getRealmIds($order = self::SORT_ON_ORDER, LoggerInterface $logger = null)
    {
        return array_keys(static::getRealmNames($order, $logger));
    }

    /**
     * @see iRealm::getRealmObjects()
     */

    public static function getRealmObjects($order = self::SORT_ON_ORDER, LoggerInterface $logger = null)
    {
        self::initialize($logger);
        return static::getSortedObjectList('Realm', self::$dataWarehouseConfig->toStdClass(), $order, null, $logger);
    }

    /**
     * @see iRealm::getStandardErrorStatisticFromStatistic()
     */

    public static function getStandardErrorStatisticFromStatistic($statisticId)
    {
        return sprintf('sem_%s', $statisticId);
    }

    /**
     * @see iRealm::factory()
     */

    public static function factory($shortName, LoggerInterface $logger = null, \stdClass $options = null)
    {
        if ( ! is_string($shortName) ) {
            $e = new \Exception("Realm 'shortname' must be a string", true);
            throw new \Exception($e->getTraceAsString());
        }

        self::initialize($logger, $options);
        $configObj = self::$dataWarehouseConfig->getSectionData($shortName);

        if ( false === $configObj ) {
            $msg = sprintf("Request for unknown Realm: %s", $shortName);
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new \Exception($msg);
        }
        return new static($shortName, $configObj, $logger);
    }

    /**
     * Sort the list of configuration objects according to the order specificaiton.
     *
     * @param stdClass $config An object containing configuration information where keys are entity
     *   short identifiers and the values are the configuration objects.
     * @param int $order The order specification as defined in iRealm.
     *
     * @return array The list ordered according to the order specificaiton where keys are entity
     *   short identifiers and values are configuration objects.
     */

    protected static function sortConfig(\stdClass $config, $order)
    {
        // Convert top level object into an associative array. This will leave the underlying values
        // as objects.

        $configList = (array) $config;

        switch ($order) {
            case self::SORT_ON_SHORT_ID:
                ksort($configList);
                break;

            case self::SORT_ON_NAME:
                uasort(
                    $configList,
                    function ($a, $b) {
                        return strcmp($a->name, $b->name);
                    }
                );
                break;

            case self::SORT_ON_ORDER:
                uasort(
                    $configList,
                    function ($a, $b) {
                        // Order is optional so default to 0 if the order is not specified
                        $orderA = ( isset($a->order) ? $a->order : 0 );
                        $orderB = ( isset($b->order) ? $b->order : 0 );

                        if ( $orderA < $orderB ) {
                            return -1;
                        } elseif ( $orderA > $orderB ) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                );
                break;

            default:
                // Do not sort
                break;
        }

        return $configList;
    }

    /**
     * Generate a sorted associative array from the specified configuration object. Note that
     * configuration objects marked as disabled are not included.
     *
     * @param stdClass $configObj An object containing one or more configurations for a realm, group
     *   by, or statistic. The object keys are the short names and the values are the entity
     *   configurations.
     * @param int $order A specification on how the realm list will be ordered. See iRealm for a
     *   list of possible values.
     *
     * @return array An associative array where the keys are entity short names and the values are
     * configuration objects for those entities. The array is sorted according to the order
     * specified.
     */

    private static function getSortedNameList(\stdClass $configObj, $order)
    {
        $list = array();

        $sorted = self::sortConfig($configObj, $order);
        foreach ( $sorted as $shortName => $config ) {
            // Skip disabled configs
            if ( ! isset($config->disabled) || false === $config->disabled ) {
                $list[$shortName] = $config->name;
            }
        }

        return $list;
    }

    /**
     * Generate a sorted associative array from the specified configuration object. Note that
     * configuration objects marked as disabled are not included.
     *
     * @param string $className
     * @param stdClass $configObj An object containing one or more configurations for a realm, group
     *   by, or statistic. The object keys are the short names and the values are the entity
     *   configurations.
     * @param int $order A specification on how the realm list will be ordered. See iRealm for a
     *   list of possible values.
     * @param Realm A Realm object needed for the factory method of GroupBys and Statistics
     * @param LoggerInterface|null $logger A Monolog Logger instance that will be utilized during processing.
     *
     * @return array An associative array where the keys are entity short names and the values are
     *   instantiated configuration objects for those entities. The array is sorted according to the
     *   order specified.
     */

    private static function getSortedObjectList(
        $className,
        \stdClass $configObj,
        $order,
        Realm $realmObj = null,
        LoggerInterface $logger = null
    ) {
        $list = array();
        $sorted = self::sortConfig($configObj, $order);

        foreach ( $sorted as $shortName => $config ) {

            // Skip disabled configs

            if (isset($config->disabled) && $config->disabled) {
                continue;
            }

            // The method that we call is determined by the class we are instantiating. For Realms
            // use late static binding. For other classes use the class name specified unless the
            // configuration explicitly provides a class name.

            $factoryClassName = ('Realm' == $className ? Realm::class : $className);
            if ('Realm' != $className && isset($configObj->class)) {
                if (!class_exists($configObj->class)) {
                    $msg = sprintf("Attempt to instantiate undefined %s class %s", $className, $configObj->class);
                    if (null !== $logger) {
                        $logger->error($msg);
                    }
                    throw new \Exception($msg);
                }
                $factoryClassName = $configObj->class;
            } elseif (false === strpos($factoryClassName, '\\') && 'static' != $factoryClassName) {
                $factoryClassName = sprintf('\\%s\\%s', __NAMESPACE__, $factoryClassName);
            }

            // We are using the array format for a callable instead of a string due to the use of `static::` being deprecated w/ the string version.
            $factoryCallable = [$factoryClassName, 'factory'];
            if ('Realm' == $className) {
                // The Realm class already has the configuration and does not need it to be passed
                // to factory().
                $list[$shortName] = forward_static_call($factoryCallable, $shortName, null, null, $logger);
            } else {

                // Entities encapsulated by the realm need their config objects
                $list[$shortName] = forward_static_call($factoryCallable, $shortName, $config, $realmObj, $logger);
            }
        }

        return $list;
    }

    /**
     * This constructor is meant to be called by factory() but it cannot be made private because we
     * extend Loggable, which has a public constructor.
     *
     * @param string $shortName The short name for this realm
     * @param stdClass $specificaiton An object contaning the realm definition.
     * @param LoggerInterface|null $logger A Monolog Logger instance that will be utilized during processing.
     */

    public function __construct($shortName, \stdClass $specification, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        if ( empty($shortName) ) {
            $this->logAndThrowException('Realm short name not provided');
        } elseif ( ! is_string($shortName) ) {
            $this->logAndThrowException(
                sprintf('Statistic short name must be a string, %s provided: %s', $shortName, gettype($shortName))
            );
        } elseif ( null === $specification ) {
            $this->logAndThrowException('No Realm specificaiton provided');
        }

        // Verify the types of the various configuration options

        $messages = array();
        $configTypes = array(
            'aggregate_schema' => 'string',
            'aggregate_table_prefix' => 'string',
            'datasource' => 'string',
            'group_bys' => 'object',
            'name' => 'string',
            'statistics' => 'object'
        );

        if ( ! \xd_utilities\verify_object_property_types($specification, $configTypes, $messages) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Realm configuration: %s', implode(', ', $messages))
            );
        }

        $optionalConfigTypes = array(
            'aggregate_table_alias' => 'string',
            'category' => 'string',
            'disabled' => 'bool',
            'min_aggregation_unit' => 'string',
            'module' => 'string',
            'order' => 'int',
            'show_in_catalog' => 'bool'
        );

        if ( ! \xd_utilities\verify_object_property_types($specification, $optionalConfigTypes, $messages, true) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Realm configuration: %s', implode(', ', $messages))
            );
        }

        $this->id = $shortName;
        $this->category = $shortName;

        foreach ( $specification as $key => $value ) {
            switch ($key) {
                case 'aggregate_schema':
                    $this->aggregateTableSchema = trim($value);
                    break;
                case 'aggregate_table_prefix':
                    $this->aggregateTablePrefix = trim($value);
                    break;
                case 'aggregate_table_alias':
                    $this->aggregateTableAlias = trim($value);
                    break;
                case 'category':
                    $this->category = trim($value);
                    break;
                case 'datasource':
                    $this->datasource = trim($value);
                    break;
                case 'disabled':
                    $this->disabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'group_bys':
                    $this->groupByConfigs = $value;
                    break;
                case 'order':
                    $this->order = filter_var($value, FILTER_VALIDATE_INT);
                    break;
                case 'min_aggregation_unit':
                    $this->minAggregationUnit = trim($value);
                    break;
                case 'module':
                    $this->module = trim($value);
                    break;
                case 'name':
                    $this->name = trim($value);
                    break;
                case 'show_in_catalog':
                    $this->showInCatalog = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'statistics':
                    $this->statisticConfigs = $value;
                    break;
                default:
                    $this->logger->notice(sprintf("Unknown key in realm by definition for '%s': '%s'", $this->id, $key));
                    break;
            }
        }

        $this->variableStore = new VariableStore(
            array(
                'ORGANIZATION_NAME' => ORGANIZATION_NAME,
                'ORGANIZATION_NAME_ABBREV' => ORGANIZATION_NAME_ABBREV,
                'REALM_ID' => $this->id,
                'REALM_NAME' => $this->name,
                'HIERARCHY_TOP_LEVEL_LABEL' => HIERARCHY_TOP_LEVEL_LABEL,
                'HIERARCHY_TOP_LEVEL_INFO' => HIERARCHY_TOP_LEVEL_INFO,
                'HIERARCHY_MIDDLE_LEVEL_LABEL' => HIERARCHY_MIDDLE_LEVEL_LABEL,
                'HIERARCHY_MIDDLE_LEVEL_INFO' => HIERARCHY_MIDDLE_LEVEL_INFO,
                'HIERARCHY_BOTTOM_LEVEL_LABEL' => HIERARCHY_BOTTOM_LEVEL_LABEL,
                'HIERARCHY_BOTTOM_LEVEL_INFO' => HIERARCHY_BOTTOM_LEVEL_INFO
            ),
            $logger
        );
    }

    /**
     * @see iRealm::getId()
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * @see iRealm::getName()
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * @see iRealm::getAggregateTableSchema()
     */

    public function getAggregateTableSchema()
    {
        return $this->aggregateTableSchema;
    }

    /**
     * @see iRealm::getAggregateTablePrefix()
     */

    public function getAggregateTablePrefix($includeSchema = true)
    {
        return sprintf('%s%s', ( $includeSchema ? $this->aggregateTableSchema  . '.' : "" ), $this->aggregateTablePrefix);
    }

    /**
     * @see iRealm::getAggregateTableAlias()
     */

    public function getAggregateTableAlias()
    {
        return $this->aggregateTableAlias;
    }

    /**
     * @see iRealm::getDatasource()
     */

    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @see iRealm::getModuleName()
     */

    public function getModuleName()
    {
        return $this->module;
    }

    /**
     * @see iReamn::isDisabled()
     */

    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @see iRealm::getOrder()
     */

    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @see iRealm::statisticExists()
     */

    public function statisticExists($id)
    {
        return isset($this->statisticConfigs->$id);
    }

    /**
     * @see iRealm::groupByExists()
     */

    public function groupByExists($id)
    {
        return isset($this->groupByConfigs->$id);
    }

    /**
     * @see iRealm::getGroupByNames()
     */

    public function getGroupByNames($order = self::SORT_ON_ORDER)
    {
        return static::getSortedNameList($this->groupByConfigs, $order);
    }

    /**
     * @see iRealm::getGroupByIds()
     *
     * Essentially, call array_keys() on the list of groupby names to get the ids. This keeps the
     * code in one place rather than littered throughout the codebase.
     */

    public function getGroupByIds($order = self::SORT_ON_ORDER)
    {
        return array_keys($this->getGroupByNames($order));
    }

    /**
     * @see iRealm::getStatisticNames()
     */

    public function getStatisticNames($order = self::SORT_ON_ORDER)
    {
        return static::getSortedNameList($this->statisticConfigs, $order);
    }

    /**
     * @see iRealm::getStatisticIds()
     *
     * Essentially, call array_keys() on the list of statistic names to get the ids. This keeps the
     * code in one place rather than littered throughout the codebase.
     */

    public function getStatisticIds($order = self::SORT_ON_ORDER)
    {
        return array_keys($this->getStatisticNames($order));
    }

    /**
     * @see iRealm::getGroupByObjects()
     */

    public function getGroupByObjects($order = self::SORT_ON_ORDER)
    {
        return static::getSortedObjectList('GroupBy', $this->groupByConfigs, $order, $this, $this->logger);
    }

    /**
     * @see iRealm::getStatisticObjects()
     */

    public function getStatisticObjects($order = self::SORT_ON_ORDER)
    {
        return static::getSortedObjectList('Statistic', $this->statisticConfigs, $order, $this, $this->logger);
    }

    /**
     * @see iRealm::getGroupByObject()
     */

    public function getGroupByObject($shortName)
    {
        if ( ! isset($this->groupByConfigs->$shortName) ) {
            if(TimeAggregationUnit::isTimeAggregationUnitName($shortName)){
                $timeException = new UnavailableTimeAggregationUnitException(sprintf('Realm: %s, does not support aggregation by %s', $this->name, $shortName));
                $timeException->errorData['unit'] = $shortName;
                throw $timeException;
            }
            $this->logger->warning(sprintf("No GroupBy found with id '%s' in Realm: %s", $shortName, $this->name));
            throw new UnknownGroupByException(
                sprintf("No GroupBy found with id '%s' in Realm: %s", $shortName, $this->name)
            );
        } elseif ( isset($this->groupByConfigs->$shortName->disabled) && $this->groupByConfigs->$shortName->disabled ) {
            $this->logAndThrowException(sprintf("Attempt to access disabled GroupBy '%s'", $shortName));
        }

        $config = $this->groupByConfigs->$shortName;
        $className = (isset($config->class) ? $config->class : 'GroupBy');
        if ( false === strpos($className, '\\') ) {
            $className = sprintf('\\%s\\%s', __NAMESPACE__, $className);
        }

        if ( ! class_exists($className) ) {
            $this->logAndThrowException(
                sprintf("Attempt to instantiate undefined GroupBy class: %s", $className)
            );
        }

        $factory = sprintf('%s::factory', $className);
        return forward_static_call($factory, $shortName, $this->groupByConfigs->$shortName, $this, $this->logger);
    }

    /**
     * @see iRealm::getStatisticObject()
     */

    public function getStatisticObject($shortName)
    {
        if ( ! isset($this->statisticConfigs->$shortName) ) {
            $this->logAndThrowException(sprintf("No Statistic found with id '%s'", $shortName));
        } elseif ( isset($this->statisticConfigs->$shortName->disabled) && $this->statisticConfigs->$shortName->disabled ) {
            $this->logAndThrowException(sprintf("Attempt to access disabled Statistic '%s'", $shortName));
        }

        $config = $this->statisticConfigs->$shortName;
        $className = (isset($config->class) ? $config->class : 'Statistic');
        if ( false === strpos($className, '\\') ) {
            $className = sprintf('\\%s\\%s', __NAMESPACE__, $className);
        }

        if ( ! class_exists($className) ) {
            $this->logAndThrowException(
                sprintf("Attempt to instantiate undefined Statistic class %s", $className)
            );
        }

        $factory = sprintf('%s::factory', $className);
        return forward_static_call($factory, $shortName, $this->statisticConfigs->$shortName, $this, $this->logger);
    }

    /**
     * @see iRealm::getDefaultWeightStatName()
     */

    public function getDefaultWeightStatName()
    {
        return 'weight';
    }

    /**
     * @see iRealm::getMinimumAggregationUnit()
     */

    public function getMinimumAggregationUnit()
    {
        return $this->minAggregationUnit;
    }

    /**
     * @see iRealm::showInMetricCatalog()
     */

    public function showInMetricCatalog()
    {
        return $this->showInCatalog;
    }

    /**
     * @see iRealm::getCategory()
     */

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @see iRealm::getVariableStore()
     */

    public function getVariableStore()
    {
        return $this->variableStore;
    }

    /**
     * @see iRealm::getDrillTargets()
     */

    public function getDrillTargets($groupById, $hiddenGroupBys = array(), $order = self::SORT_ON_ORDER)
    {
        $drillTargets = array();
        $groupByObjects = $this->getGroupByObjects($order);

        foreach ( $groupByObjects as $gId => $groupByObj ) {
            // Don't include the current group by or any group bys that are not available for drill
            // down.
            if ( $gId == $groupById || ! $groupByObj->showInMetricCatalog() || in_array($gId, $hiddenGroupBys) ) {
                continue;
            }
            $drillTargets[] = sprintf('%s-%s', $gId, $groupByObj->getName());
        }
        return $drillTargets;
    }

    /**
     * @see iRealm::__toString()
     */

    public function __toString()
    {
        return sprintf('%s(%s)', get_class($this), $this->id);
    }
}
