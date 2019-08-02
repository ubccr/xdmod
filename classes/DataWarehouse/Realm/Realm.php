<?php
/**
 * A Realm is a collection of data, descriptive attributes, and statistics.
 */

namespace Realm;

use Log as Logger;  // CCR implementation of PEAR logger
use Configuration\Configuration;

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
     *   define the table (e.g., “jobfact_by_” with aggregation period “day” creates “jobfact_by_day”`
     */

    protected $aggregateTablePrefix = null;

    /**
     * @var int A numerical ordering hint as to how this realm should be displayed visually relative
     *   to other realms. Lower numbers are displayed first. If no order is specified, 0 is assumed.
     */

    protected $order = 0;

    /**
     * @var string Name of the module that defines this realm (default: “xdmod”)
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
     * @var stdClass|null An associative array of group by configuration objects where the key is
     *   the short identifier and the value is a stdClass containing the configuration. These are
     *   used to create GroupBy objects on demand.
     */

    protected $groupByConfigs = null;

    /**
     * @var stdClass|null An associative array of statistic configuration objects where the key is
     *   the short identifier and the value is a stdClass containing the configuration. These are
     *   used to create Statistic objects on demand.
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
     * @see iRealm::initialize()
     */

    public static function initialize(Logger $logger = null)
    {
        if ( null === self::$dataWarehouseConfig ) {
            self::$dataWarehouseConfig = Configuration::factory(
                'datawarehouse2.json',
                CONFIG_DIR,
                $logger
            );
        }
    }

    /**
     * @see iRealm::getRealmNames()
     */

    public static function getRealmNames($order = self::SORT_ON_ORDER, Logger $logger = null)
    {
        self::initialize($logger);
        return static::getSortedNameList(self::$dataWarehouseConfig->toStdClass(), $order);
    }

    /**
     * @see iRealm::getRealmObjects()
     */

    public static function getRealmObjects($order = self::SORT_ON_ORDER, Logger $logger = null)
    {
        self::initialize($logger);
        return static::getSortedObjectList('Realm', self::$dataWarehouseConfig->toStdClass(), $order, null, $logger);
    }

    /**
     * @see iRealm::factory()
     */

    public static function factory($shortName, Logger $logger = null)
    {
        self::initialize($logger);
        $configObj = self::$dataWarehouseConfig->getSectionData($shortName);

        if ( false === $configObj ) {
            $msg = sprintf("Request for unknown Realm: %s", $shortName);
            if ( null !== $logger ) {
                $logger->err($msg);
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
     * @param Log|null $logger A Log instance that will be utilized during processing.
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
        Logger $logger = null
    ) {
        $list = array();
        $sorted = self::sortConfig($configObj, $order);

        foreach ( $sorted as $shortName => $config ) {

            // Skip disabled configs

            if ( isset($config->disabled) && $config->disabled ) {
                continue;
            }

            // The method that we cann is determined by the class we are instantiating. For Realms
            // use late static binding. For other classes use the class name specified unless the
            // configuration explicitly provides a class name.

            $factoryClassName = ('Realm' == $className ? 'static' : $className);
            if ( 'Realm' != $className && isset($configObj->class) ) {
                if ( ! class_exists($configObj->class) ) {
                    $this->logAndThrowException(
                        sprintf("Attempt to instantiate undefined %s class %s", $className, $configObj->class)
                    );
                }
                $factoryClassName = $configObj->class;
            } elseif ( false === strpos($factoryClassName, '\\') ) {
                $factoryClassName = sprintf('\\%s\\%s', __NAMESPACE__, $factoryClassName);
            }

            $factory = sprintf('%s::factory', $factoryClassName);
            print "Factory = $factory\n";

            if ( 'Realm' == $className ) {
                // The Realm class already has the configuration and does not need it to be passed
                // to factory().
                $list[$shortName] = forward_static_call($factory, $shortName, $logger);
            } else {
                // Entities encapsulated by the realm need their config objects
                $list[$shortName] = forward_static_call($factory, $shortName, $config, $realmObj, $logger);
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
     * @param Log|null $logger A Log instance that will be utilized during processing.
     */

    public function __construct($shortName, \stdClass $specification, Logger $logger = null)
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
            'class' => 'string',
            'disabled' => 'bool',
            'min_aggregation_unit' => 'string',
            'module' => 'string',
            'order' => 'int'
        );

        if ( ! \xd_utilities\verify_object_property_types($specification, $optionalConfigTypes, $messages, true) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Realm configuration: %s', implode(', ', $messages))
            );
        }

        $this->id = $shortName;

        foreach ( $specification as $key => $value ) {
            switch ($key) {
                case 'aggregate_schema':
                    $this->aggregateTableSchema = trim($value);
                    break;
                case 'aggregate_table_prefix':
                    $this->aggregateTablePrefix = trim($value);
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
                case 'statistics':
                    $this->statisticConfigs = $value;
                    break;
                default:
                    break;
            }
        }
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
     * @see iRealm::getStatisticNames()
     */

    public function getStatisticNames($order = self::SORT_ON_ORDER)
    {
        return static::getSortedNameList($this->statisticConfigs, $order);
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
            $this->logAndThrowException(sprintf("No GroupBy found with id '%s'", $shortName));
        } elseif ( isset($this->groupByConfigs->disabled) && $this->groupByConfigs->disabled ) {
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
        } elseif ( isset($this->groupByConfigs->disabled) && $this->groupByConfigs->disabled ) {
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
     * @see iRealm::getDefaultWeighgtStatName()
     */

    public function getDefaultWeighgtStatName()
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
     * @see iRealm::getDrillTargets()
     */

    public function getDrillTargets($statisticId, $groupById, $order = self::SORT_ON_ORDER)
    {
        // If the statistic id is not valid for this realm, do not provide any drill downs
        if ( ! $this->statisticExists($statisticId) ) {
            $this->logAndThrowException(
                sprintf("Attempt to get drill-down targets for unknown statistic '%s'", $statisticId)
            );
        }

        $drillTargets = array();
        $groupByObjects = $this->getGroupByObjects($order);

        foreach ( $groupByObjects as $gId => $groupByObj ) {
            // Don't include the current group by or any group bys that are not available for drill
            // down.
            if ( $gId == $groupById || ! $groupByObj->isAvailableForDrilldown() ) {
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
