<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 * 
 * class for modelling a column for an aggregator
 *
 */
class TableColumn
{
	protected $_name;
	protected $_type;
	protected $_formula;
	protected $_in_group_by;
	protected $_in_cube;
	protected $_comment;
	public $_in_index;
	
	function __construct($name, $type, $formula ='', $in_group_by = false, $in_cube = true, $comment = '', $in_index = false) 
	{
		$this->_name = $name;	
		$this->_type = $type;
		$this->_formula = $formula;
		$this->_in_group_by = $in_group_by;
		$this->_in_cube = $in_cube;
		$this->_in_index = $in_index;
		$this->_comment = $comment;
	}
	
	public function __toString()
	{
		return $this->_name;
	}
	public function getName()
	{
		return $this->_name;
	}	
	public function getType()
	{
		return $this->_type;
	}	
	public function getFormula()
	{
		if(strlen($this->_formula) > 0) return $this->_formula;
		return $this->_name;
	}
	public function isInGroupBy()
	{
		return $this->_in_group_by;
	}
	public function isInCube()
	{
		return $this->_in_cube;
	}
	public function getComment()
	{
		return $this->_comment;
	}
}

?>