<?php
/**
 * @package OpenXdmod\Storage
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
    public function __construct()
    {
        parent::__construct(
            'quarter',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    DATE(gt.quarter_start) AS long_name,
                    DATE(gt.quarter_start) AS short_name,
                    gt.quarter_start_ts AS start_ts
                FROM modw.quarters gt
                WHERE 1
                ORDER BY gt.id ASC
            ',
            array()
        );
        $this->setAvailableOnDrilldown(false);
    }

    public static function getLabel()
    {
        return 'Quarter';
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
            'date(' . $query->getDateTable()->getAlias() . '.quarter_start)',
            $this->getLongNameColumnName($multiGroup)
        );
        $shortnameField = new FormulaField(
            'date(' . $query->getDateTable()->getAlias() . '.quarter_start)',
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
