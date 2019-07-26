<?php
/**
 * @see iStatistic
 */

namespace Realm;

use Log as Logger;  // CCR implementation of PEAR logger

class Statistic extends \CCR\Loggable implements iStatistic
{
    /**
     * @var Realm The realm that this GroupBy belongs to.
     */

    protected $realm = null;

    /**
     * @var string The name of the module that defined this group by
     */

    protected $moduleName = null;

    /**
     * @var string The database alias to use with the formula when querying the data.
     */

    protected $dbAlias = null;

    /**
     * @var string The short identifier.
     */

    protected $id = null;

    /**
     * @var string The display name.
     */

    protected $name = null;

    /**
     * @var string The SQL fragment used to generate the statistic.
     */

    protected $formula = null;

    /**
     * @var string The SQL fragment used specifically for aggregate queries (optional, overrides formula)
     */

    protected $aggregateFormula = null;

    /**
     * @var string The SQL fragment used specifically for timeseries queries (optional, overrides formula)
     */

    protected $timeseriesFormula = null;

    /**
     * @var string Human-readable description supporting basic HTML formatting.
     */

    protected $description = null;

    /**
     * @var string The unit of measurement.
     */

    protected $unit = null;

    /**
     * @var int A numerical ordering hint as to how this realm should be displayed visually relative
     *   to other realms. Lower numbers are displayed first. If no order is specified, 0 is assumed.
     */

    protected $order = 0;

    /**
     * @var int The number of decimal places to display.
     */

    protected $precision = 1;

    /**
     * @var boolean Set to true if this statistic by should not be utilized.
     */

    protected $disabled = false;

    /**
     * @var int PHP order specificaiton to determine how the query should sort results containing
     *   this Statistic.
     * @see http://php.net/manual/en/function.array-multisort.php
     */

    protected $sortOrder = SORT_DESC;

    /**
     * @var array|null The definition of the additional where condition for this statistic. If not
     *   null, the array must contain 3 parameters: (left-column, operation, right-column). For
     *   example: array('netdrv_panasas_rx', 'IS NOT', 'NULL').
     */

    protected $whereConditionDefinition = null;

    /**
     * @var boolean Set to false if this statistic cannot use tables where data is rolled up by time
     *   period to perform the calculation for an aggregate chart.  If a request was for aggregate
     *   charts and any statistic can't be provided in that form, the chart will be changed to to
     *   timeseries
     */

    protected $useTimeseriesAggregateTables = true;

    /**
     * @see iRealm::factory()
     */

    public static function factory($shortName, \stdclass $config, Realm $realm, Logger $logger = null)
    {
        return new static($shortName, $config, $realm, $logger);
    }

    /**
     * This constructor is meant to be called by factory() but it cannot be made private because we
     * extend Loggable, which has a public constructor.
     *
     * @param string $shortName The short name for this statistic
     * @param stdClass $config An object contaning the configuration specificaiton.
     * @param Realm $realm Realm object that this GroupBy will belong to.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     */

    public function __construct($shortName, \stdClass $config, Realm $realm, Logger $logger = null)
    {
        parent::__construct($logger);

        // The __toString() method needs these to be set and logAndThrowException() calls
        // __toString() so assign these at the top.

        $this->id = $shortName;
        $this->realm = $realm;
        $this->dbAlias = sprintf('%s_%s', $realm->getId(), $this->id);
        $this->moduleName = $realm->getModuleName();

        if ( empty($shortName) ) {
            $this->logger->logAndThrowException('Statistic short name not provided');
        } elseif ( ! is_string($shortName) ) {
            $this->logger->logAndThrowException(
                sprintf('Statistic short name must be a string, %s provided: %s', $shortName, gettype($shortName))
            );
        } elseif ( null === $config ) {
            $this->logger->logAndThrowException('No Statistic configuration provided');
        }

        // Verify the types of the various configuration options

        $messages = array();
        $configTypes = array(
            'description_html' => 'string',
            'formula' => 'string',
            'name' => 'string',
            'unit' => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($config, $configTypes, $messages) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Statistic configuration: %s', implode(', ', $messages))
            );
        }

        $optionalConfigTypes = array(
            'additional_where_condition' => 'array',
            'aggregate_formula' => 'string',
            'disabled' => 'bool',
            'module' => 'string',
            'order' => 'int',
            'precision' => 'int',
            'timeseries_formula' => 'string',
            'use_timeseries_aggregate_tables' => 'bool'
        );

        if ( ! \xd_utilities\verify_object_property_types($config, $optionalConfigTypes, $messages, true) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Realm configuration: %s', implode(', ', $messages))
            );
        }

        foreach ( $config as $key => $value ) {
            switch ($key) {
                case 'additional_where_condition':
                    if ( 3 != count($value) ) {
                        $this->logAndThrowException(
                            sprintf('Expected an array of 3 elements, got %d elements', count($value))
                        );
                    }
                    $this->whereConditionDefinition = $value;
                    break;
                case 'aggregate_formula':
                    $this->aggregateFormula = trim($value);
                    break;
                case 'description_html':
                    $this->description = trim($value);
                    break;
                case 'disabled':
                    $this->disabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'formula':
                    $this->formula = trim($value);
                    break;
                case 'module':
                    $this->moduleName = trim($value);
                    break;
                case 'name':
                    $this->name = trim($value);
                    break;
                case 'order':
                    $this->order = filter_var($value, FILTER_VALIDATE_INT);
                    break;
                case 'precision':
                    $this->precision = filter_var($value, FILTER_VALIDATE_INT);
                    break;
                case 'timeseries_formula':
                    $this->timeseriesFormula = trim($value);
                    break;
                case 'unit':
                    $this->unit = trim($value);
                    break;
                case 'use_timeseries_aggregate_tables':
                    $this->useTimeseriesAggregateTables = filter_var($value, FILTER_VALIDATE_BOOLEAN);
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

    public function getName($includeUnit = true)
    {
        if ( $includeUnit && strpos($this->name, $this->unit) ) {
            return sprintf("%s (%s)", $this->name, $this->unit);
        } else {
            return $this->name;
        }
    }

    /**
     * @see iStatistic::getHtmlDescription()
     */

    public function getHtmlDescription()
    {
        return $this->description;
    }

    /**
     * @see iStatistic::getNameAndDescription()
     */

    public function getNameAndDescription()
    {
        return sprintf("<b>%s</b>: %s", $this->name, $this->description);
    }

    /**
     * @see iStatistic::getUnit()
     */

    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @see iStatistic::getFormula()
     */

    public function getFormula(Query $query = null)
    {
        // If no query was specified return the unmodified formula. If a query was specified, return
        // the appropriate formula based on whether this is an aggregate or timeseries query.

        if ( null === $query ) {
            if ( null === $this->formula ) {
                throw new \Exception(
                    sprintf("Key 'formula' not specified for statistic %s and Query not provided to getFormula()", $this)
                );
            }
            return sprintf('%s AS %s', $this->formula, $this->dbAlias);
        } else {
            if ( null === $this->aggregateFormula && null === $this->timeseriesFormula ) {
                return $query->getVariableStore()->substitute($this-formula);
            } elseif ( $query->isAggregate() ) {
                return sprintf('%s AS %s', $query->getVariableStore()->substitute($this->aggregateFormula), $this->dbAlias);
            } elseif ( $query->isTimeseries() ) {
                return sprintf('%s AS %s', $query->getVariableStore()->substitute($this->timeseriesFormula), $this->dbAlias);
            }
        }
    }

    /**
     * @see iStatistic::getPrecision()
     */

    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @see iStatistic::setSortOrder()
     */

    public function setSortOrder($sortOrder = SORT_DESC)
    {
        $validSortOrders = array(
            SORT_ASC,
            SORT_DESC,
            SORT_REGULAR,
            SORT_NUMERIC,
            SORT_STRING,
            SORT_LOCALE_STRING,
            SORT_NATURAL
        );

        if ( ! in_array($sortOrder, $validSortOrders) ) {
            $this->logAndThrowException(sprintf("Invalid sort option: %d", $sortOrder));
        }

        $this->sortOrder = $sortOrder;
    }

    /**
     * @see iStatistic::getSortOder()
     */

    public function getSortOder()
    {
        return $this->sortOrder;
    }

    /**
     * @see iStatistic::getOrder()
     */

    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @see iStatistic::getAdditionalWhereCondition()
     */

    public function getAdditionalWhereCondition()
    {
        list($leftCol, $operation, $rightCol) = $this->whereConditionDefinition;
        return new \DataWarehouse\Query\Model\WhereCondition($leftCol, $operation, $rightCol);
    }

    /**
     * @see iStatistic::usesTimePeriodTablesForAggregate()
     */

    public function usesTimePeriodTablesForAggregate()
    {
        return $this->useTimeseriesAggregateTables;
    }

    /**
     * @see iStatistic::getWeightStatName()
     */

    public function getWeightStatName()
    {
        return 'weight_is_not_used';
    }

    /**
     * @see iStatistic::__toString()
     */

    public function __toString()
    {
        return sprintf("%s->Statistic(id=%s, formula=%s)", $this->realm, $this->id, $this->formula);
    }
}
