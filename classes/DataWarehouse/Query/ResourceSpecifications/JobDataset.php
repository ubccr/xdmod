<?php
namespace DataWarehouse\Query\ResourceSpecifications;

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

    public function __construct(array $parameters)
    {
        parent::__construct('ResourceSpecifications', 'modw_aggregates', 'resourcespecsfact_by_day', array());

        $config = RawStatisticsConfiguration::factory();

        $dataTable = $this->getDataTable();

        // The data table is always aliased to "agg".
        $tables = ['agg' => $dataTable];

        foreach ($config->getQueryTableDefinitions('ResourceSpecifications') as $tableDef) {
            $alias = $tableDef['alias'];
            $table = new Table(new Schema($tableDef['schema']), $tableDef['name'], $alias);
            $tables[$alias] = $table;
            $this->addTable($table);

            $join = $tableDef['join'];
            $this->addWhereCondition(new WhereCondition(new TableField($table, $join['primaryKey']), '=', new TableField($tables[$join['foreignTableAlias']], $join['foreignKey'])));
        }

        $factTable = $tables['rs'];

        $this->setDistinct(true);

        if (isset($parameters['start_date']) && isset($parameters['end_date'])) {
            $startDate = date_parse_from_format('Y-m-d', $parameters['start_date']);
            $startDateTimestamp = mktime(0, 0, 0, $startDate['month'], $startDate['day'], $startDate['year']);

            $endDate = date_parse_from_format('Y-m-d', $parameters['end_date']);
            $endDateTimestamp = mktime(23, 59, 59, $endDate['month'], $endDate['day'], $endDate['year']);

            if ($startDateTimestamp === false) {
                throw new Exception('invalid "start_date" query parameter.');
            }

            if ($endDateTimestamp === false) {
                throw new Exception('invalid "end_date" query parameter.');
            }

            $startDayId = (date('Y', $startDateTimestamp) * 100000 + date('z', $startDateTimestamp) + 1);
            $endDayId = (date('Y', $endDateTimestamp) * 100000 + date('z', $endDateTimestamp) + 1);

            $factEndIdField = new TableField($factTable, 'end_day_id');
            $factStartIdField = new TableField($factTable, 'start_day_id');

            $this->addWhereCondition(new WhereCondition($factEndIdField, '>=', $startDayId));
            $this->addWhereCondition(new WhereCondition($factStartIdField, '<=', $endDayId));

        } else {
            throw new Exception('invalid query parameters');
        }

        foreach ($config->getQueryFieldDefinitions('ResourceSpecifications') as $field) {
            $alias = $field['name'];
            if (isset($field['tableAlias']) && isset($field['column'])) {
                $this->addField(new TableField($tables[$field['tableAlias']], $field['column'], $alias));
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

        $this->addOrder(new \DataWarehouse\Query\Model\OrderBy(
            new TableField($tables['rf'], 'id'),
            'ASC',
            'id'
        ));

        $this->addOrder(new \DataWarehouse\Query\Model\OrderBy(
            new TableField($tables['rs'], 'end_date_ts'),
            'ASC',
            'end_date'
        ));
    }

    public function getColumnDocumentation()
    {
        return $this->documentation;
    }
}
