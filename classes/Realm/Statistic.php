<?php
/**
 * @see iStatistic
 */

namespace Realm;

use ETL\VariableStore;
use Psr\Log\LoggerInterface;

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
     * @var string The short identifier. Must be unique among all statistics across all realms.
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
     * @var boolean Set to false if this statistic should not be shown to the user in the metric
     * catalog.
     */

    protected $showInCatalog = true;

    /**
     * @var int PHP order specificaiton to determine how the query should sort results containing
     *   this Statistic.
     * @see http://php.net/manual/en/function.array-multisort.php
     */

    protected $sortOrder = SORT_DESC;

    /**
     * @var string The id of the weight statistic to use when normalizing data for this statistic.
     *   This is typically used when calculating values such as standard error.
     */

    protected $weightStatisticId = 'weight_is_not_used';

    /**
     * @var array|null The definition of the additional where condition for this statistic. If not
     *   null, the array must contain 3 parameters: (left-column, operation, right-column). For
     *   example: array('netdrv_panasas_rx', 'IS NOT', 'NULL').
     */

    protected $additionalWhereConditionDefinition = null;

    /**
     * @var boolean Set to false if this statistic cannot use tables where data is rolled up by time
     *   period to perform the calculation for an aggregate chart.  If a request was for aggregate
     *   charts and any statistic can't be provided in that form, the chart will be changed to to
     *   timeseries
     */

    protected $useTimeseriesAggregateTables = true;

    /**
     * @var VariableStore Collection of variable names and values available for substitution in
     *   various properties.
     */

    protected $variableStore = null;

    /**
     * @var array Group By names that should be hidden for this statistic.
     */
    protected $hiddenGroupBys = [];

    /**
     * @see iStatistic::factory()
     */

    public static function factory($shortName, \stdclass $config, Realm $realm, LoggerInterface $logger = null)
    {
        return new static($shortName, $config, $realm, $logger);
    }

    /**
     * This constructor is meant to be called by factory() but it cannot be made private because we
     * extend Loggable, which has a public constructor.
     *
     * @param string $shortName The short name for this statistic
     * @param \stdClass $config An object contaning the configuration specificaiton.
     * @param Realm $realm Realm object that this GroupBy will belong to.
     * @param LoggerInterface|null $logger A Monolog Logger instance that will be utilized during processing.
     */

    public function __construct($shortName, \stdClass $config, Realm $realm, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        // The __toString() method needs these to be set and logAndThrowException() calls
        // __toString() so assign these at the top.

        $this->id = $shortName;
        $this->realm = $realm;
        $this->moduleName = $realm->getModuleName();

        if ( empty($shortName) ) {
            $this->logAndThrowException('Statistic short name not provided');
        } elseif ( ! is_string($shortName) ) {
            $this->logAndThrowException(
                sprintf('Statistic short name must be a string, %s provided: %s', $shortName, gettype($shortName))
            );
        } elseif ( null === $config ) {
            $this->logAndThrowException('No Statistic configuration provided');
        }

        // Verify the types of the various configuration options

        $messages = array();
        $configTypes = array(
            'description_html' => 'string',
            'name' => 'string',
            'unit' => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($config, $configTypes, $messages) ) {
            $this->logAndThrowException(
                sprintf('Error verifying Statistic configuration: %s', implode(', ', $messages))
            );
        }

        // Note that either formula or both aggregate_formula and timeseries_formula fields must be
        // defined. We are treating them as optional only to verify their types and will verify
        // their presense later.

        $optionalConfigTypes = array(
            'additional_where_condition' => 'array',
            'aggregate_formula' => 'string',
            'data_sort_order' => 'string',
            'disabled' => 'bool',
            'formula' => 'string',
            'module' => 'string',
            'order' => 'int',
            'precision' => 'int',
            'show_in_catalog' => 'bool',
            'timeseries_formula' => 'string',
            'use_timeseries_aggregate_tables' => 'bool',
            'weight_statistic_id' => 'string'
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
                    $this->additionalWhereConditionDefinition = $value;
                    break;
                case 'aggregate_formula':
                    $this->aggregateFormula = trim($value);
                    break;
                case 'data_sort_order':
                    // The sort order is specified in the JSON config file as the string
                    // representation of a PHP constant so convert it to an integer in order to
                    // properly use it. See https://php.net/manual/en/function.array-multisort.php
                    if (!empty($value)){
                        $this->setSortOrder(constant($value));
                    } else {
                        $this->setSortOrder(null);
                    }
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
                case 'show_in_catalog':
                    $this->showInCatalog = filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
                case 'weight_statistic_id':
                    $this->weightStatisticId = trim($value);
                    break;
                case 'hidden_groupbys':
                    if(is_array($value)) {
                        $this->hiddenGroupBys = $value;
                    }
                    break;
                default:
                    $this->logger->notice(
                        sprintf("Unknown key in definition for realm '%s' statistic '%s': '%s'", $this->realm->getName(), $this->id, $key)
                    );
                    break;
            }
        }

        // Ensure the formulas are set properly

        if (
            (null === $this->formula && (null === $this->aggregateFormula || null === $this->timeseriesFormula)) ||
            (null !== $this->formula && (null !== $this->aggregateFormula || null !== $this->timeseriesFormula))
        ){
            $this->logAndThrowException("Must define either 'formula' or ('aggregate_formula' and 'timeseries_formula')");
        }

        $this->variableStore = new VariableStore(
            array(
                'STATISTIC_ID' => $this->id,
                'WEIGHT_STATISTIC_ID' => $this->weightStatisticId
            ),
            $logger
        );
    }

    /**
     * @see iStatistic::getRealm()
     */

    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * @see iStatistic::getId()
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * @see iStatistic::getName()
     */

    public function getName($includeUnit = true)
    {
        // Include the unit if the unit is not found in the statistic name
        if ( $includeUnit && false === strpos($this->name, $this->unit) ) {
            return $this->realm->getVariableStore()->substitute(sprintf("%s (%s)", $this->name, $this->unit));
        } else {
            return $this->realm->getVariableStore()->substitute($this->name);
        }
    }

    /**
     * @see iStatistic::getHtmlDescription()
     */

    public function getHtmlDescription()
    {
        return $this->realm->getVariableStore()->substitute($this->description);
    }

    /**
     * @see iStatistic::getHtmlNameAndDescription()
     */

    public function getHtmlNameAndDescription()
    {
        return sprintf("<b>%s</b>: %s", $this->getName(), $this->getHtmlDescription());
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

    public function getFormula(\DataWarehouse\Query\iQuery $query = null)
    {
        // If no query was specified return the unmodified formula. If a query was specified, return
        // the appropriate formula based on whether this is an aggregate or timeseries query.

        if ( null === $query ) {
            if ( null === $this->formula ) {
                throw new \Exception(
                    sprintf("Key 'formula' not specified for statistic %s and Query not provided to getFormula()", $this)
                );
            }
            return sprintf('%s AS %s', $this->formula, $this->id);
        } else {
            // Update the variable store with the most recent values in the query class as they may
            // change dynamically.
            $queryVariableStore = $query->updateVariableStore();
            $formula = null;
            if ( null === $this->aggregateFormula && null === $this->timeseriesFormula ) {
                $formula = $this->formula;
            } elseif ( $query->isAggregate() ) {
                $formula = $this->aggregateFormula;
            } elseif ( $query->isTimeseries() ) {
                $formula = $this->timeseriesFormula;
            }
            return sprintf('%s AS %s', $this->variableStore->substitute($queryVariableStore->substitute($formula)), $this->id);
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

        if ( null !== $sortOrder && ! in_array($sortOrder, $validSortOrders) ) {
            $this->logAndThrowException(sprintf("Invalid sort option: %d", $sortOrder));
        }

        $this->sortOrder = $sortOrder;
    }

    /**
     * @see iStatistic::getSortOrder()
     */

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @see iStatistic::getModuleName()
     */

    public function getModuleName()
    {
        return $this->moduleName;
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
        if ( null === $this->additionalWhereConditionDefinition ) {
            return null;
        } else {
            list($leftCol, $operation, $rightCol) = $this->additionalWhereConditionDefinition;
            return new \DataWarehouse\Query\Model\WhereCondition($leftCol, $operation, $rightCol);
        }
    }

    /**
     * @see iStatistic::usesTimePeriodTablesForAggregate()
     */

    public function usesTimePeriodTablesForAggregate()
    {
        return $this->useTimeseriesAggregateTables;
    }

    /**
     * @see iStatistic::getWeightStatisticId()
     */

    public function getWeightStatisticId()
    {
        return $this->weightStatisticId;
    }

    /**
     * @see iRealm::showInMetricCatalog()
     */

    public function showInMetricCatalog()
    {
        return $this->showInCatalog;
    }

    /**
     * @see iStatistic::getHiddenGroupBys()
     */

    public function getHiddenGroupBys()
    {
        return $this->hiddenGroupBys;
    }

    /**
     * @see iStatistic::__toString()
     */

    public function __toString()
    {
        return sprintf("%s->Statistic(id=%s, formula=%s)", $this->realm, $this->id, $this->formula);
    }
}
