<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for the operation of a where clause.
* 
*/
class Operation
{
    private $_operation_code;
    public function __construct($op)
    {
        $this->_operation_code = $op;
    }
    public function __toString()
    {
        return $this->_operation_code;
    }
}
