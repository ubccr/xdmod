<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByQuarter extends \DataWarehouse\Query\Jobs\GroupBy
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
            'SELECT DISTINCT
                gt.id,
                CONCAT(YEAR(gt.quarter_start)," Q", CEIL(MONTH(gt.quarter_start)/3)) AS long_name,
                CONCAT(YEAR(gt.quarter_start)," Q", CEIL(MONTH(gt.quarter_start)/3)) AS short_name,
                gt.quarter_start_ts AS start_ts
            FROM  modw.quarters gt
            WHERE 1
            ORDER BY gt.id ASC',
            array()
        );
        $this->setAvailableOnDrilldown(false);
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $modw_aggregates_schema = new \DataWarehouse\Query\Model\Schema('modw_aggregates');

        $id_field = new \DataWarehouse\Query\Model\TableField($query->getDataTable(), 'quarter_id', $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\FormulaField("concat(year({$query->getDateTable()->getAlias()}.quarter_start),' Q', ceil(month({$query->getDateTable()->getAlias()}.quarter_start)/3))", $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\FormulaField("concat(year({$query->getDateTable()->getAlias()}.quarter_start),' Q', ceil(month({$query->getDateTable()->getAlias()}.quarter_start)/3))", $this->getShortNameColumnName($multi_group));
        $value_field = new \DataWarehouse\Query\Model\TableField($query->getDateTable(), 'quarter_start_ts');
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);
        $query->addField($value_field);

        $query->addGroup($id_field);

        $this->addOrder($query, $multi_group);
    }
    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($query->getDataTable(), 'quarter_id'), $dir, $this->getName());
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
