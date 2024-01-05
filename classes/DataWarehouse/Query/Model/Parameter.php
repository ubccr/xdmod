<?php
namespace DataWarehouse\Query\Model;

class Parameter extends \Common\Identity
{
    /**
     * @param string $operator
     * @param string $value
     */
    public function __construct($name, /**
     * @var string SQL query parameter operator. Typically: <, >, <=, >=, <>, ==, IS, IS NOT, IN
     */
    protected $_operator, /**
     * @var string Right-hand side of the operator
     */
    protected $_value)
    {
        parent::__construct($name);
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

    public function __toString(): string
    {
        return sprintf('%s %s %s', $this->_name, $this->_operator, $this->_value);
    }
}
