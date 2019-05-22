<?php
namespace DataWarehouse\Query\Cloud\GroupBys;

class GroupByDay extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return  'Day';
    }


    public function __construct()
    {
        parent::__construct(
            'day',
            array(),
            'SELECT DISTINCT
                gt.id,
                DATE(gt.day_start) AS long_name,
                DATE(gt.day_start) AS short_name,
                gt.day_start_ts AS start_ts
            FROM
                modw.days gt
            WHERE 1
            ORDER BY
                gt.id ASC',
            array()
        );
        $this->setAvailableOnDrilldown(false);
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $modw_aggregates_schema = new \DataWarehouse\Query\Model\Schema('modw_cloud');

        $id_field = new \DataWarehouse\Query\Model\TableField($query->getDataTable(), 'day_id', $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\FormulaField('date(' . $query->getDateTable()->getAlias() . '.day_start)', $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\FormulaField('date(' . $query->getDateTable()->getAlias() . '.day_start)', $this->getShortNameColumnName($multi_group));
        $value_field = new \DataWarehouse\Query\Model\TableField($query->getDateTable(), 'day_start_ts');
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);
        $query->addField($value_field);

        $query->addGroup($id_field);

        $this->addOrder($query, $multi_group);
    }
    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($query->getDataTable(), 'day_id'), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        $parameters = array();

        return $parameters;
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        $parameters = array();

        return $parameters;
    }
}
