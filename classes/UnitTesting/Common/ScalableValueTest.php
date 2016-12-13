<?php

@require_once dirname(__FILE__).'/../../../configuration/linker.php';


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\ScalableValue
* 
*/

class ScalableValueTest extends PHPUnit_Framework_TestCase
{
	private $_scalableValue;
	function setUp()
    {
		$this->_scalableValue = new \Common\ScalableValue(10,.5,.2);
    }
	
	function tearDown() 
	{
		$this->_scalableValue = NULL;	
	}
	   
	public function testGet()
    {
		$this->assertEquals( $this->_scalableValue->get(false), 10, "This should pass" );
		$this->assertEquals( $this->_scalableValue->get(true), 10 * pow(.5,.2), "This should pass" );
    }

}

?>
