<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;

/**
 * Storage query GroupBy base class.
 */
abstract class GroupBy extends \DataWarehouse\Query\GroupBy
{

    /**
     * @var \DataWarehouse\Query\Model\Schema;
     */
    protected $schema;

    /**
     * @var \DataWarehouse\Query\Model\Table
     */
    protected $table;

    /**
     * @var string
     */
    protected $info;

    public function __construct(
        $name,
        array $additionalPermittedParameters = array(),
        $possibleValuesQuery = null
    ) {
        $permittedParamters = array_merge(
            array_keys(Aggregate::getRegisteredStatistics()),
            $additionalPermittedParameters
        );

        parent::__construct(
            $name,
            $permittedParamters,
            $possibleValuesQuery
        );

        $this->schema = new Schema('modw_storage');
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getDrillTargets($statisticName, $queryClassname)
    {
        $registerdGroupBys = Aggregate::getRegisteredGroupBys();
        $drillTargetGroupBys = array();

        foreach ($registerdGroupBys as $groupByName => $groupByClassname) {
            if ($groupByName === 'none' || $groupByName === $this->getName()) {
                continue;
            }

            $groupByClassname = $queryClassname::getGroupByClassname($groupByName);
            $groupByInstance = $queryClassname::getGroupBy($groupByName);
            $permittedStats = $groupByInstance->getPermittedStatistics();
            if ($groupByInstance->getAvailableOnDrilldown()
                && array_search($statisticName, $permittedStats) !== false
            ) {
                $drillTargetGroupBys[] = $groupByName . '-' . $groupByInstance->getLabel();
            }
        }

        sort($drillTargetGroupBys);

        return $drillTargetGroupBys;
    }

    /**
     * Order by "_order_id_field_name" column.
     */
    public function addOrder(
        Query &$query,
        $multiGroup = false,
        $dir = 'ASC',
        $prepend = false
    ) {
        $orderField = new OrderBy(
            new TableField(
                $this->table,
                $this->_order_id_field_name
            ),
            $dir,
            $this->getName()
        );

        if ($prepend) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function applyTo(
        Query &$query,
        Table $dataTable,
        $multiGroup = false
    ) {
        $query->addTable($this->table);

        $tableIdField = new TableField($this->table, $this->_id_field_name);
        $dataTableIdField = new TableField($dataTable, $this->_id_field_name);

        $query->addWhereCondition(
            new WhereCondition(
                $tableIdField,
                '=',
                $dataTableIdField
            )
        );

        $idField = new TableField(
            $this->table,
            $this->_id_field_name,
            $this->getIdColumnName($multiGroup)
        );

        $nameField = new TableField(
            $this->table,
            $this->_long_name_field_name,
            $this->getLongNameColumnName($multiGroup)
        );

        $shortnameField = new TableField(
            $this->table,
            $this->_short_name_field_name,
            $this->getShortNameColumnName($multiGroup)
        );

        $orderIdField = new TableField(
            $this->table,
            $this->_order_id_field_name,
            $this->getOrderIdColumnName($multiGroup)
        );

        $query->addField($orderIdField);
        $query->addField($idField);
        $query->addField($nameField);
        $query->addField($shortnameField);

        $query->addGroup($idField);

        $this->addOrder($query, $multiGroup);
    }

    public function addWhereJoin(
        Query &$query,
        Table $dataTable,
        $multiGroup,
        $operation,
        $whereConstraint
    ) {
        $query->addTable($this->table);

        $tableIdField = new TableField($this->table, $this->_id_field_name);
        $dataTableIdField = new TableField($dataTable, $this->_id_field_name);

        $query->addWhereCondition(
            new WhereCondition(
                $tableIdField,
                '=',
                $dataTableIdField
            )
        );

        if (is_array($whereConstraint)) {
            $whereConstraint = "('" . implode("','", $whereConstraint) . "')";
        }

        $query->addWhereCondition(
            new WhereCondition(
                $tableIdField,
                $operation,
                $whereConstraint
            )
        );
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            '_filter_',
            $this->_id_field_name
        );
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            sprintf(
                'SELECT %s AS field_label FROM %s WHERE %s IN (_filter_) ORDER BY %s',
                $this->_long_name_field_name,
                $this->table->getQualifiedName(),
                $this->_id_field_name,
                $this->_long_name_field_name
            )
        );
    }

    public function getDefaultDatasetType()
    {
        return 'timeseries';
    }
}
