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
        $this->assertEquals($this->_alias->getName(), 'alias_name', "This should pass" );

    }

    public function testSetName()
    {
        $this->_alias1->setName('mock_name');
        $this->assertEquals($this->_alias1->getName(), 'mock_name', "This should pass" );
    }

    public function testToString()
    {
        $this->assertEquals($this->_alias->__toString(), 'alias_name', "This should pass" );
    }
}
