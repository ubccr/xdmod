<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for describing the where clause of a query.
* 
*/
class WhereCondition
{
	public $_left;
	public $_right;
	public $_operation;
	
	public function __construct($left, $operation, $right)
	{
		$this->_left = $left;
		$this->_right = $right;
		$this->_operation = $operation;
	}
	
	public function __toString()
	{
		return $this->_left. ' '.$this->_operation.' '.$this->_right ;
	}
}

?>