<?php
/**
 * @package OpenXdmod\Cloud
 * @author Rudra Chakraborty
 */

namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Cloud\GroupBy;

/**
 * GroupBy used for viewing aggregate cloud data by day.
 */
class GroupByDay extends GroupBy
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
                FROM modw.days gt
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
            'date(' . $query->getDateTable()->getAlias() . '.day_start)',
            $this->getLongNameColumnName($multiGroup)
        );
        $shortnameField = new FormulaField(
            'date(' . $query->getDateTable()->getAlias() . '.day_start)',
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
