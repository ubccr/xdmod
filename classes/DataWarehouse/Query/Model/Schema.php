<?php
namespace DataWarehouse\Query\Model;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for a database schema
* 
*/
class Schema extends \Common\Identity
{
    public function __construct($schemaname)
    {
        $this->setName($schemaname);
    }
}
