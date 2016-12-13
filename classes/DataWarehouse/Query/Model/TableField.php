<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for describing field in a table.
* 
*/
class TableField extends Field
{ 
	private $_table;
	public function __construct(\DataWarehouse\Query\Model\Table $table,
								$fieldname,
							   $aliasname = '')
	{
		$this->_table = $table;
		parent::__construct($fieldname, $aliasname==''?$fieldname:$aliasname);
	}
	
	public function getQualifiedName($show_alias = false)
	{
		$ret = $this->_table->getAlias().'.'.$this->getDefinition();

		if($show_alias == true)
		{
			$ret .= " as '".($this->getAlias()==''?$this->getDefinition():$this->getAlias())."'";
		}
		return $ret;
	}
	
	public function __toString()
	{
		return $this->_table->getAlias().'.'.$this->getDefinition();
	}

} 

?>
