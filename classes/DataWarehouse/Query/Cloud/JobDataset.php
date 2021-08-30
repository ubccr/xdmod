<?php
namespace DataWarehouse\Query\Cloud;

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
        parent::__construct('Cloud', 'modw_cloud', 'cloudfact_by_day', array());

        $this->setDistinct(true);
        $config = RawStatisticsConfiguration::factory();

        // The data table is always aliased to "agg".
        $tables = ['agg' => $this->getDataTable()];

        foreach ($config->getQueryTableDefinitions('Cloud') as $tableDef) {
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
        $factTable = $tables['i'];
        $sessionTable = $tables['sr'];

        if (isset($parameters['primary_key'])) {
            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'instance_id'), "=", $parameters['primary_key']));
        } elseif (isset($parameters['job_identifier'])) {
            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'resource_id'), '=', $parameters['resource_id']));
            $this->addPdoWhereCondition(new WhereCondition(new TableField($factTable, 'provider_identifier'), '=', $parameters['job_identifier']));
        } elseif (isset($parameters['start_date']) && isset($parameters['end_date'])) {
            date_default_timezone_set('UTC');
            $startDate = date_parse_from_format('Y-m-d', $parameters['start_date']);
            $startDateTs = mktime(0, 0, 0, $startDate['month'], $startDate['day'], $startDate['year']);

            if ($startDateTs === false) {
                throw new Exception('invalid "start_date" query parameter');
            }

            $endDate = date_parse_from_format('Y-m-d', $parameters['end_date']);
            $endDateTs = mktime(23, 59, 59, $endDate['month'], $endDate['day'], $endDate['year']);

            if ($startDateTs === false) {
                throw new Exception('invalid "end_date" query parameter');
            }

            $this->addPdoWhereCondition(new WhereCondition(new TableField($sessionTable, 'end_time_ts'), ">=", $startDateTs));
            $this->addPdoWhereCondition(new WhereCondition(new TableField($sessionTable, 'start_time_ts'), "<=", $endDateTs));
        } else {
            throw new Exception('invalid query parameters');
        }

        if ($stat == "accounting" || $stat == 'batch') {
            foreach ($config->getQueryFieldDefinitions('Cloud') as $field) {
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
        }
        elseif ($stat == 'timeseries') {
            $this->setDistinct(false);

            $this->addField(new TableField($tables['agg'], 'start_time'));
            $this->addField(new TableField($tables['agg'], 'end_time'));
            $this->addField(new TableField($tables['agg'], 'start_event_type_id'));
            $this->addField(new TableField($tables['agg'], 'end_event_type_id'));

            $pt = new Table(new Schema('modw'), 'person', 'p');
            $this->joinTo($pt, "person_id", "long_name", "name");

            $st = new Table(new Schema('modw'), 'systemaccount', 'sa');
            $this->joinTo($st, "systemaccount_id", "username", "username");

        } else {
            $this->addField(new TableField($factTable, "provider_identifier", "local_job_id"));
            $this->addField(new TableField($factTable, "instance_id", "jobid"));

            $rt = new Table(new Schema("modw"), "resourcefact", "rf");
            $this->joinTo($rt, "host_resource_id", "code", "resource");

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
}
