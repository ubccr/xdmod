<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Storage\GroupByAggregationUnit;

/**
 * GroupBy used for viewing aggregate storage data by month.
 */
class GroupByMonth extends GroupByAggregationUnit
{
    public function __construct()
    {
        parent::__construct(
            'month',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    DATE(gt.month_start) AS long_name,
                    DATE(gt.month_start) AS short_name,
                    gt.month_start_ts AS start_ts
                FROM months gt
                WHERE 1
                ORDER BY gt.id ASC
            '
        );
        $this->setAvailableOnDrilldown(false);
    }

    public static function getLabel()
    {
        return 'Month';
    }

    public function applyTo(
        Query &$query,
        Table $dataTable,
        $multiGroup = false
    ) {
        $idField = new TableField(
            $query->getDataTable(),
            'month_id',
            $this->getIdColumnName($multiGroup)
        );
        $nameField = new FormulaField(
            'DATE(' . $query->getDateTable()->getAlias() . '.month_start)',
            $this->getLongNameColumnName($multiGroup)
        );
        $shortnameField = new FormulaField(
            'DATE(' . $query->getDateTable()->getAlias() . '.month_start)',
            $this->getShortNameColumnName($multiGroup)
        );
        $valueField = new TableField(
            $query->getDateTable(),
            'month_start_ts'
        );
        $query->addField($idField);
        $query->addField($nameField);
        $query->addField($shortnameField);
        $query->addField($valueField);
        $query->addGroup($idField);
        $this->addOrder($query, $multiGroup);
    }
}
