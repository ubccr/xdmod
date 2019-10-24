<?php
namespace DataWarehouse\Query\Model;

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
