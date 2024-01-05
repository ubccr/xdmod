<?php
namespace DataWarehouse\Query\Model;

class OrderBy implements \Stringable
{
    protected $_field;
    public function __construct(\DataWarehouse\Query\Model\Field $field, protected $_order, protected $_column_name)
    {
        $this->_field = $field;
    }

    public function getOrder()
    {
        return $this->_order;
    }
    public function getField()
    {
        return $this->_field;
    }
    public function getColumnName()
    {
        return $this->_column_name;
    }
    public function __toString(): string
    {
        return sprintf("%s %s", $this->_field, $this->_order);
    }
}
