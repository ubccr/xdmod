<?php

namespace UnitTests\DataWarehouse\Query\Model;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \DataWarehouse\Query\Model\Alias
*
*/

class AliasTest extends \PHPUnit\Framework\TestCase
{
    private $_alias;
    private $_alias1;

    public function setup(): void
    {
        $this->_alias = new \DataWarehouse\Query\Model\Alias('alias_name');
        $this->_alias1 = new \DataWarehouse\Query\Model\Alias('');
    }

    public function tearDown(): void {
        $this->_alias = null;
        $this->_alias1 = null;
    }


    public function testGetName()
    {
        $this->assertEquals('alias_name',$this->_alias->getName(), "This should pass" );

    }

    public function testSetName()
    {
        $this->_alias1->setName('mock_name');
        $this->assertEquals('mock_name', $this->_alias1->getName(), "This should pass" );
    }

    public function testToString()
    {
        $this->assertEquals('alias_name',$this->_alias->__toString(), "This should pass" );
    }
}
