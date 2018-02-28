<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by day to a query
*
*/

class GroupByMonth extends \DataWarehouse\Query\Cloud\GroupBy
{
	public static function getLabel()
	{
		return 'Month';
	}


	public function __construct()
	{
		parent::__construct('month',
							array(),
							"select distinct gt.id,
												 date_format(gt.month_start, '%Y-%m') as long_name,
												 date_format(gt.month_start, '%Y-%m') as short_name,
												 gt.month_start_ts as start_ts
												 from  modw.months gt
												 where 1
												 order by gt.id asc",
							array()
							);
		$this->setAvailableOnDrilldown(false);
	}

	public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $field_name = NULL)
	{

		$modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
		$modw_aggregates_schema = new \DataWarehouse\Query\Model\Schema('modw_cloud');

		$id_field = new \DataWarehouse\Query\Model\TableField($query->getDataTable(), "month_id", $this->getIdColumnName($multi_group));
		$name_field = new \DataWarehouse\Query\Model\FormulaField('date_format('.$query->getDateTable()->getAlias().".month_start, '%Y-%m')", $this->getLongNameColumnName($multi_group));
		$shortname_field = new \DataWarehouse\Query\Model\FormulaField('date_format('.$query->getDateTable()->getAlias().".month_start, '%Y-%m')", $this->getShortNameColumnName($multi_group));
		$value_field = new \DataWarehouse\Query\Model\TableField($query->getDateTable(), "month_start_ts");
		$query->addField($id_field);
		$query->addField($name_field);
		$query->addField($shortname_field);
		$query->addField($value_field);

		$query->addGroup($id_field);

		$this->addOrder($query,$multi_group);
	}
	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($query->getDataTable(), "month_id"), $dir, $this->getName());
		if($prepend === true)
		{
			$query->prependOrder($orderField);
		}else
		{
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
?>
