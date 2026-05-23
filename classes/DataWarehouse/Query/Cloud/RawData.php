<?php
namespace DataWarehouse\Query\Cloud;

use Psr\Log\LoggerInterface;
use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\FormulaField;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\Schema;
use CCR\DB;

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
        LoggerInterface $logger = null
    ) {
        $realmId = 'Cloud';
        $schema = 'modw_cloud';
        $dataTablePrefix = 'cloudfact_by_';

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
        $sessionlistTable = new Table($dataTable->getSchema(), $dataTable->getName() . "_sessionlist", "sl");

        $factTable = new Table(new Schema('modw_cloud'), "instance", "i");
        $sessionTable = new Table(new Schema('modw_cloud'), "session_records", "sr");

        $resourcefactTable = new Table(new Schema('modw'), 'resourcefact', 'rf');
        $this->addTable($resourcefactTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "host_resource_id"),
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

        $this->addField(new TableField($factTable, "provider_identifier", "provider_job_id"));
        $this->addField(new TableField($factTable, "instance_id", "jobid"));

        $this->addTable($factTable);
        $this->addTable($sessionlistTable);
        $this->addTable($sessionTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($sessionlistTable, "agg_id"),
            "=",
            new TableField($dataTable, "id")
        ));
        $this->addWhereCondition(new WhereCondition(
            new TableField($sessionlistTable, "session_id"),
            "=",
            new TableField($sessionTable, "session_id")
        ));

        $this->addWhereCondition(new WhereCondition(
            new TableField($factTable, "instance_id"),
            "=",
            new TableField($sessionTable, "instance_id")
        ));

        $this->prependOrder(
            new \DataWarehouse\Query\Model\OrderBy(
                new TableField($factTable, 'provider_identifier'),
                'DESC',
                'end_time_ts'
            )
        );
    }

    public function getQueryType(){
        return 'timeseries';
    }
}
