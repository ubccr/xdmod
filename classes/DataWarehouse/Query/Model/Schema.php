<?php
namespace DataWarehouse\Query\Model;

class Schema extends \Common\Identity
{
    public function __construct($schemaname)
    {
        $this->setName($schemaname);
    }
}
