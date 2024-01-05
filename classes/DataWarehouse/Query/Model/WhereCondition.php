<?php
namespace DataWarehouse\Query\Model;

class WhereCondition implements \Stringable
{
    public function __construct(public $_left, public $_operation, public $_right)
    {
    }

    public function __toString(): string
    {
        return $this->_left. ' '.$this->_operation.' '.$this->_right ;
    }
}
