<?php

@require_once dirname(__FILE__).'/../../../../../configuration/linker.php';


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \DataWarehouse\Query\Model\Alias
* 
*/

class AliasTest extends PHPUnit_Framework_TestCase
{
	private $_alias;
	private $_alias1;
	
	function setUp()
    {
		$this->_alias = new \DataWarehouse\Query\Model\Alias('alias_name');
		$this->_alias1 = new \DataWarehouse\Query\Model\Alias('');
    }
	
	function tearDown() {
		$this->_alias = NULL;
		$this->_alias1 = NULL;
	}
	
    
	public function testGetName()
    {
		$this->assertEquals( $this->_alias->getName(), 'alias_name', "This should pass" );
		
    }
	
	public function testSetName()
    {
		$this->_alias1->setName('mock_name');
		$this->assertEquals( $this->_alias1->getName(), 'mock_name', "This should pass" );
    }
	
	public function testToString()
    {
		$this->assertEquals( $this->_alias->__toString(), 'alias_name', "This should pass" );
    }

}

?>
