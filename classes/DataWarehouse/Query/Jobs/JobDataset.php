<?php
namespace DataWarehouse\Query\Jobs;

use DataWarehouse\Data\RawStatisticsConfiguration;
use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use Exception;

/**
  * @see DataWarehouse::Query::RawQuery
  */
class JobDataset extends \DataWarehouse\Query\RawQuery
{
    private $documentation = array();

    public function __construct(
        array $parameters,
        $stat = "all"
    ) {
        parent::__construct('Jobs', 'modw_aggregates', 'jobfact_by_day', array());

        $config = RawStatisticsConfiguration::factory();

        // The data table is always aliased to "jf".
        $tables = ['jf' => $this->getDataTable()];

        foreach ($config->getQueryTableDefinitions('Jobs') as $tableDef) {
            $alias = $tableDef['alias'];
            $table = new Table(
                new Schema($tableDef['schema']),
                $tableDef['name'],
                $alias
            );
            $tables[$alias] = $table;
            $this->addTable($table);

            $join = $tableDef['join'];
            $this->addWhereCondition(new WhereCondition(
                new TableField($table, $join['primaryKey']),
                '=',
                new TableField($tables[$join['foreignTableAlias']], $join['foreignKey'])
            ));
        }

        // This table is defined in the configuration file, but used in the section below.
        $factTable = $tables['jt'];

        if (isset($parameters['primary_key'])) {
            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'job_id'), "=", $parameters['primary_key']));
        } elseif (isset($parameters['job_identifier'])) {
            $matches = array();
            if (preg_match('/^(\d+)(?:[\[_](\d+)\]?)?$/', $parameters['job_identifier'], $matches)) {
                $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'resource_id'), '=', $parameters['resource_id']));
                if (isset($matches[2])) {
                    $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'local_jobid'), '=', $matches[1]));
                    $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'local_job_array_index'), '=', $matches[2]));
                } else {
                    $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'local_job_id_raw'), '=', $matches[1]));
                }
            } else {
                throw new Exception('invalid "job_identifier" query parameter');
            }
        } elseif (isset($parameters['start_date']) && isset($parameters['end_date'])) {
            date_default_timezone_set('UTC');
            $startDate = date_parse_from_format('Y-m-d', $parameters['start_date']);
            $startDateTs = mktime(
                0,
                0,
                0,
                $startDate['month'],
                $startDate['day'],
                $startDate['year']
            );
            if ($startDateTs === false) {
                throw new Exception('invalid "start_date" query parameter');
            }

            $endDate = date_parse_from_format('Y-m-d', $parameters['end_date']);
            $endDateTs = mktime(
                23,
                59,
                59,
                $endDate['month'],
                $endDate['day'],
                $endDate['year']
            );
            if ($startDateTs === false) {
                throw new Exception('invalid "end_date" query parameter');
            }

            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'end_time_ts'), ">=", $startDateTs));
            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'end_time_ts'), "<=", $endDateTs));
        } else {
            throw new Exception('invalid query parameters');
        }

        if ($stat == "accounting" || $stat == 'batch') {
            foreach ($config->getQueryFieldDefinitions('Jobs') as $field) {
                $alias = $field['name'];
                if (isset($field['tableAlias']) && isset($field['column'])) {
                    $this->addField(new TableField(
                        $tables[$field['tableAlias']],
                        $field['column'],
                        $alias
                    ));
                } elseif (isset($field['formula'])) {
                    $this->addField(new FormulaField($field['formula'], $alias));
                } else {
                    throw new Exception(sprintf(
                        'Missing tableAlias and column or formula for "%s", definition: %s',
                        $alias,
                        json_encode($field)
                    ));
                }
                $this->documentation[$alias] = $field;
            }
        } else {
            $this->addField(new TableField($factTable, "job_id", "jobid"));
            $this->addField(new TableField($factTable, "local_jobid", "local_job_id"));

            $rt = new Table(new Schema("modw"), "resourcefact", "rf");
            $this->joinTo($rt, "task_resource_id", "code", "resource");

            $pt = new Table(new Schema('modw'), 'person', 'p');
            $this->joinTo($pt, "person_id", "long_name", "name");

            $st = new Table(new Schema('modw'), 'systemaccount', 'sa');
            $this->joinTo($st, "systemaccount_id", "username", "username");
        }
    }

    /**
     * helper function to join the data table to another table
     */
    private function joinTo($othertable, $joinkey, $otherkey, $colalias, $idcol = "id")
    {
        $this->addTable($othertable);
        $this->addWhereCondition(new WhereCondition(new TableField($this->getDataTable(), $joinkey), '=', new TableField($othertable, $idcol)));
        $this->addField(new TableField($othertable, $otherkey, $colalias));
    }

    public function getColumnDocumentation()
    {
        return $this->documentation;
    }

    /**
     * The query differs from the base class query because the same fact table
     * row may correspond to multiple rows in the aggregate table (e.g. a job
     * that runs over two days). Therefore the DISTINCT keyword is added to
     * deduplicate.
     *
     * @see \DataWarehouse\Query\Query::getQueryString()
     */
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
SELECT DISTINCT
  %s
FROM
  %s%s
WHERE
  %s
%s%s%s%s
SQL;

        $data_query = sprintf(
            $format,
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
}
