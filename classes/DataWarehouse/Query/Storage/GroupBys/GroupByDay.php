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
 * GroupBy used for viewing aggregate storage data by day.
 */
class GroupByDay extends GroupByAggregationUnit
{
    public function __construct()
    {
        parent::__construct(
            'day',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    DATE(gt.day_start) AS long_name,
                    DATE(gt.day_start) AS short_name,
                    gt.day_start_ts AS start_ts
                FROM days gt
                WHERE 1
                ORDER BY gt.id ASC
            '
        );
        $this->setAvailableOnDrilldown(false);
    }

    public static function getLabel()
    {
        return 'Day';
    }

    public function applyTo(
        Query &$query,
        Table $dataTable,
        $multiGroup = false
    ) {
        $idField = new TableField(
            $query->getDataTable(),
            'day_id',
            $this->getIdColumnName($multiGroup)
        );
        $nameField = new FormulaField(
            'DATE(' . $query->getDateTable()->getAlias() . '.day_start)',
            $this->getLongNameColumnName($multiGroup)
        );
        $shortnameField = new FormulaField(
            'DATE(' . $query->getDateTable()->getAlias() . '.day_start)',
            $this->getShortNameColumnName($multiGroup)
        );
        $valueField = new TableField(
            $query->getDateTable(),
            'day_start_ts'
        );
        $query->addField($idField);
        $query->addField($nameField);
        $query->addField($shortnameField);
        $query->addField($valueField);
        $query->addGroup($idField);
        $this->addOrder($query, $multiGroup);
    }
}
