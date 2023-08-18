<?php
namespace DataWarehouse\Query\Model;

class WhereCondition
{
    public $_left;

    /**
     * @var mixed Right-hand side of the operator. An array should be used iff
     * the operator is 'IN'.
     */
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
        $right = $this->_right;
        if (is_array($this->_right)) {
            $right = sprintf('(%s)', implode(',', $this->_right));
        }
        return sprintf('%s %s %s', $this->_left, $this->_operation, $right);
    }
}
