<?php
namespace DataWarehouse\Query;

use Configuration\XdmodConfiguration;
use Exception;

use CCR\DB;
use CCR\DB\PDODB;
use FilterListHelper;
use Models\Services\Parameters;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* Base class for defining a query.
*
*/

class Query
{
    public $roleParameterDescriptions;
    public $filterParameterDescriptions;
    private $pdoparams;
    private $pdoindex;
    private $log;

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

    private static $config;

    private $leftJoins = array();

    public function __construct($realm_name,
                                $datatable_schema,
                                $datatable_name,
                                array $control_stats,
                                $aggregation_unit_name,
                                $start_date,
                                $end_date,
                                $group_by,
                                $stat = 'job_count',
                                array $parameters = array(),
                                $query_groupname = 'query_groupname',
                                array $parameterDescriptions = array(),
                                $single_stat = false)
    {

        static::registerStatistics();
        static::registerGroupBys();

        $this->pdoparams = array();
        $this->pdoindex = 0;

        $this->setRealmName($realm_name);
        $this->setQueryGroupname($query_groupname);

        $this->_aggregation_unit = \DataWarehouse\Query\TimeAggregationUnit::factory($aggregation_unit_name,$start_date, $end_date, "{$datatable_schema}.{$datatable_name}_by_");
        $this->setDataTable($datatable_schema, "{$datatable_name}_by_{$this->_aggregation_unit}");

        $this->setDuration($start_date, $end_date, $aggregation_unit_name);

        if($group_by != null) $this->setGroupBy($group_by);
        $this->setParameters($parameters);

        if($stat != null) $this->setStat($stat, $single_stat);
        $this->parameterDescriptions = $parameterDescriptions;

        foreach($control_stats as $control_stat)
        {
            $this->addStatField(static::getStatistic($control_stat, $this));
        }

        $this->roleParameterDescriptions = array();
        $this->filterParameterDescriptions = array();
        $this->log = \CCR\Log::factory('xms.query', array(
            'console' => false,
            'db' => false,
            'mail' => false,
            'file' => LOG_DIR . '/query.log',
            'fileLogLevel' => PEAR_LOG_DEBUG
        ));
    }



    public $_db_profile = 'datawarehouse'; //The name of the db settings in portal_settings.ini

    /*
    * The query group name is used to group a set of queries together and define
    * groupings, stats and drilldowns for them independent of other queries.
    */
    private $_query_groupname = 'query_groupname';

    /*
    * The query realm name defines what part of the data warehouse the query belongs to.
    */
    private $_realm_name = 'query_realm';

    public function getQueryGroupname()
    {
        return $this->_query_groupname;
    }
    public function setQueryGroupname($query_groupname)
    {
        $this->_query_groupname = $query_groupname;
    }

    public function getRealmName()
    {
        return $this->_realm_name;
    }
    public function setRealmName($realm_name)
    {
        $this->_realm_name = $realm_name;
    }



    public $_single_stat = false;

    protected $_data_table;
    protected $_date_table;

    protected $_group_by;

    public function groupBy()
    {
        return $this->_group_by;
    }

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

    protected $_main_stat_field;
    public function getMainStatisticField()
    {
        return $this->_main_stat_field;
    }

    public function getQueryType()
    {
        return 'aggregate';
    }

    private $_tables = array();
    private $_fields = array();
    protected $_stat_fields = array();
    private $_where_conditions = array();
    private $_stat_where_conditions = array();
    private $_groups = array();
    private $_orders = array();

    protected $_aggregation_unit;

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

    protected $_duration_formula;



    public function execute($limit = 10000000)
    {

        $query_string = $this->getQueryString($limit);

        $debug = PDODB::debugging();
        if ($debug == true) {
            $class = get_class($this);
            $this->log->debug(sprintf("%s: \n%s", $class, $query_string));
        }

        $time_start = microtime(true);
        $results = DB::factory($this->_db_profile)->query($query_string, $this->pdoparams);

        $time_end = microtime(true);
        $return = array();
        if($this->_main_stat_field != null)
        {
            $stat = $this->_main_stat_field->getAlias()->getName();
            $stat_weight = $this->_main_stat_field->getWeightStatName();

            $sort_option = $this->_group_by->getOrderByStatOption();

            if(isset($sort_option))
            {
                $sort_option = $this->_main_stat_field->getOrderByStatOption();
            }
            if(isset($sort_option))
            {
                $stat_column = array();
                $name_column = array();
                foreach ($results as $key => $row)
                {
                    $stat_column[$key]  = $row[$stat];
                    $name_column[$key]  = $row['name'];
                }

                // Sort the results with stat_column descending
                array_multisort($stat_column, $sort_option, $name_column, SORT_ASC, $results);
            }
            $sem_name = 'sem_'.$stat;
            if(count($results) > 0 )
            {
                $return[$stat] = array();
                $return['weight'] = array();
            }
            $index = 0;
            foreach($results as $result)
            {
                if(!isset($result['id']))
                {
                    $result['id'] = -1;
                }
                if(!isset($result['name']))
                {
                    $result['name'] = 'NA';
                    $result['short_name'] = 'NA';
                }
                if($index < $limit)
                {
                    $return['id'][] = $result['id'];
                    $return['name'][] =$result['name'];
                    $return['short_name'][] =$result['short_name'];
                    $return[$stat][] = $result[$stat];
                    if(isset($result[$sem_name])) $return[$sem_name][] = $result[$sem_name];
                    else $return[$sem_name][] = 0;
                    $return['weight'][] = $result[$stat_weight];
                }else
                {
                    if($index == $limit)
                    {
                        $return['id'][] = -1;
                        $return['name'][] = 'Other';
                        $return['short_name'][] = 'Other';
                        $return[$stat][] = $result[$stat];
                        if(isset($result[$sem_name])) $return[$sem_name][] = $result[$sem_name];
                        else $return[$sem_name][] = 0;
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
        }
        else
        {
            $stat_fields = $this->getStatFields();
            $fields = $this->getFields();

            foreach($results as $result)
            {

                //$return['id'][] = $result['id'];
                //$return['name'][] = $result['name'];
                //$return['short_name'][] = $result['short_name'];

                foreach($fields as $field_key => $field)
                {
                    $return[$field_key][] = $result[$field_key];
                }
                foreach($stat_fields as $stat_key => $stat_field)
                {
                    $return[$stat_key][] = $result[$stat_key];
                }
            }
        }

        $return['query_string'] = $query_string;
        $return['query_time'] = $time_end - $time_start;
        $return['count'] = count($results);
        return $return;
    }

    public function getDataset($limit = 10000000)
    {
        $dataset = new \DataWarehouse\Data\AggregateDataset( $this->getQueryGroupname(), $this->getRealmName());
        $dataset->setStartDate($this->getStartDate());
        $dataset->setEndDate($this->getEndDate());

        $raw_data = $this->execute($limit);

        $this->query_string = $raw_data['query_string'];
        unset($raw_data['query_string']);
        $this->query_time = $raw_data['query_time'];
        unset($raw_data['query_time']);
        $this->_count = $raw_data['count'];
        unset($raw_data['count']);

        if(count($raw_data) <= 0) return $dataset;

        if($this->_main_stat_field != null)
        {
            $stat_key = $this->_main_stat_field->getAlias()->getName();
            $sem_key = 'sem_'.$stat_key;
            $series = new \DataWarehouse\Data\AggregateData($this->_main_stat_field->getLabel(),
                                                        $this->_main_stat_field->getLabel(),
                                                        $this->_main_stat_field,
                                                        $this->_group_by,
                                                        $this->_aggregation_unit,
                                                        is_array($raw_data['id'])?$raw_data['id']:array($raw_data['id']),
                                                        is_array($raw_data['name'])?$raw_data['name']:array($raw_data['name']),
                                                        is_array($raw_data['short_name'])?$raw_data['short_name']:array($raw_data['short_name']),
                                                        is_array($raw_data[$stat_key])?$raw_data[$stat_key]:array($raw_data[$stat_key]),
                                                        is_array($raw_data['weight'])?$raw_data['weight']:array($raw_data['weight']),
                                                        NULL,
                                                        is_array($raw_data[$sem_key])?$raw_data[$sem_key]:array($raw_data[$sem_key]),
                                                        $this->query_string,
                                                        $this->query_time);
            $dataset->addSeries($series);

        }
        else
        {
            $stat_fields = $this->getStatFields();

            foreach($stat_fields as $stat_key => $stat_field)
            {
                $sem_key = 'sem_'.$stat_key;
                $series = new \DataWarehouse\Data\AggregateData($stat_field->getLabel(),
                                                            $stat_field->getLabel(),
                                                            $stat_field,
                                                            $this->_group_by,
                                                            $this->_aggregation_unit,
                                                            is_array($raw_data['id'])?$raw_data['id']:array($raw_data['id']),
                                                            is_array($raw_data['name'])?$raw_data['name']:array($raw_data['name']),
                                                            is_array($raw_data['short_name'])?$raw_data['short_name']:array($raw_data['short_name']),
                                                            is_array($raw_data[$stat_key])?$raw_data[$stat_key]:array($raw_data[$stat_key]),
                                                            is_array($raw_data['weight'])?$raw_data['weight']:array($raw_data['weight']),
                                                            NULL,
                                                            is_array($raw_data[$sem_key])?$raw_data[$sem_key]:array($raw_data[$sem_key]),
                                                            $this->query_string,
                                                            $this->query_time);
                $dataset->addSeries($series);

            }
        }

        return $dataset;
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
    public function addStatField(\DataWarehouse\Query\Statistic $field)
    {
        $this->_stat_fields[$field->getAlias()->getName()] = $field;

        $addnlwhere = $field->getAdditionalWhereCondition();
        if( $addnlwhere !== NULL )
        {
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
    public function isInSelectFields($field_name)
    {
        $selectFields = $this->getSelectFields();
        return isset($selectFields[$field_name]);
    }
    public function getSelectFields()
    {
        $fields = $this->getFields();
        $stat_fields = $this->getStatFields();

        $select_fields = array();
        foreach($fields as $field_key => $field)
        {
            $select_fields[$field_key] = $field->getQualifiedName(true);
        }
        foreach($stat_fields as $field_key => $stat_field)
        {
            $select_fields[$field_key] = $stat_field->getQualifiedName(true);
        }
        return $select_fields;
    }

    public function getSelectTables()
    {
        $tables = $this->getTables();
        $select_tables = array();
        foreach($tables as $table)
        {
            $select_tables[] = $table->getQualifiedName(true, true);
        }
        return $select_tables;
    }

    public function getSelectOrderBy()
    {
        $orders = $this->getOrders();
        $select_order_by = array();
        foreach($orders as $order_key => $order)
        {
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

    public function executeRaw($limit = NULL, $offset = NULL)
    {
        $query_string = $this->getQueryString($limit,  $offset);
        $results = DB::factory($this->_db_profile)->query($query_string, $this->pdoparams);
        return $results;
    }
    public function getRawStatement($limit = NULL, $offset = NULL, $extraHavingClause = NULL)
    {
        $query_string = $this->getQueryString($limit,  $offset, $extraHavingClause);
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
            throw new Exception("Cannot get dimension values without specifying dimension.");
        }

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        $id_field = $select_fields['id'];
        $name_field = $select_fields['name'];
        $short_name_field = $select_fields['short_name'];

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

        $dimension_table = $select_tables[1];

        $restriction_wheres = array();
        $dimension_group_by = $this->groupBy();
        $dimension_group_by_name = $dimension_group_by->getName();
        $id_field_without_alias = preg_replace($as_clause_regex, '', $id_field);
        if ($this->restrictedByRoles) {
            foreach ($this->roleRestrictions as $restriction_dimension_name => $restriction_data) {
                $restriction_dimension_values_str = implode(', ', $restriction_data['dimensionValues']);

                $filter_table = FilterListHelper::getFullTableName($this, $dimension_group_by, $restriction_data['groupBy']);

                $restriction_wheres[] = "$id_field_without_alias IN (
                    SELECT
                        $filter_table.$dimension_group_by_name
                    FROM
                        $filter_table
                    WHERE
                        $filter_table.$restriction_dimension_name IN ($restriction_dimension_values_str)
                )";
            }
        } else {
            $filter_table = FilterListHelper::getFullTableName($this, $dimension_group_by);
            $restriction_wheres[] = "$id_field_without_alias IN (
                SELECT
                    $filter_table.$dimension_group_by_name
                FROM
                    $filter_table
            )";
        }

        $restriction_wheres_str = implode(' OR ', $restriction_wheres);

        $dimension_values_query = "
            SELECT
                $id_field,
                $name_field,
                $short_name_field,
                $orders_field
            FROM $dimension_table
            WHERE $restriction_wheres_str
            GROUP BY $groups_str
        ";

        if ($orders_exist) {
            $order_by_str = implode(', ', $this->getSelectOrderBy());
            $dimension_values_query .= " ORDER BY $order_by_str";
        }

        return $dimension_values_query;
    }

    public function getQueryString($limit = NULL, $offset = NULL, $extraHavingClause = NULL)
    {
        $wheres = $this->getWhereConditions();
        $groups = $this->getGroups();

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        $select_order_by = $this->getSelectOrderBy();

        $data_query = "select \n".implode(", \n",$select_fields)."\n
                      from \n".implode(",\n", $select_tables)."\n
                      ". $this->getLeftJoinSql()."\n
                        where \n".implode("\n and ", $wheres);

        if(count($groups) > 0)
        {
            $data_query .= " group by \n".implode(",\n",$groups);
        }
		if($extraHavingClause != NULL)
		{
			$data_query .= " having " . $extraHavingClause . "\n";
		}
        if(count($select_order_by) > 0)
        {
            $data_query .= " order by \n".implode(",\n",$select_order_by);
        }

        if($limit !== NULL && $offset !== NULL)
        {
            $data_query .= " limit $limit offset $offset";
        }
        return $data_query;
    }
    public function getCountQueryString()
    {
        $wheres = $this->getWhereConditions();
        $groups = $this->getGroups();

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        $select_order_by = $this->getSelectOrderBy();

        $data_query = "select count(*) as row_count from (select sum(1) as total
                      from \n".implode(",\n", $select_tables)."\n
                        where \n".implode("\n and ", $wheres);

        if(count($groups) > 0)
        {
            $data_query .= " group by \n".implode(",\n",$groups);
        }

        $data_query .= ") as a WHERE a.total IS NOT NULL";
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
        foreach($parameters as $parameter)
        {
            if($parameter instanceof \DataWarehouse\Query\Model\Parameter)
            {
                $paramName = $parameter->getName();

                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());
                if($parameter->getOperator() == "=") {
                    $rightField =  ":subst" . $this->pdoindex;
                    $this->pdoparams[$rightField] = $parameter->getValue();
                    $this->pdoindex += 1;
                }
                else {
                    // TODO - work out how to use PDO parameters for IN queries.
                    $rightField = $parameter->getValue();
                }

                $this->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
                                                    $leftField ,
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

    private function getLeftJoinSql()
    {
        $stmt = '';
        foreach($this->leftJoins as $joincond)
        {
            $stmt .= ' LEFT JOIN ' . $joincond[0]->getQualifiedName(true, true) .
                ' ON ' . $joincond[1];
        }
        return $stmt;
    }

    private function getParameters(array $parameters = array())
    {
        $whereConditions = array();

        foreach($parameters as $parameter)
        {
            if($parameter instanceof \DataWarehouse\Query\Model\Parameter)
            {
                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());

                $whereConditions[] = new \DataWarehouse\Query\Model\WhereCondition($leftField , $parameter->getOperator(), $parameter->getValue());
            }
        }

        return $whereConditions;
    }

    public function addParameters(array $parameters = array())
    {
        $this->parameters = array_merge($parameters, $this->parameters);

        foreach($parameters as $parameter)
        {
            if($parameter instanceof \DataWarehouse\Query\Model\Parameter)
            {
                $paramName = $parameter->getName();

                $leftField = new \DataWarehouse\Query\Model\TableField($this->_data_table, $parameter->getName());
                $rightField = $parameter->getValue();

                $this->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
                                                    $leftField ,
                                                    $parameter->getOperator(),
                                                    $rightField
                                                    )
                                );

            }

        }
    }
    protected function setDataTable($schemaname, $tablename, $join_index = '')
    {
        //AG - disabled count for performance.
        /*$data_table_count = DB::factory($this->_db_profile)->getRowCount($schemaname, $tablename);

        if($data_table_count <= 0)
        {
            throw new \Exception("The data table for $schemaname.$tablename is empty. ");
        }*/

        $this->_data_table = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema($schemaname), $tablename, 'jf', $join_index);
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

    public function getShortTitle()
    {
        return $this->_main_stat_field->getLabel();
    }
    public function getTitle($group_info_only = false)
    {
        $group_label = $this->groupBy()->getLabel();
        return $group_info_only?
                $group_label.' stats'.($this->groupBy()->getName()==='none'?
                        ' Summary':
                        ': by '.$this->groupBy()->getLabel()):
             /*'['.$group_label.'] '.*/$this->_main_stat_field->getLabel().($this->groupBy()->getName()==='none'?'':': by '.$this->groupBy()->getLabel());
    }

    public function getTitle2()
    {
        return implode(" -- ",array_unique($this->parameterDescriptions));
    }

    public function getFilterParametersTitle()
    {
        return implode("; ",array_unique($this->filterParameterDescriptions));
    }
    public function getRoleParametersTitle()
    {
        return implode(" -- ",array_unique($this->roleParameterDescriptions));
    }

    public function configureForChart(&$chartProperties, &$selectedDimensionIds, &$sortInfo)
    {
        $this->sortInfo = $sortInfo;

        $xAxis = isset($chartProperties['X Axis'])?$chartProperties['X Axis']:'';
        if($xAxis != '')
        {
            $x_axis_column_type = substr($xAxis,0,3);
            $x_axis_column_name = substr($xAxis,4);

             $this->addGroupBy($x_axis_column_name);

            foreach($selectedDimensionIds as $selectedDimensionId)
            {
                if($selectedDimensionId == $x_axis_column_name) continue;
                $f  = $this->addFilter($selectedDimensionId);
            }
        }else
        {
             $this->addGroupBy('none');
        }
        {
            $yAxisIndex = 1;
            while(($yAxis = isset($chartProperties["Y Axis $yAxisIndex"])?$chartProperties["Y Axis $yAxisIndex"]:'') != '')
            {
                $y_axis_column_type = substr($yAxis,0,3);
                $y_axis_column_name = substr($yAxis,4);
                $this->addStat($y_axis_column_name);
                $yAxisIndex++;
            }
        }

        if(count($sortInfo) > 0)
        {
            $this->clearOrders();
            foreach($sortInfo as $sort)
            {
                $this->addOrderBy($sort['column_name'], $sort['direction']);
            }
        }
    }
    public function configureForDatasheet(&$selectedDimensionIds, &$selectedMetricIds, &$sortInfo)
    {
        $this->sortInfo = $sortInfo;
        if(count($selectedDimensionIds) > 0)
        {
            foreach($selectedDimensionIds as $selectedDimensionId)
            {
                $this->addGroupBy($selectedDimensionId);
            }
        }else
        {
            $this->addGroupBy('none');
        }
        foreach($selectedMetricIds as $selectedMetricId)
        {
            $this->addStat($selectedMetricId);
        }


        if(count($sortInfo) > 0)
        {
            $this->clearOrders();
            foreach($sortInfo as $sort)
            {
                $this->addOrderBy($sort['column_name'], $sort['direction']);
            }
        }
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

    public function setParametersFromRequest($request, &$role_parameters)
    {
        static::registerGroupBys();
        $registeredGroupBys = static::getRegisteredGroupBys();

        //if(isset($this->_group_bys['person']) || isset($this->_group_bys['institution']) || isset($this->_group_bys['username']) || isset($this->_group_bys['allocation']) || isset($this->_group_bys['pi']) || isset($this->_group_bys['pi_institution']) || isset($this->_group_bys['nsfstatus']))
        {
            $request = array_merge($request,$role_parameters);
        }

        //if groupby is resource or queue and role params include provider

        $parameters = array();
        $parameterDescriptions = array();
        foreach($registeredGroupBys as $registeredGroupByName => $registeredGroupByClassname)
        {
            $group_by_instance = new $registeredGroupByClassname();
            $parameters = array_merge( $group_by_instance->pullQueryParameters($request), $parameters);
            $parameterDescriptions = array_merge( $group_by_instance->pullQueryParameterDescriptions($request), $parameterDescriptions);
        }

        sort($parameters);
        $this->setParameters($parameters);

        $this->parameterDescriptions = $parameterDescriptions;
    }
    public function setFilters( $user_filters )
    {
        //print_r($user_filters);
        $filters = array();
        if(!isset($user_filters->data) || !is_array($user_filters->data)) $user_filters->data = array();
        foreach($user_filters->data as $user_filter)
        {
            if(isset($user_filter->checked) && $user_filter->checked == 1)    $filters[$user_filter->id] = $user_filter;
        }

        //combine the filters and group them by dimension
        $groupedFilters = array();
        foreach($filters as $filter)
        {
            if(isset($filter->checked) && $filter->checked != 1 ) continue;

            if(!isset($groupedFilters[$filter->dimension_id])) $groupedFilters[$filter->dimension_id] = array();
            $groupedFilters[$filter->dimension_id][] = $filter->value_id;
        }

        $filterParameters = array();
        $filterParameterDescriptions = array();
        foreach($groupedFilters as $filter_parameter_dimension => $filterValues)
        {
            try{
                $group_by_instance = static::getGroupBy($filter_parameter_dimension);
                $param = array($filter_parameter_dimension.'_filter' => implode(',',$filterValues));
                $this->addParameters($group_by_instance->pullQueryParameters($param));
                $filterParameters[$filter_parameter_dimension] = array(
                    'groupBy' => $group_by_instance,
                    'dimensionValues' => $filterValues,
                );
                $filterParameterDescriptions = array_merge($filterParameterDescriptions, $group_by_instance->pullQueryParameterDescriptions($param));
            }catch(\Exception $ex)
            {
            }
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
    public function setMultipleRoleParameters($rolearray, $user) {

        $allwheres = array();
        $role_parameters = array();
        $wide_role_parameter_found = false;

        // Check whether multiple service providers are supported or not.
        try {
          $multiple_providers_supported = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
        }
        catch(Exception $e){
          $multiple_providers_supported = FALSE;
        }

        foreach($rolearray as $role) {

            $roleparams = Parameters::getParameters($user, $role);

            if(count($roleparams) == 0 ) {
                // Empty where condition translates to a "WHERE 1". There is no need to add the other
                // where conditions associated with the other roles since the different
                // role where conditions are ORed together.
                return array();
            }

            foreach($roleparams as $role_parameter_dimension => $role_parameter_value)
            {
                // If this is a service provider parameter and only one service
                // provider is supported, the user effectively has access to
                // all data. There is no need to add any where conditions.
                $is_provider_dimension = $role_parameter_dimension === 'provider';
                if ($is_provider_dimension && !$multiple_providers_supported) {
                    return array();
                }

                // Get the group by object associated with this dimension.
                // If it does not exist for this realm, skip this parameter.
                try {
                    $group_by_instance = static::getGroupBy($role_parameter_dimension);
                } catch (Exceptions\UnknownGroupByException $e) {
                    continue;
                }

                if( is_array($role_parameter_value) ) {
                    $param = array($role_parameter_dimension.'_filter' => implode(',',$role_parameter_value));
                    $role_parameter_values = $role_parameter_value;
                } else {
                    $param = array($role_parameter_dimension.'_filter' => $role_parameter_value);
                    $role_parameter_values = array($role_parameter_value);
                }

                $rolewheres = $this->getParameters($group_by_instance->pullQueryParameters($param));

                $allwheres[] = "(" . implode(" AND ", $rolewheres) . ")";

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

        if(count($allwheres) == 0 ) {
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
        foreach($role_parameters as $role_parameter_dimension => $role_parameter_value)
        {
            try{
                $group_by_instance = static::getGroupBy($role_parameter_dimension);

                if( is_array($role_parameter_value) ) {
                    $param = array($role_parameter_dimension.'_filter' => implode(',',$role_parameter_value));
                    $role_parameter_values = $role_parameter_value;
                } else {
                    $param = array($role_parameter_dimension.'_filter' => $role_parameter_value);
                    $role_parameter_values = array($role_parameter_value);
                }

                $this->addParameters($group_by_instance->pullQueryParameters($param));

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

                $roleParameterDescriptions = array_merge($roleParameterDescriptions, $group_by_instance->pullQueryParameterDescriptions($param));
            }catch(\Exception $ex)
            {
            }
        }

        $this->roleParameters = $groupedRoleParameters;
        $this->roleParameterDescriptions = $roleParameterDescriptions;
    }
    protected function setGroupBy($group_by)
    {
        $this->_group_by = static::getGroupBy($group_by);

        $this->_group_by->applyTo($this,$this->_data_table);
    }
    public function addGroupBy($group_by_name)
    {
        try
        {
            $group_by = static::getGroupBy($group_by_name);
        }
        catch (Exceptions\UnavailableTimeAggregationUnitException $time_unit_exception)
        {
            $time_unit_exception->errorData['realm'] = $this->getRealmName();
            throw $time_unit_exception;
        }

        $this->_group_bys[$group_by_name] = $group_by;
        $group_by->applyTo($this,$this->_data_table, true);
        return $group_by;
    }

    public function addWhereAndJoin($where_col_name, $operation, $whereConstraint)
    {
        try
        {
            $group_by = static::getGroupBy($where_col_name);
        }
        catch (Exceptions\UnavailableTimeAggregationUnitException $time_unit_exception)
        {
            $time_unit_exception->errorData['realm'] = $this->getRealmName();
            throw $time_unit_exception;
        }

        // Use the group by instance specific to the situation to
        // construct where clause and add it to the current query object
        $whereObj = $group_by->addWhereJoin($this,$this->_data_table, true, $operation, $whereConstraint);
        return $whereObj;

    } // public function addWhereAndJoin($group_by_name)

    public function addFilter($group_by_name)
    {
        $group_by = static::getGroupBy($group_by_name);

        return $group_by->filterByGroup($this,$this->_data_table);
    }
    public function addStat($stat_name)
    {
        if($stat_name == '') return NULL;
        $statistic = static::getStatistic($stat_name, $this);
        $this->_stats[$stat_name] = $statistic;
        $this->addStatField($statistic);
        return $statistic;
    }

    public function addOrderBy($sort_group_or_stat_name, $sort_direction)
    {
        if(isset($this->_group_bys[$sort_group_or_stat_name]))
        {
            $this->_group_bys[$sort_group_or_stat_name]->addOrder($this,true,$sort_direction,false);
        }
        else if(isset($this->_stat_fields[$sort_group_or_stat_name]))
        {
            $this->prependOrder(new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\Field($sort_group_or_stat_name),$sort_direction, $sort_group_or_stat_name));
        }

    }

    /**
     * Add an order by and set the sort info using a data description object.
     *
     * Code originally found in ComplexDataset and HighChartTimeseries2.
     *
     * @param object $data_description A data description object.
     */
    public function addOrderByAndSetSortInfo($data_description) {
        switch ($data_description->sort_type)
        {
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
        } // switch ($data_description->sort_type)
    }

    public function setStat($stat, $single_stat = false)
    {
        $this->_single_stat = $single_stat;

        $stat_name_to_classname = static::getRegisteredStatistics();

        $permitted_statistics = $this->_group_by->getPermittedStatistics();


        if($stat == 'all')
        {
            $this->_main_stat_field = null;
            foreach($permitted_statistics as $stat_name)
            {
                $this->addStatField(static::getStatistic($stat_name, $this));
            }
        }
        else
        {
            if(!in_array($stat,$permitted_statistics))
            {
                throw new \Exception("$stat is not available for {$this->_group_by->getLabel()}");
            }

            $this->_main_stat_field = static::getStatistic($stat, $this);
            if($single_stat === true)
            {
                $this->addStatField($this->_main_stat_field);
            }else
            {
                foreach($permitted_statistics as $stat_name)
                {
                    $this->addStatField(static::getStatistic($stat_name, $this));
                }
            }
        }

    }

    protected function setDuration($start_date, $end_date)
    {
        $start_date_given = $start_date !== null;
        $end_date_given = $end_date !== null;

        if($start_date_given && strtotime($start_date) == false)
        {
            throw new \Exception("start_date must be a date");
        }
        if($end_date_given && strtotime($end_date) == false)
        {
            throw new \Exception("end_date must be a date");
        }

        $this->_start_date = $start_date_given ? $start_date : '0000-01-01';
        $this->_end_date = $end_date_given ? $end_date : '9999-12-31';

        $start_date_parsed = date_parse_from_format('Y-m-d', $this->_start_date);
        $end_date_parsed = date_parse_from_format('Y-m-d', $this->_end_date);

        $this->_start_date_ts = mktime($start_date_parsed['hour'],
                               $start_date_parsed['minute'],
                               $start_date_parsed['second'],
                               $start_date_parsed['month'],
                               $start_date_parsed['day'],
                               $start_date_parsed['year']);
        $this->_end_date_ts = mktime(23,
                               59,
                               59,
                               $end_date_parsed['month'],
                               $end_date_parsed['day'],
                               $end_date_parsed['year']);
        list($this->_min_date_id, $this->_max_date_id) = $this->_aggregation_unit->getDateRangeIds($this->_start_date, $this->_end_date);

        if (!$start_date_given && !$end_date_given) {
            return;
        }

        $this->_date_table = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), $this->_aggregation_unit.'s', 'd');

        $this->addTable($this->_date_table);

        $date_id_field = new \DataWarehouse\Query\Model\TableField($this->_date_table,'id');
		$data_table_date_id_field = new \DataWarehouse\Query\Model\TableField($this->_data_table,"{$this->_aggregation_unit}_id");

        $this->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($date_id_field,
                                                    '=',
                                                    $data_table_date_id_field
                                                    )
                                );
        $this->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($data_table_date_id_field,
                                                    'between',
                                                    new \DataWarehouse\Query\Model\Field("{$this->_min_date_id} and {$this->_max_date_id}")
                                                    )
                                );
        /*$this->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($data_table_date_id_field,
                                                    '<=',
                                                    new \DataWarehouse\Query\Model\Field("{$this->_max_date_id}")
                                                    )
                                );*/


        $duration_query = " select sum(dd.hours) as duration from modw.{$this->_aggregation_unit}s dd where  dd.id between {$this->_min_date_id} and {$this->_max_date_id} ";

        $duration_result = DB::factory($this->_db_profile)->query($duration_query);

        $this->setDurationFormula(new \DataWarehouse\Query\Model\Field("(".($duration_result[0]['duration'] == ''?1:$duration_result[0]['duration']).")"));
    }

    public function getDataSource()
    {
        $realm = static::getRealm();

        try {
            return self::getConfig($realm, 'datasource');
        } catch (Exception $e) {
            return 'Unk';
        }
    }

    //////////////Static Members////////////////////////

	private static $_group_by_name_to_instance = array();

	public static function &get_group_by_name_to_instance()
    {
        $realm = static::getRealm();

        self::initData($realm);

        return self::$_group_by_name_to_instance[$realm];
    }

    private static $_group_by_name_to_class_name = array();

    public static function &get_group_by_name_to_class_name()
    {
        $realm = static::getRealm();

        self::initData($realm);

        return self::$_group_by_name_to_class_name[$realm];
    }
	//private static $_statistic_by_instance_cache = array();
    private static $_statistic_name_to_class_name = array();

    public static function &get_statistic_name_to_class_name()
    {
        $realm = static::getRealm();

        self::initData($realm);

        return self::$_statistic_name_to_class_name[$realm];
    }

    /**
     * This function checks to see if the stats have been initialized for the provided $realm and if
     * not, it calls `registerStatistics`. It also checks if the group bys have been initialized and
     * if not calls`registerGroupBys`.
     *
     * Note: This is required because of the public static functions above. Since they are
     * `public static` they can be called without having to instantiate Query ( or calling
     * `Query::getGroupBy` ). So this function provides an easy method of ensuring that the
     * statistics / groupBys data structures are populated before use.
     *
     * @param string $realm
     */
    private static function initData($realm)
    {
        if (!isset(self::$_stats_initialized[$realm])) {
            self::registerStatistics();

        }

        if (!isset(self::$_group_bys_initialized[$realm])) {
            self::registerGroupBys();
        }
    }

    /*
    *
    * @param $group_by_name for example 'resource', 'person', ...
    * @returns a subclass of GroupBy based on $group_by_name requested.
    * @throws Exception if $group_by_name is not registered
    *
    * GroupBy subclasses must be registed using @registerGroup first
    *
    */
    public static function getGroupBy($group_by_name)
    {
		 $group_by_name_to_instance = &static::get_group_by_name_to_instance();

		if(!isset($group_by_name_to_instance[$group_by_name]))
		{
			static::registerStatistics();
			static::registerGroupBys();
			$classname = static::getGroupByClassname($group_by_name);
			$group_by_name_to_instance[$group_by_name] = new $classname;

            $realm = static::getRealm();
            $config = static::getConfigData();
            $found = array_pop(
                array_filter(
                    $config['realms'][$realm]['group_bys'],
                    function ($value) use ($group_by_name) {
                        return $value['name']=== $group_by_name;
                    }
                )
            );
            if (array_key_exists('visible', $found)) {
                $group_by_name_to_instance[$group_by_name]->setAvailableOnDrilldown($found['visible']);
            }

        }
        return $group_by_name_to_instance[$group_by_name];
    } //getGroupBy

    public static function getGroupByClassname($group_by_name)
    {
        $group_by_name_to_class_name = &static::get_group_by_name_to_class_name();
        if(isset($group_by_name_to_class_name[$group_by_name]))
        {
            return $group_by_name_to_class_name[$group_by_name];
        }
        else
        {
            if (TimeAggregationUnit::isTimeAggregationUnitName($group_by_name)) {
                $time_exception = new Exceptions\UnavailableTimeAggregationUnitException("Query: Unavailable Time Aggregation Unit \"$group_by_name\" Specified");
                $time_exception->errorData['unit'] = $group_by_name;
                throw $time_exception;
            }

            throw new Exceptions\UnknownGroupByException("Query: Unknown Group By \"$group_by_name\" Specified");
        }
    } //getGroupByClassname


    /*
    * This function returns a copy of the array that maps the group by names to class names
    */
    public static function getRegisteredGroupBys()
    {
         $group_by_name_to_class_name = &static::get_group_by_name_to_class_name();
        return  $group_by_name_to_class_name;
    }//getRegisteredGroupBys

    /*
    *
     * @param $statistic_name for example 'job_count', ...
    * @returns a subclass of Statistic based on $statistic_name requested.
    * @throws Exception if $statistic_name is not registered
    *
    * Statistic subclasses must be registed using @registerGroup first
    *
    */
    public static function getStatistic($statistic_name, $query_instance = NULL)
    {
        $statistic_name_to_class_name = &static::get_statistic_name_to_class_name();
        if(isset($statistic_name_to_class_name[$statistic_name]))
        {
            $class_name = $statistic_name_to_class_name[$statistic_name];
            return new $class_name($query_instance != NULL ? $query_instance : new static('day', '2001-01-01','2001-01-02','none'));
        }
        else
        {
            throw new \Exception("Query: Statistic {$statistic_name} is unknown.");
        }
    } //getStatistic

    /*
    * This function returns a copy of the array that maps the statistic names to class names
    */
    public static function getRegisteredStatistics()
    {
        $statistic_name_to_class_name = &static::get_statistic_name_to_class_name();
        return $statistic_name_to_class_name;
    }//getRegisteredStatistics

    private static $_group_bys_initialized = array();

    /**
     * Register group bys for the current realm.
     */
    public static function registerGroupBys()
    {
        $realm = static::getRealm();

        if (!isset(self::$_group_bys_initialized[$realm])) {

            $group_bys = self::getConfig($realm, 'group_bys');

            $classPrefix = "\\DataWarehouse\\Query\\$realm\\GroupBys\\";

            $group_by_name_to_class_name = array();

            foreach ($group_bys as $group_by) {
                $group_by_name_to_class_name[$group_by['name']] = $classPrefix . $group_by['class'];
            }

            self::$_group_by_name_to_class_name[$realm] = $group_by_name_to_class_name;

            self::$_group_bys_initialized[$realm] = true;
        }
    }

    private static $_stats_initialized = array();

    /**
     * Register statistics for the current realm.
     */
    public static function registerStatistics()
    {
        $realm = static::getRealm();

        if (!isset(self::$_stats_initialized[$realm])) {

            $stats = self::getConfig($realm, 'statistics');

            $classPrefix = "\\DataWarehouse\\Query\\$realm\\Statistics\\";

            $statistic_name_to_class_name = array();

            foreach ($stats as $stat) {
                $statistic_name_to_class_name[$stat['name']] = $classPrefix . $stat['class'];
            }

            self::$_statistic_name_to_class_name[$realm] = $statistic_name_to_class_name;

            self::$_stats_initialized[$realm] = true;
        }
    }

    private static $_static_realm = array();

    protected static function getRealm()
    {
        $class = get_called_class();

        if (!isset(self::$_static_realm[$class])) {
            if (preg_match('/DataWarehouse\\\\Query\\\\(\\w+)\\\\/', $class, $matches)) {
                self::$_static_realm[$class] = $matches[1];
            } else {
                throw new \Exception("Failed to determine realm for class '$class'");
            }
        }

        return self::$_static_realm[$class];
    }

    protected static function getConfigData()
    {
        if (!isset(self::$config)) {
            self::$config = XdmodConfiguration::assocArrayFactory('datawarehouse.json', CONFIG_DIR);
        }

        return self::$config;
    }

    protected static function getConfig($realm, $section = null)
    {
        $configData = self::getConfigData();

        foreach ($configData['realms'] as $key => $realmData) {
            if ($key == $realm) {
                if ($section === null) {
                    return $realmData;
                } elseif (array_key_exists($section, $realmData)) {
                    return $realmData[$section];
                } else {
                    $msg = "Unknown section '$section' for realm '$realm'";
                    throw new \Exception($msg);
                }
            }
        }

        throw new \Exception("Unknown realm '$realm'");
    }

    public function __toString() {

        return " query_groupname: {$this->_query_groupname} \n"
                ." realm_name: {$this->_realm_name} \n"
                ." single_stat: {$this->_single_stat} \n"
                ." aggregation_unit: {$this->_aggregation_unit} \n"
                ." data_table: {$this->_data_table} \n"
                ." date_table: {$this->_date_table} \n"
                ." group_by: {$this->_group_by} \n"
                ." main_stat_field: { $this->_main_stat_field } \n";
                //." group_bys: ". print_r($this->_group_bys, true)."\n"
                //." stats: ". print_r($this->_stats, true)."\n"
                //." tables: ". print_r($this->_tables, true)."\n"
                //." fields: ". print_r($this->_fields, true)."\n"
                //." stat_fields: ". print_r($this->_stat_fields, true)."\n"
                //." where_conditions: ". print_r($this->_where_conditions, true)."\n"
                //." stat_where_conditions: ". print_r($this->_stat_where_conditions, true)."\n"
                //." groups: ". print_r($this->_groups, true)."\n"
                //." orders: ". print_r($this->_orders, true)."\n"
                //."pdoparams: ".print_r($this->pdoparams, true)." \n"
                //." pdoindex: {$this->pdoindex} \n" ;
                //." : ". ."\n"
                //." : {} \n"
    } // __toString()
}
?>
