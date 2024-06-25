<?php
namespace DataWarehouse\Query\ResourceSpecifications;

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
        $realmId = 'ResourceSpecifications';
        $schema = 'modw_aggregates';
        $dataTablePrefix = 'resourcespecsfact_by_';

        parent::__construct(
            $realmId,
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupById,
            $statisticId,
            $parameters,
            $logger
        );

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

        $resourcespecsListTable = new Table($dataTable->getSchema(), $dataTable->getName() . "_resourcespecslist", "rsa");
        $factTable = new Table(new Schema('modw'), "resourcespecs", "rs");
        $resourcefactTable = new Table(new Schema('modw'), 'resourcefact', 'rf');
        $resourceTypeTable = new Table(new Schema('modw'), 'resourcetype', 'rt');
        $organizationTable = new Table(new Schema('modw'), 'organization', 'org');
        $percentAllocated = new Table(new Schema('modw'), 'resource_allocated', 'rs');
        $resourceAllocationType = new Table(new Schema('modw'), 'resource_allocation_type', 'rat');

        $this->addTable($resourcespecsListTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcespecsListTable, "agg_id"),
            "=",
            new TableField($dataTable, "id")
        ));

        $this->addTable($resourcefactTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "record_resource_id"),
            '=',
            new TableField($resourcefactTable, "id")
        ));

        $this->addTable($factTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcespecsListTable, "resourcespec_id"),
            "=",
            new TableField($factTable, "resourcespec_id")
        ));

        $this->addTable($resourceTypeTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcefactTable, "resourcetype_id"),
            '=',
            new TableField($resourceTypeTable, "id")
        ));

        $this->addTable($organizationTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcefactTable, "organization_id"),
            '=',
            new TableField($organizationTable, "id")
        ));

        $this->addTable($percentAllocated);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcefactTable, "id"),
            '=',
            new TableField($percentAllocated, "resource_id")
        ));

        $this->addTable($resourceAllocationType);
        $this->addWhereCondition(new WhereCondition(
            new TableField($resourcefactTable, "id"),
            '=',
            new TableField($resourceAllocationType, "resource_id")
        ));

        $this->addField(new TableField($resourcefactTable, "code", 'resource'));
        $this->addField(new TableField($resourceTypeTable, "description", "resource_type"));
        $this->addField(new TableField($factTable, "start_time_ts", "start_time_ts"));
        $this->addField(new TableField($factTable, "end_time_ts", "end_time_ts"));
        $this->addField(new TableField($factTable, "cpu_processor_count", "cpu_processor_count"));
        $this->addField(new TableField($factTable, "cpu_node_count", "cpu_node_count"));
        $this->addField(new TableField($factTable, "cpu_processor_count_per_node", "cpu_processor_count_per_node"));
        $this->addField(new TableField($factTable, "gpu_processor_count", "gpu_processor_count"));
        $this->addField(new TableField($factTable, "gpu_node_count", "gpu_node_count"));
        $this->addField(new TableField($factTable, "gpu_processor_count_per_node", "gpu_processor_count_per_node"));
        $this->addField(new TableField($organizationTable, "name", "organization_name"));
        $this->addField(new TableField($factTable, "su_available_per_day", "su_available"));
        $this->addField(new TableField($percentAllocated, "percent_allocated", "percent_allocated"));
        $this->addField(new TableField($resourceAllocationType, "resource_allocation_type_description", "resource_allocation_type_description"));

        $this->prependOrder(
            new \DataWarehouse\Query\Model\OrderBy(
                new TableField($factTable, 'resourcespec_id'),
                'DESC',
                'end_time_ts'
            )
        );
    }

    public function getQueryType(){
        return 'timeseries';
    }
}
