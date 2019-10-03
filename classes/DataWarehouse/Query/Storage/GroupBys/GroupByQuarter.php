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
 * GroupBy used for viewing aggregate storage data by quarter.
 */
class GroupByQuarter extends GroupByAggregationUnit
{
    public static function getLabel()
    {
        return 'Quarter';
    }

    public function __construct()
    {
        parent::__construct(
            'quarter',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    CONCAT(YEAR(gt.quarter_start)," Q", CEIL(MONTH(gt.quarter_start)/3)) AS long_name,
                    CONCAT(YEAR(gt.quarter_start)," Q", CEIL(MONTH(gt.quarter_start)/3)) AS short_name,
                    gt.quarter_start_ts AS start_ts
                FROM quarters gt
                WHERE 1
                ORDER BY gt.id ASC
            '
        );
        $this->setAvailableOnDrilldown(false);
    }

    public function applyTo(
        Query &$query,
        Table $dataTable,
        $multiGroup = false
    ) {
        $idField = new TableField(
            $query->getDataTable(),
            'quarter_id',
            $this->getIdColumnName($multiGroup)
        );
        $nameField = new FormulaField(
            'CONCAT(YEAR(' . $query->getDateTable()->getAlias() . '.quarter_start)," Q", CEIL(MONTH(' . $query->getDateTable()->getAlias() . '.quarter_start)/3))',
            $this->getLongNameColumnName($multiGroup)
        );
        $shortnameField = new FormulaField(
            'CONCAT(YEAR(' . $query->getDateTable()->getAlias() . '.quarter_start)," Q", CEIL(MONTH(' . $query->getDateTable()->getAlias() . '.quarter_start)/3))',
            $this->getShortNameColumnName($multiGroup)
        );
        $valueField = new TableField(
            $query->getDateTable(),
            'quarter_start_ts'
        );
        $query->addField($idField);
        $query->addField($nameField);
        $query->addField($shortnameField);
        $query->addField($valueField);
        $query->addGroup($idField);
        $this->addOrder($query, $multiGroup);
    }
}
