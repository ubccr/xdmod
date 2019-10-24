<?php
namespace DataWarehouse\Query\Model;

class Parameter extends \Common\Identity
{
    private $_operator;// <, >, <=, >=, <>, ==, is, is not, in
    private $_value;
    public function __construct($name, $operator, $value)
    {
        parent::__construct($name);
        $this->_operator = $operator;
        $this->_value = $value;
    }

    public function getOperator()
    {
        return $this->_operator;
    }

    public function getValue()
    {
        return $this->_value;
    }
}
