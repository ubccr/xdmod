<?php
namespace DataWarehouse\Query\Model;

class Operation implements \Stringable
{
    public function __construct(private $_operation_code)
    {
    }
    public function __toString(): string
    {
        return (string) $this->_operation_code;
    }
}
