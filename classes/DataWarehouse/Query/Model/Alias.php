<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for all objects that can be aliased
* 
*/
class Alias extends \Common\Identity
{
	public function __construct($aliasname)
	{
		parent::__construct($aliasname);
	}
	
}

?>