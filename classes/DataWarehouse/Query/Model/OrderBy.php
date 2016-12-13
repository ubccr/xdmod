<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for adding order bys to a query.
* 
*/
class OrderBy
{
	protected $_field;
	protected $_order;
	protected $_column_name;
	public function __construct(\DataWarehouse\Query\Model\Field $field, $order, $columnName)
	{
		$this->_field = $field;
		$this->_order = $order;
		$this->_column_name = $columnName;
	}
	
	public function getOrder()
	{
		return $this->_order;
	}
	public function getField()
	{
		return $this->_field;
	}
	public function getColumnName()
	{
		return $this->_column_name;
	}
}
?>