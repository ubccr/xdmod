<?php
namespace DataWarehouse\Query\Jobs;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\FormulaField;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\Schema;

/**
 * The RawData class is reponsible for generating a query that returns
 * the set of fact table rows given the where conditions on the aggregate
 * table.
 */
class RawData extends \DataWarehouse\Query\Query implements \DataWarehouse\Query\iQuery
{
    public function __construct(
        $realmId,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        $statisticId = null,
        array $parameters = array(),
        Log $logger = null
    ) {
        $realmId = 'Jobs';
        $schema = 'modw_aggregates';
        $dataTablePrefix = 'jobfact_by_';

        parent::__construct(
            $realmId,
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupById,
            null,
            $parameters
        );

        // The same fact table row may correspond to multiple rows in the
        // aggregate table (e.g. a job that runs over two days).
        $this->setDistinct(true);

        // Override values set in Query::__construct() to use the fact table rather than the
        // aggregation table prefix from the Realm configuration.

        $this->setDataTable($schema, sprintf("%s%s", $dataTablePrefix, $aggregationUnitName));
        $this->_aggregation_unit = \DataWarehouse\Query\TimeAggregationUnit::factory(
            $aggregationUnitName,
            $startDate,
            $endDate,
            sprintf("%s.%s", $schema, $dataTablePrefix)
        );

        $dataTable = $this->getDataTable();
        $joblistTable = new Table($dataTable->getSchema(), $dataTable->getName() . "_joblist", "jl");
        $factTable = new Table(new Schema('modw'), "job_tasks", "jt");

        $resourcefactTable = new Table(new Schema('modw'), 'resourcefact', 'rf');
        $this->addTable($resourcefactTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "task_resource_id"),
            '=',
            new TableField($resourcefactTable, "id")
        ));

        $personTable = new Table(new Schema('modw'), 'person', 'p');

        $this->addTable($personTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "person_id"),
            '=',
            new TableField($personTable, "id")
        ));

        $this->addField(new TableField($resourcefactTable, "code", 'resource'));
        $this->addField(new TableField($personTable, "long_name", "name"));

        $this->addField(new TableField($factTable, "job_id", "jobid"));
        $this->addField(new TableField($factTable, "local_jobid", "local_job_id"));
        $this->addField(new TableField($factTable, 'start_time_ts'));
        $this->addField(new TableField($factTable, 'end_time_ts'));
        $this->addField(new FormulaField('-1', 'cpu_user'));

        // This is used by Integrations and not currently shown on the XDMoD interface
        $this->addField(new TableField($factTable, 'name', 'job_name'));

        $this->addTable($joblistTable);
        $this->addTable($factTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($joblistTable, "agg_id"),
            "=",
            new TableField($dataTable, "id")
        ));
        $this->addWhereCondition(new WhereCondition(
            new TableField($joblistTable, "jobid"),
            "=",
            new TableField($factTable, "job_id")
        ));

        switch ($statisticId) {
            case "job_count":
                $this->addWhereCondition(new WhereCondition("jt.end_time_ts", "BETWEEN", "duration.day_start_ts AND duration.day_end_ts"));
                break;
            case "started_job_count":
                $this->addWhereCondition(new WhereCondition("jt.start_time_ts", "BETWEEN", "duration.day_start_ts AND duration.day_end_ts"));
                break;
            default:
                // All other metrics show running job count
                break;
        }

        $this->prependOrder(
            new \DataWarehouse\Query\Model\OrderBy(
                new TableField($factTable, 'end_time_ts'),
                'DESC',
                'end_time_ts'
            )
        );
    }

    public function getQueryType(){
        return 'timeseries';
    }
}
