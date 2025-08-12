<?php
/**
 * Base class for defining a data warehouse query.
 *
 * NOTE: This class is NOT meant to be instantiated directly, but through a child class such as
 *       AggregateQuery or TimeseriesQuery.
 *
 * @see iQuery
 */

namespace DataWarehouse\Query;

use CCR\Log;
use Configuration\XdmodConfiguration;
use Exception;

use CCR\Loggable;
use CCR\DB;
use CCR\DB\PDODB;
use FilterListHelper;
use Models\Services\Parameters;
use ETL\VariableStore;
use Psr\Log\LoggerInterface;
use Realm\Realm;
use Realm\Statistic;

class Query extends Loggable
{
    public $roleParameterDescriptions;
    public $filterParameterDescriptions;
    private $pdoparams;
    private $pdoindex;
    protected $sortInfo;

    /**
     * @var Realm The Realm that this query will provide data for.
     */

    protected $realm = null;

    /**
     * Tracks whether or not role restrictions have been applied to this query.
     *
     * Note that this does not reflect whether or not the role restrictions
     * will affect the results returned. If the query has been sufficiently
     * resticted by the user, the restrictions will be redundant.
     *
     * @var boolean
     */
    private $restrictedByRoles = false;

    /**
     * Tracks whether or not role restrictions that potentially include a wide
     * range of data have been applied to this query.
     *
     * Note that this does not reflect whether or not the role restrictions
     * will affect the results returned. If the query has been sufficiently
     * resticted by the user, the restrictions will be redundant.
     *
     * @var boolean
     */
    private $restrictedByWideRoles = false;

    /**
     * The set of role parameters applied to this query, if any.
     *
     * Each entry contains the GroupBy object and dimension values.
     *
     * @var array
     */
    private $roleRestrictions = array();

    /**
     * The set of role (global filter) parameters applied to this query, if any.
     *
     * Each entry contains the GroupBy object and dimension values.
     *
     * @var array
     */
    private $roleParameters = array();

    /**
     * The set of (local) filter parameters applied to this query, if any.
     *
     * Each entry contains the GroupBy object and dimension values.
     *
     * @var array
     */
    private $filterParameters = array();

    /**
     * @var VariableStore Collection of variable names and values available for substitution in SQL
     *   (or other) strings.
     */

    protected $variableStore = null;

    /**
     * @var string The name of the aggregation unit for this query (e.g., day, month, etc.)
     */

    protected $aggregationUnitName = null;

    private $leftJoins = array();

    /**
     * @var bool True if this query should use DISTINCT.
     */
    private $isDistinct = false;
    protected array $parameters;

    public function __construct(
        $realmId,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        $statisticId = null,
        array $parameters = array(),
        LoggerInterface $logger = null
    ) {
        // If the logger was not passed in, create one specifically for the query logs

        if ( null === $logger ) {
            try {
                $sqlDebug = \xd_utilities\filter_var(
                    \xd_utilities\getConfiguration('general', 'sql_debug_mode'),
                    FILTER_VALIDATE_BOOLEAN
                );
            } catch (Exception $e) {
                $sqlDebug = false;
            }
            $logger = \CCR\Log::factory(
                'datawarehouse.query',
                array(
                    'console' => false,
                    'db' => false,
                    'mail' => false,
                    'file' => LOG_DIR . '/query.log',
                    'fileLogLevel' => $sqlDebug ? Log::DEBUG : Log::NOTICE
                )
            );
        }

        parent::__construct($logger);

        $this->variableStore = new VariableStore();
        $this->pdoparams = array();
        $this->pdoindex = 0;
        $this->realm = Realm::factory($realmId, $logger);

        $this->logger->debug(sprintf('Start creating Query %s', $this));

        $this->_aggregation_unit = \DataWarehouse\Query\TimeAggregationUnit::factory(
            $aggregationUnitName,
            $startDate,
            $endDate,
            $this->realm->getAggregateTablePrefix()
        );
        $this->aggregationUnitName = $this->_aggregation_unit->getUnitName();

        $this->setDataTable(
            $this->realm->getAggregateTableSchema(),
            sprintf('%s%s', $this->realm->getAggregateTablePrefix(false), $this->aggregationUnitName)
        );

        $this->setDuration($startDate, $endDate);

        if ( ! empty($groupById) ) {
            $this->setGroupBy($groupById);
        }
        $this->setParameters($parameters);

        if ( ! empty($statisticId) ) {
            $this->setStat($statisticId);
        }

        $this->roleParameterDescriptions = array();
        $this->filterParameterDescriptions = array();
        $this->logger->debug(sprintf('Created Query %s', $this));
    }

    /**
     * @see iQuery::updateVariableStore()
     */

    public function updateVariableStore()
    {
        $this->variableStore->overwrite('QUERY_TYPE', $this->getQueryType());
        $this->variableStore->overwrite('DATE_TABLE_ID_FIELD', sprintf('%s.id', $this->getDateTable()->getAlias()));
        $this->variableStore->overwrite('MIN_DATE_ID', $this->getMinDateId());
        $this->variableStore->overwrite('MAX_DATE_ID', $this->getMaxDateId());
        $this->variableStore->overwrite('START_DATE_TS', $this->getStartDateTs());
        $this->variableStore->overwrite('END_DATE_TS', $this->getEndDateTs());
        $this->variableStore->overwrite('DURATION_FORMULA', (string) $this->getDurationFormula());
        $this->variableStore->overwrite('AGGREGATION_UNIT', $this->getAggregationUnitName());
        $this->variableStore->overwrite('ORGANIZATION_NAME', ORGANIZATION_NAME);

        return $this->variableStore;
    }

    /**
     * @see iQuery::getVariableStore()
     */

    public function getVariableStore()
    {
        return $this->variableStore;
    }

    public $_db_profile = 'datawarehouse'; //The name of the db settings in portal_settings.ini

    /**
     * @return string The short identifier for the realm that this query is constructed for.
     *   This is named getRealmName() for historical reasons.
     */

    public function getRealmName()
    {
        return $this->realm->getId();
    }

    /**
     * @see iQuery::getRealm()
     */

    public function getRealm()
    {
        return $this->realm;
    }

    protected $_data_table;
    protected $_date_table;

    /**
     * @var GroupBy|null The primary group by passed in via the constructor and set by setGroupBy().
     */

    protected $_group_by = null;

    public function groupBy()
    {
        return $this->_group_by;
    }

    /**
     * @var array An array of additional GroupBy objects added via addGroupBy()
     */

    public $_group_bys = array();
    public function getGroupBys()
    {
        return $this->_group_bys;
    }

    public $_stats = array();
    public function getStats()
    {
        return $this->_stats;
    }

    /**
     * @var Statistic|null The primary statistic by passed in via the constructor and set by setStat().
     */

    protected $_main_stat_field = null;

    private $_tables = array();
    private $_fields = array();

    /**
     * @var array An array of additional Statistic objects added via addStatField()
     */

    protected $_stat_fields = array();
    private $_where_conditions = array();
    private $_stat_where_conditions = array();
    private $_groups = array();
    private $_orders = array();

    protected $_aggregation_unit;

    public function getAggregationUnitName()
    {
        return $this->aggregationUnitName;
    }

    public function getAggregationUnit()
    {
        return $this->_aggregation_unit;
    }

    protected $_start_date;
    protected $_end_date;

    public $_start_date_ts;
    public $_end_date_ts;

    public function getStartDate()
    {
        return $this->_start_date;
    }
    public function getEndDate()
    {
        return $this->_end_date;
    }

    public function getStartDateTs()
    {
        return $this->_start_date_ts;
    }
    public function getEndDateTs()
    {
        return $this->_end_date_ts;
    }

    protected $_duration_formula;

    public function execute($limit = 10000000)
    {
        $query_string = $this->getQueryString($limit);

        $debug = PDODB::debugging();
        if ($debug == true) {
            $class = get_class($this);
            $this->logger->debug(sprintf("%s: \n%s", $class, $query_string));
        }

        $time_start = microtime(true);
        $results = DB::factory($this->_db_profile)->query($query_string, $this->pdoparams);

        $time_end = microtime(true);
        $return = array();
        if ($this->_main_stat_field != null) {
            $stat = $this->_main_stat_field->getId();
            $stat_weight = $this->_main_stat_field->getWeightStatName();

            $sort_option = $this->_group_by->getSortOrder();

            if (isset($sort_option)) {
                $sort_option = $this->_main_stat_field->getSortOrder();
            }
            if (isset($sort_option)) {
                $stat_column = array();
                $name_column = array();
                foreach ($results as $key => $row) {
                    $stat_column[$key]  = $row[$stat];
                    $name_column[$key]  = $row['name'];
                }

                // Sort the results with stat_column descending
                array_multisort($stat_column, $sort_option, $name_column, SORT_ASC, $results);
            }
            $sem_name = Realm::getStandardErrorStatisticFromStatistic(
                $stat
            );
            if (count($results) > 0) {
                $return[$stat] = array();
                $return['weight'] = array();
            }
            $index = 0;
            foreach ($results as $result) {
                if (!isset($result['id'])) {
                    $result['id'] = -1;
                }
                if (!isset($result['name'])) {
                    $result['name'] = 'NA';
                    $result['short_name'] = 'NA';
                }
                if ($index < $limit) {
                    $return['id'][] = $result['id'];
                    $return['name'][] =$result['name'];
                    $return['short_name'][] =$result['short_name'];
                    $return[$stat][] = $result[$stat];
                    if (isset($result[$sem_name])) {
                        $return[$sem_name][] = $result[$sem_name];
                    } else {
                        $return[$sem_name][] = 0;
                    }
                    $return['weight'][] = $result[$stat_weight];
                } else {
                    if ($index == $limit) {
                        $return['id'][] = -1;
                        $return['name'][] = 'Other';
                        $return['short_name'][] = 'Other';
                        $return[$stat][] = $result[$stat];
                        if (isset($result[$sem_name])) {
                            $return[$sem_name][] = $result[$sem_name];
                        } else {
                            $return[$sem_name][] = 0;
                        }
                        $return['weight'][] = $result[$stat_weight];
                    }
                    $return['id'][$limit] = -1;
                    $return['name'][$limit] = 'Other';
                    $return['short_name'][$limit] = 'Other';
                    $return[$stat][$limit] += $result[$stat];
                    $return[$sem_name][$limit] = 0;
                    $return[$stat]['weight'] += $result[$stat_weight];
                }
                $index++;
            }
        } else {
            $stat_fields = $this->getStatFields();
            $fields = $this->getFields();

            foreach ($results as $result) {

                foreach ($fields as $field_key => $field) {
                    $return[$field_key][] = $result[$field_key];
                }
                foreach ($stat_fields as $stat_key => $stat_field) {
                    $return[$stat_key][] = $result[$stat_key];
                }
            }
        }

        $return['query_string'] = $query_string;
        $return['query_time'] = $time_end - $time_start;
        $return['count'] = count($results);
        return $return;
    }

    public function addTable(\DataWarehouse\Query\Model\Table $table)
    {
        $this->_tables[$table->getAlias()->getName()] = $table;
    }
    public function getTables()
    {
        return $this->_tables;
    }

    public function addLeftJoin(\DataWarehouse\Query\Model\Table $table, \DataWarehouse\Query\Model\WhereCondition $where)
    {
        $this->leftJoins[$table->getAlias()->getName()] = array($table, $where);
    }

    public function addField(\DataWarehouse\Query\Model\Field $field)
    {
        $this->_fields[$field->getAlias()->getName()] = $field;
    }
    public function getFields()
    {
        return $this->_fields;
    }
    public function addStatField(Statistic $field)
    {
        $this->logger->debug(sprintf("%s Add statistic: '%s'", $this, $field->getId()));

        $this->_stat_fields[$field->getId()] = $field;

        $addnlwhere = $field->getAdditionalWhereCondition();
        if ($addnlwhere !== null) {
            $this->_stat_where_conditions[$addnlwhere->__toString()] = $addnlwhere;

            // The per-statistics where clauses are ORed together. Compute the full clause
            // and add it as a single where condition.
            $extra = "(".implode("\n or ", $this->_stat_where_conditions) . ")";
            $this->_where_conditions['extra'] = $extra;
        }
    }
    public function getStatFields()
    {
        return $this->_stat_fields;
    }

    /**
     * Add a where condition to the query and add the data to the pdo parameters. This
     * function should be used when the right hand side of the where condition is untrused
     * user supplied data.
     *
     * Note this function does not handle pdo parameterization of 'IN' conditions.
     */
    public function addPdoWhereCondition(\DataWarehouse\Query\Model\WhereCondition $where_condition)
    {
        // key on the non-parameterized form since the substitution string is different every time.
        $key = $where_condition->__toString();

        if (isset($this->_where_conditions[$key])) {
            return;
        }

        $namedParam = $this->getNamedParameterMarker($where_condition->_right);

        $this->_where_conditions[$key] = new \DataWarehouse\Query\Model\WhereCondition(
            $where_condition->_left,
            $where_condition->_operation,
            $namedParam
        );
    }

    public function addWhereCondition(\DataWarehouse\Query\Model\WhereCondition $where_condition)
    {
        $this->_where_conditions[$where_condition->__toString()] = $where_condition;
    }
    public function getWhereConditions()
    {
        return $this->_where_conditions;
    }

    public function addGroup(\DataWarehouse\Query\Model\Field $field)
    {
        $this->_groups[$field->getAlias()->getName()] = $field;
    }
    public function getGroups()
    {
        return $this->_groups;
    }

    public function prependOrder(\DataWarehouse\Query\Model\OrderBy $field)
    {
        $this->_orders = array_merge(array($field->getField()->getQualifiedName(false) => $field), $this->_orders);
    }
    public function addOrder(\DataWarehouse\Query\Model\OrderBy $field)
    {
        $this->_orders[$field->getField()->getQualifiedName(false)] = $field;
    }
    public function getOrders()
    {
        return $this->_orders;
    }
    public function clearOrders()
    {
        unset($this->_orders);
        $this->_orders = array();
    }
    public function getSelectFields()
    {
        $fields = $this->getFields();
        $stat_fields = $this->getStatFields();

        $select_fields = array();
        foreach ($fields as $field_key => $field) {
            $select_fields[$field_key] = $field->getQualifiedName(true);
        }
        foreach ($stat_fields as $field_key => $stat_field) {
            $select_fields[$field_key] = $stat_field->getFormula($this);
        }
        return $select_fields;
    }

    public function getSelectTables()
    {
        $tables = $this->getTables();
        $select_tables = array();
        foreach ($tables as $table) {
            $select_tables[] = $table->getQualifiedName(true, true);
        }
        return $select_tables;
    }

    public function getSelectOrderBy()
    {
        $orders = $this->getOrders();
        $select_order_by = array();
        foreach ($orders as $order_key => $order) {
            $select_order_by[] = $order->getField()->getQualifiedName(false).' '.$order->getOrder();
        }
        return $select_order_by;
    }

    public function getDurationFormula()
    {
        return $this->_duration_formula;
    }

    public function setDurationFormula(\DataWarehouse\Query\Model\Field $field)
    {
        $this->_duration_formula = $field;
    }

    protected $_min_date_id;
    protected $_max_date_id;

    public function getMinDateId()
    {
        return $this->_min_date_id;
    }

    public function getMaxDateId()
    {
        return $this->_max_date_id;
    }

    public function getRawStatement($limit = null, $offset = null, $extraHavingClause = null)
    {
        $query_string = $this->getQueryString($limit, $offset, $extraHavingClause);
        return DB::factory($this->_db_profile)->query($query_string, $this->pdoparams, true);
    }

    public function getCount()
    {
        $count_result = DB::factory($this->_db_profile)->query($this->getCountQueryString(), $this->pdoparams);

        return $count_result[0]['row_count'];
    }

    public function getDimensionValues()
    {
        return DB::factory($this->_db_profile)->query($this->getDimensionValuesQuery(), $this->pdoparams);
    }

    public function getDimensionValuesQuery()
    {
        $groups = $this->getGroups();
        if (empty($groups)) {
            $this->logAndThrowException("Cannot build dimension values query without specifying a dimension.");
        }

        $select_tables = $this->getSelectTables();

        // MetricExplorer::getDimensionValues() expects the fields returned by this query to be
        // named "id", "name", "short_name".  Construct the list of dimension fields without the
        // alias so we can add our own. We do not call getSelectFields() because that generates
        // values with the alias of the form "<field> AS <alias>" and the alias is qualified with
        // the dimension name (e.g., "person.id AS person_id").

        $select_fields = array();
        foreach ($this->getFields() as $field_key => $field) {
            $select_fields[$field_key] = $field->getQualifiedName();
        }

        $primaryGroupById = $this->_group_by->getId();
        $id_field = $select_fields[ sprintf('%s_id', $primaryGroupById) ];
        $name_field = $select_fields[ sprintf('%s_name', $primaryGroupById) ];
        $short_name_field = $select_fields[ sprintf('%s_short_name', $primaryGroupById) ];

        $groups_str = implode(', ', $groups);

        $orders = $this->getOrders();
        $num_orders = count($orders);
        $orders_exist = $num_orders > 0;
        $orders_field_alias_clause = ' AS _dimensionOrderValue';
        $as_clause_regex = '/\s+AS\s+\S+\s*$/i';
        if ($orders_exist) {
            $orders_field = reset($orders)->getField()->getQualifiedName(false) . $orders_field_alias_clause;
        } else {
            $orders_field = preg_replace($as_clause_regex, $orders_field_alias_clause, $name_field, 1, $numAsSubsitutionsDone);
            if ($numAsSubsitutionsDone === 0) {
                $orders_field .= $orders_field_alias_clause;
            }
        }

        // This method is only called from MetricExplorer::getDimensionValues() which constructs an
        // aggregate query with start and end dates of NULL, meaning that the duration table is
        // never added to the query by setDuration() leaving only the data and dimension tables.
        // Relying on the table index seems awefully fragile for a dynamic query class. Note that
        // changing the order in which setStat() or setGroupBy() are called will change the table
        // order.

        $dimension_table = $select_tables[1];

        $restriction_wheres = array();
        $dimension_group_by = $this->groupBy();
        $dimension_group_by_id = $dimension_group_by->getId();
        $id_field_without_alias = preg_replace($as_clause_regex, '', $id_field);
        if ($this->restrictedByRoles) {
            foreach ($this->roleRestrictions as $restriction_dimension_name => $restriction_data) {
                $restriction_dimension_values_str = implode(', ', $restriction_data['dimensionValues']);

                $filter_table = FilterListHelper::getFullTableName($this, $dimension_group_by, $restriction_data['groupBy']);

                $restriction_wheres[] = sprintf(
                    "%s IN ( SELECT %s.%s FROM %s WHERE %s.%s IN (%s) )",
                    $id_field_without_alias,
                    $filter_table,
                    $dimension_group_by_id,
                    $filter_table,
                    $filter_table,
                    $restriction_dimension_name,
                    $restriction_dimension_values_str
                );

            }
        } else {
            $filter_table = FilterListHelper::getFullTableName($this, $dimension_group_by);
            $restriction_wheres[] = sprintf(
                "%s IN ( SELECT %s.%s FROM %s )",
                $id_field_without_alias,
                $filter_table,
                $dimension_group_by_id,
                $filter_table
            );
        }

        $restriction_wheres_str = implode(' OR ', $restriction_wheres);

        $format = <<<SQL
SELECT
  %s AS id,
  %s AS name,
  %s AS short_name,
  %s
FROM %s
WHERE %s
GROUP BY %s
%s
SQL;
        $dimension_values_query = sprintf(
            $format,
            $id_field,
            $name_field,
            $short_name_field,
            $orders_field,
            $dimension_table,
            $restriction_wheres_str,
            $groups_str,
            ( $orders_exist ? "ORDER BY " . implode(', ', $this->getSelectOrderBy()) : "" )
        );

        $this->logger->debug(
            sprintf("%s %s()\n%s", $this, __FUNCTION__, $dimension_values_query)
        );

        return $dimension_values_query;
    }

    public function getQueryString($limit = null, $offset = null, $extraHavingClause = null)
    {
        $wheres = $this->getWhereConditions();
        $groups = $this->getGroups();

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        if ( 0 == count($select_fields) ) {
            $this->logAndThrowException("Cannot generate query string with no select fields");
        }

        $select_order_by = $this->getSelectOrderBy();

        $format = <<<SQL
SELECT%s
  %s
FROM
  %s%s
WHERE
  %s
%s%s%s%s
SQL;

        $data_query = sprintf(
            $format,
            ( $this->isDistinct ? ' DISTINCT' : '' ),
            implode(",\n  ", $select_fields),
            implode(",\n  ", $select_tables),
            ( "" == $this->getLeftJoinSql() ? "" : "\n" . $this->getLeftJoinSql() ),
            implode("\n  AND ", $wheres),
            ( count($groups) > 0 ? "GROUP BY " . implode(",\n  ", $groups) : "" ),
            ( null !== $extraHavingClause ? "\nHAVING $extraHavingClause" : "" ),
            ( count($select_order_by) > 0 ? "\nORDER BY " . implode(",\n  ", $select_order_by) : "" ),
            ( null !== $limit && null !== $offset ? "\nLIMIT $limit OFFSET $offset" : "" )
        );

        $this->logger->debug(
            sprintf("%s %s()\n%s", $this, __FUNCTION__, $data_query)
        );

        return $data_query;
    }

    public function getCountQueryString()
    {
        $wheres = $this->getWhereConditions();
        $groups = $this->getGroups();

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        $format = <<<SQL
SELECT
  COUNT(*) AS row_count
FROM (
  SELECT
  %s AS total
  FROM
    %s
  WHERE
    %s
  %s
) AS a WHERE a.total IS NOT NULL
SQL;
        $data_query = sprintf(
            $format,
            ( $this->isDistinct ? 'DISTINCT ' . implode(', ', $select_fields) . ', 1' : 'SUM(1)' ),
            implode(",\n    ", $select_tables),
            implode("\n    AND ", $wheres),
            ( count($groups) > 0 ? "GROUP BY\n    " . implode(",\n    ", $groups) : "" )
        );

        $this->logger->debug(
            sprintf("%s %s()\n%s", $this, __FUNCTION__, $data_query)
        );

        return $data_query;
    }

    /**
     * Store a bound parameter for the query and return the named parameter
     * marker that should be used in the SQL query.
     *
     * @param the value to bind to the query
     * @return string a named parameter marker.
     */
    protected function getNamedParameterMarker($value)
    {
        $pdosubst = ':subst' . $this->pdoindex;
        $this->pdoparams[$pdosubst] = $value;
        $this->pdoindex += 1;
        return $pdosubst;
    }

    public function setParameters(array $parameters = array())
    {
        $this->parameters = $parameters;
        foreach ($parameters as $parameter) {
            if ($parameter instanceof \DataWarehouse\Query\Model\Parameter) {

                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());
                if ($parameter->getOperator() == "=") {
                    $rightField =  ":subst" . $this->pdoindex;
                    $this->pdoparams[$rightField] = $parameter->getValue();
                    $this->pdoindex += 1;
                } else {
                    // TODO - work out how to use PDO parameters for IN queries.
                    $rightField = $parameter->getValue();
                }

                $this->addWhereCondition(
                    new \DataWarehouse\Query\Model\WhereCondition(
                        $leftField,
                        $parameter->getOperator(),
                        $rightField
                    )
                );
            }
        }
    }

    /**
     * Copy the parameters and role parameters from another query class
     * The where conditions and role parameters from the other class will
     * overwrite any existing settings in this class.
     */
    public function cloneParameters(Query $other)
    {
        $this->_where_conditions = $other->_where_conditions;
        $this->parameters = $other->parameters;
        $this->restrictedByRoles = $other->restrictedByRoles;
        $this->roleRestrictions = $other->roleRestrictions;
        $this->roleParameters = $other->roleParameters;
        $this->roleParameterDescriptions = $other->roleParameterDescriptions;
    }

    protected function getLeftJoinSql()
    {
        $stmt = '';
        foreach ($this->leftJoins as $joincond) {
            $stmt .= ' LEFT JOIN ' . $joincond[0]->getQualifiedName(true, true) .
                ' ON ' . $joincond[1];
        }
        return $stmt;
    }

    private function getParameters(array $parameters = array())
    {
        $whereConditions = array();

        foreach ($parameters as $parameter) {
            if ($parameter instanceof \DataWarehouse\Query\Model\Parameter) {
                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());

                $whereConditions[] = new \DataWarehouse\Query\Model\WhereCondition($leftField, $parameter->getOperator(), $parameter->getValue());
            }
        }

        return $whereConditions;
    }

    public function addParameters(array $parameters = array())
    {
        $this->parameters = array_merge($parameters, $this->parameters);

        foreach ($parameters as $parameter) {
            if ($parameter instanceof \DataWarehouse\Query\Model\Parameter) {

                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());
                $rightField = $parameter->getValue();

                $this->addWhereCondition(
                    new \DataWarehouse\Query\Model\WhereCondition(
                        $leftField,
                        $parameter->getOperator(),
                        $rightField
                    )
                );
            }
        }
    }
    protected function setDataTable($schemaname, $tablename, $join_index = '')
    {
        $this->_data_table = new \DataWarehouse\Query\Model\Table(
            new \DataWarehouse\Query\Model\Schema($schemaname),
            $tablename,
            $this->realm->getAggregateTableAlias(),
            $join_index
        );
        $this->addTable($this->_data_table);
    }
    public function getDataTable()
    {
        return $this->_data_table;
    }
    public function getDateTable()
    {
        return $this->_date_table;
    }

    public function getTitle($group_info_only = false)
    {
        $groupById = $this->groupBy()->getId();
        $groupByName = $this->groupBy()->getName();

        if ( $group_info_only ) {
            return sprintf(
                '%s stats%s%s',
                $groupByName,
                ( $groupById === 'none' ? ' Summary' : ': by ' . $groupByName )
            );
        } else {
            return sprintf(
                '%s%s',
                $this->_main_stat_field->getName(),
                ( $groupById === 'none' ? '' : ': by ' . $groupByName )
            );
        }
    }

    public function getFilterParametersTitle()
    {
        return implode("; ", array_unique($this->filterParameterDescriptions));
    }

    /**
     * Check if the query is limited by its role restrictions.
     *
     * This is used to check if the role restrictions for this query, if any,
     * will possibly have any effect on the results. They will not if the user
     * has specified stricter criteria than their role restrictions.
     *
     * @return boolean True if any role restrictions logically restrict the
     *                 results of the query further than any specified filters.
     */
    public function isLimitedByRoleRestrictions()
    {
        // If this query has no role restrictions, it can't be limited by them.
        if (!$this->restrictedByRoles) {
            return false;
        }

        // Check each dimension the query is being filtered on. If the filters
        // restrict a given dimension as much or further than the role
        // restrictions do, then the role restrictions aren't limiting the
        // query.
        $filterSets = array(
            $this->roleParameters,
            $this->filterParameters,
        );
        foreach ($filterSets as $filterSet) {
            foreach ($filterSet as $dimensionName => $dimensionData) {
                if (!array_key_exists($dimensionName, $this->roleRestrictions)) {
                    continue;
                }

                $filterValuesNotInRoleRestrictions = array_diff(
                    $dimensionData['dimensionValues'],
                    $this->roleRestrictions[$dimensionName]['dimensionValues']
                );

                if (empty($filterValuesNotInRoleRestrictions)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function setFilters($user_filters)
    {
        $filters = array();
        if (!isset($user_filters->data) || !is_array($user_filters->data)) {
            $user_filters->data = array();
        }
        foreach ($user_filters->data as $user_filter) {
            if (isset($user_filter->checked) && $user_filter->checked == 1) {
                $filters[$user_filter->id] = $user_filter;
            }
        }

        //combine the filters and group them by dimension
        $groupedFilters = array();
        foreach ($filters as $filter) {
            if (isset($filter->checked) && $filter->checked != 1) {
                continue;
            }

            if (!isset($groupedFilters[$filter->dimension_id])) {
                $groupedFilters[$filter->dimension_id] = array();
            }
            $groupedFilters[$filter->dimension_id][] = $filter->value_id;
        }

        $filterParameters = array();
        $filterParameterDescriptions = array();
        foreach ($groupedFilters as $filter_parameter_dimension => $filterValues) {
            try {
                $group_by_instance = $this->realm->getGroupByObject($filter_parameter_dimension);
            } catch (\Exception $ex) {
                // Specifically catch when a realm does not have a groupby, this allows
                // that specific realm to not have the filter
                continue;
            }
            $param = array($filter_parameter_dimension.'_filter' => implode(',', $filterValues));
            $this->addParameters($group_by_instance->generateQueryFiltersFromRequest($param));
            $filterParameters[$filter_parameter_dimension] = array(
                'groupBy' => $group_by_instance,
                'dimensionValues' => $filterValues,
            );
            $filterParameterDescriptions = array_merge($filterParameterDescriptions, $group_by_instance->generateQueryParameterLabelsFromRequest($param));
        }

        $this->filterParameters = $filterParameters;
        $this->filterParameterDescriptions = $filterParameterDescriptions;
    }

    /* Used to set the query to return data for multiple roles. The where
     * conditions for each role are anded together and each set is ORed
     * together in the query.
     *
     * The role parameter descriptions are not updated by this function.
     *
     * @param array   $rolearray an array of Role objects that will be used to
     *                           determine which parameters are added to this
     *                           query.
     * @param \XDUser $user      the user that is running this query.
     *
     * @return array The set of role parameters applied to this query, if any.
     *               Each entry contains the group by object and values.
     */
    public function setMultipleRoleParameters($rolearray, $user)
    {
        $allwheres = array();
        $role_parameters = array();
        $wide_role_parameter_found = false;

        // Check whether multiple service providers are supported or not.
        try {
            $multiple_providers_supported = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
        } catch (Exception $e) {
            $multiple_providers_supported = false;
        }

        foreach ($rolearray as $role) {
            $roleparams = Parameters::getParameters($user, $role);

            if (count($roleparams) == 0) {
                // Empty where condition translates to a "WHERE 1". There is no need to add the other
                // where conditions associated with the other roles since the different
                // role where conditions are ORed together.
                return array();
            }

            foreach ($roleparams as $role_parameter_dimension => $role_parameter_value) {
                // If this is a service provider parameter and only one service
                // provider is supported, the user effectively has access to
                // all data. There is no need to add any where conditions.
                $is_provider_dimension = $role_parameter_dimension === 'provider';
                if ($is_provider_dimension && !$multiple_providers_supported) {
                    return array();
                }

                if (is_array($role_parameter_value)) {
                    $param = array($role_parameter_dimension.'_filter' => implode(',', $role_parameter_value));
                    $role_parameter_values = $role_parameter_value;
                } else {
                    $param = array($role_parameter_dimension.'_filter' => $role_parameter_value);
                    $role_parameter_values = array($role_parameter_value);
                }

                // Get the group by object associated with this dimension.
                // If it does not exist for this realm, skip this parameter.
                if (!$this->realm->groupByExists($role_parameter_dimension)){
                    continue;
                }
                else {
                    $group_by_instance = $this->realm->getGroupByObject($role_parameter_dimension);
                    $allwheres[] = "(" .
                        implode(
                            " AND ",
                            $this->getParameters($group_by_instance->generateQueryFiltersFromRequest($param))
                        )
                        . ")";
                }

                if (array_key_exists($role_parameter_dimension, $role_parameters)) {
                    $role_parameters_value_list = &$role_parameters[$role_parameter_dimension]['dimensionValues'];
                    foreach ($role_parameter_values as $role_parameter_value) {
                        if (in_array($role_parameter_value, $role_parameters_value_list)) {
                            continue;
                        }

                        $role_parameters_value_list[] = $role_parameter_value;
                    }
                } else {
                    $role_parameters[$role_parameter_dimension] = array(
                        'groupBy' => $group_by_instance,
                        'dimensionValues' => $role_parameter_values,
                    );
                }

                // If this parameter is on a wide-ranging dimension,
                // note that such a parameter has been added.
                if ($is_provider_dimension) {
                    $wide_role_parameter_found = true;
                }
            }
        }

        if (count($allwheres) == 0) {
            return array();
        }

        $this->_where_conditions["allroles"] = "(" . implode(" OR ", $allwheres) . ")";
        $this->restrictedByRoles = true;
        $this->restrictedByWideRoles = $wide_role_parameter_found;
        $this->roleRestrictions = $role_parameters;

        return $role_parameters;
    }

    public function setRoleParameters($role_parameters = array())
    {
        $groupedRoleParameters = array();
        $roleParameterDescriptions = array();
        foreach ($role_parameters as $role_parameter_dimension => $role_parameter_value) {
            try{
                $group_by_instance = $this->realm->getGroupByObject($role_parameter_dimension);
            } catch (\Exception $ex){
                // Specifically catch when a realm does not have a groupby, this allows
                // that specific realm to not have the filter
                continue;
            }

            if (is_array($role_parameter_value)) {
                $param = array($role_parameter_dimension.'_filter' => implode(',', $role_parameter_value));
                $role_parameter_values = $role_parameter_value;
            } else {
                $param = array($role_parameter_dimension.'_filter' => $role_parameter_value);
                $role_parameter_values = array($role_parameter_value);
            }

            $this->addParameters($group_by_instance->generateQueryFiltersFromRequest($param));

            if (array_key_exists($role_parameter_dimension, $groupedRoleParameters)) {
                $role_parameters_value_list = &$groupedRoleParameters[$role_parameter_dimension]['dimensionValues'];
                foreach ($role_parameter_values as $current_role_parameter_value) {
                    if (in_array($current_role_parameter_value, $role_parameters_value_list)) {
                        continue;
                    }

                    $role_parameters_value_list[] = $current_role_parameter_value;
                }
            } else {
                $groupedRoleParameters[$role_parameter_dimension] = array(
                    'groupBy' => $group_by_instance,
                    'dimensionValues' => $role_parameter_values,
                );
            }

            $roleParameterDescriptions = array_merge($roleParameterDescriptions, $group_by_instance->generateQueryParameterLabelsFromRequest($param));
        }

        $this->roleParameters = $groupedRoleParameters;
        $this->roleParameterDescriptions = $roleParameterDescriptions;
    }

    protected function setGroupBy($group_by)
    {
        $this->logger->debug(
            sprintf("%s: Set primary group by: %s", $this, $group_by)
        );

        $this->_group_by = $this->realm->getGroupByObject($group_by);
        $this->_group_by->applyTo($this, $this->_data_table);
    }

    public function addGroupBy($group_by_name)
    {
        $this->logger->debug(
            sprintf("%s: Add group by: %s", $this, $group_by_name)
        );

        try {
            $group_by = $this->realm->getGroupByObject($group_by_name);
        } catch (Exceptions\UnavailableTimeAggregationUnitException $time_unit_exception) {
            $time_unit_exception->errorData['realm'] = $this->getRealmName();
            throw $time_unit_exception;
        }

        $this->_group_bys[$group_by_name] = $group_by;
        $group_by->applyTo($this, $this->_data_table, true);
        return $group_by;
    }

    public function addWhereAndJoin($where_col_name, $operation, $whereConstraint)
    {
        try {
            $group_by = $this->realm->getGroupByObject($where_col_name);
        } catch (Exceptions\UnavailableTimeAggregationUnitException $time_unit_exception) {
            $time_unit_exception->errorData['realm'] = $this->getRealmName();
            throw $time_unit_exception;
        }

        // Use the group by instance specific to the situation to
        // construct where clause and add it to the current query object
        return $group_by->addWhereJoin($this, $this->_data_table, $operation, $whereConstraint);
    }

    public function addFilter($group_by_name)
    {
        $group_by = $this->realm->getGroupByObject($group_by_name);

        return $group_by->filterByGroup($this, $this->_data_table);
    }
    public function addStat($stat_name)
    {
        if ($stat_name == '') {
            return null;
        }
        $statistic = $this->realm->getStatisticObject($stat_name);
        $this->_stats[ $statistic->getId() ] = $statistic;
        $this->addStatField($statistic);
        return $statistic;
    }

    public function addOrderBy($sort_group_or_stat_name, $sort_direction)
    {
        if (isset($this->_group_bys[$sort_group_or_stat_name])) {
            $this->_group_bys[$sort_group_or_stat_name]->addOrder($this, true, $sort_direction, false);
        } elseif (isset($this->_stat_fields[$sort_group_or_stat_name])) {
            $this->prependOrder(new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\Field($sort_group_or_stat_name), $sort_direction, $sort_group_or_stat_name));
        }
    }

    /**
     * Add an order by and set the sort info using a data description object.
     *
     * Code originally found in ComplexDataset and TimeseriesChart.
     *
     * @param object $data_description A data description object.
     */
    public function addOrderByAndSetSortInfo($data_description)
    {
        switch ($data_description->sort_type) {
            case 'value_asc':
                $this->addOrderBy($data_description->metric, 'asc');
                $this->sortInfo = array(
                array(
                    'column_name' => $data_description->metric,
                    'direction' => 'asc'
                )
                );
                break;

            case 'value_desc':
                $this->addOrderBy($data_description->metric, 'desc');
                $this->sortInfo = array(
                array(
                    'column_name' => $data_description->metric,
                    'direction' => 'desc'
                )
                );
                break;

            case 'label_asc':
                $this->addOrderBy($data_description->group_by, 'asc');
                $this->sortInfo = array(
                array(
                    'column_name' => $data_description->group_by,
                    'direction' => 'asc'
                )
                );
                break;

            case 'label_desc':
                $this->addOrderBy($data_description->group_by, 'desc');
                $this->sortInfo = array(
                array(
                    'column_name' => $data_description->group_by,
                    'direction' => 'desc'
                )
                );
                break;
        }
    }

    /**
     * Set the primary statistic and add all available statistics for the query realm as stat fields
     * in the generated query. If 'all' is provided as the statistic name, all available statistics
     * will be added to the query but no primary statistic will be set.
     *
     * @param string $stat The name of the statistic to set as the primary statistic.
     *
     * Note: 2019-08-15 This is currently only called from Query::__construct().
     */

    public function setStat($stat)
    {
        $permitted_statistics = $this->realm->getStatisticIds();

        if ($stat == 'all') {
            $this->_main_stat_field = null;
            foreach ($permitted_statistics as $stat_name) {
                $this->addStatField($this->realm->getStatisticObject($stat_name));
            }
        } else {
            if (!in_array($stat, $permitted_statistics)) {
                throw new \Exception(
                    sprintf("Statistic %s is not available for Group By %s", $stat, $this->_group_by !== null ? $this->_group_by->getId() : 'null')
                );
            }

            $this->logger->debug(
                sprintf("%s: Set primary statistic field: %s", $this, $stat)
            );

            $this->_main_stat_field = $this->realm->getStatisticObject($stat);
            foreach ($permitted_statistics as $stat_name) {
                $this->addStatField($this->realm->getStatisticObject($stat_name));
            }
        }
    }

    protected function setDuration($start_date, $end_date)
    {
        $start_date_given = $start_date !== null;
        $end_date_given = $end_date !== null;

        if ($start_date_given && strtotime($start_date) == false) {
            throw new \Exception("start_date must be a date");
        }
        if ($end_date_given && strtotime($end_date) == false) {
            throw new \Exception("end_date must be a date");
        }

        $this->_start_date = $start_date_given ? $start_date : '0000-01-01';
        $this->_end_date = $end_date_given ? $end_date : '9999-12-31';

        $start_date_parsed = date_parse_from_format('Y-m-d', $this->_start_date);
        $end_date_parsed = date_parse_from_format('Y-m-d', $this->_end_date);

        $this->_start_date_ts = mktime(
            $start_date_parsed['hour'],
            $start_date_parsed['minute'],
            $start_date_parsed['second'],
            $start_date_parsed['month'],
            $start_date_parsed['day'],
            $start_date_parsed['year']
        );
        $this->_end_date_ts = mktime(
            23,
            59,
            59,
            $end_date_parsed['month'],
            $end_date_parsed['day'],
            $end_date_parsed['year']
        );

        list($this->_min_date_id, $this->_max_date_id) = $this->_aggregation_unit->getDateRangeIds($this->_start_date, $this->_end_date);

        if (!$start_date_given && !$end_date_given) {
            return;
        }

        $this->_date_table = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), $this->_aggregation_unit.'s', 'duration');

        $this->addTable($this->_date_table);

        $date_id_field = new \DataWarehouse\Query\Model\TableField($this->_date_table, 'id');
        $data_table_date_id_field = new \DataWarehouse\Query\Model\TableField($this->_data_table, "{$this->_aggregation_unit}_id");

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $date_id_field,
                '=',
                $data_table_date_id_field
            )
        );
        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $data_table_date_id_field,
                'between',
                new \DataWarehouse\Query\Model\Field(
                    sprintf("%s and %s", $this->_min_date_id, $this->_max_date_id)
                )
            )
        );

        $duration_query = sprintf(
            "select sum(dd.hours) as duration from modw.%ss dd where dd.id between %s and %s",
            $this->aggregationUnitName,
            $this->_min_date_id,
            $this->_max_date_id
        );

        $duration_result = DB::factory($this->_db_profile)->query($duration_query);

        $this->setDurationFormula(
            new \DataWarehouse\Query\Model\Field(
                "(" . ( $duration_result[0]['duration'] == '' ? 1 : $duration_result[0]['duration'] ) . ")"
            )
        );
    }

    /**
     * @see iQuery::getDataSource()
     */

    public function getDataSource()
    {
        return $this->realm->getDatasource();
    }

    /**
     * @see iQuery::isAggregate()
     */

    public function isAggregate()
    {
        return ($this instanceof AggregateQuery);
    }

    /**
     * @see iQuery::isTimeseries()
     */

    public function isTimeseries()
    {
        return ($this instanceof TimeseriesQuery);
    }

    /**
     * Set the query to use DISTINCT or not.
     *
     * @param bool $distinct
     */
    protected function setDistinct($distinct)
    {
        $this->isDistinct = $distinct;
    }

    /**
     * Does this query use DISTINCT?
     *
     * @return bool True if the query uses DISTINCT.
     */
    public function isDistinct()
    {
        return $this->isDistinct;
    }

    /**
     * @see iQuery::__toString()
     */

    public function __toString()
    {
        $primaryGroupById = array();
        if ( null !== $this->_group_by ) {
            $primaryGroupById[] = $this->_group_by->getId();
        }
        $primaryStatisticId = array();
        if ( null !== $this->_main_stat_field ) {
            $primaryStatisticId[] = $this->_main_stat_field->getId();
        }

        return sprintf(
            "%s(%s, groupbys=(%s), statistics=(%s))",
            get_class($this),
            $this->realm->getId(),
            implode(',', array_merge($primaryGroupById, array_keys($this->_group_bys))),
            implode(',', array_keys($this->_stat_fields))
        );
    }

    /**
     * @see iQuery::getDebugInfo()
     */

    public function getDebugInfo()
    {
        return " realm_name: {$this->getRealmName()} \n"
                ." aggregation_unit: {$this->_aggregation_unit} \n"
                ." data_table: {$this->_data_table} \n"
                ." date_table: {$this->_date_table} \n"
                ." group_by: {$this->_group_by} \n"
                ." main_stat_field: { $this->_main_stat_field } \n";
    }
}
