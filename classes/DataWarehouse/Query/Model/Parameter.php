<?php
namespace DataWarehouse\Query\Model;

class Parameter extends \Common\Identity
{
    /**
     * @var string SQL query parameter operator. Typically: <, >, <=, >=, <>, ==, IS, IS NOT, IN
     */

    protected $_operator;

    /**
     * @var string Right-hand side of the operator
     */

    protected $_value;

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

    /**
     * Provide a human readable string representing this parameter.
     */

    public function __toString()
    {
        return sprintf('%s %s %s', $this->_name, $this->_operator, $this->_value);
    }
}
