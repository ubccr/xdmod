<?php

@require_once dirname(__FILE__).'/../../../../../configuration/linker.php';


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \DataWarehouse\Query\Model\Field
* 
*/

class FieldTest extends PHPUnit_Framework_TestCase
{
	private $_field;
	
	function setUp()
    {
		//$this->_field
    }
	
	function tearDown() {
		$this->_field = NULL;
	}
	
    
	public function testGetDefinition()
    {
		//$this->assertEquals( $this->_alias->getName(), 'alias_name', "This should pass" );
		
    }
	
	public function testGetAlias()
    {
		//$this->_alias1->setName('mock_name');
		//$this->assertEquals( $this->_alias1->getName(), 'mock_name', "This should pass" );
    }
	
	public function testGetQualifiedName()
	{
	}

}

?>
