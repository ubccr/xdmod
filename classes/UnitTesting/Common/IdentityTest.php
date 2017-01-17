<?php

@require_once dirname(__FILE__).'/../../../configuration/linker.php';


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\Identity
* 
*/

class IdentityTest extends PHPUnit_Framework_TestCase
{
    private $_identity;
    private $_identity1;
    function setUp()
    {
        $this->_identity = new \Common\Identity('identity_name');
        $this->_identity1 = new \Common\Identity('');
    }
    
    function tearDown()
    {
        $this->_identity = null;
        $this->_identity1 = null;
    }
    
    public function testNoDefaultParameterToConstructor()
    {
        try {
            $identity = null;
            $identity =  new \Common\Identity(); // this construction should fail since the name parameter is not specified
        } catch (Exception $ex) {
            $this->assertEquals($identity, null, "This should pass");
            return;
        }
        $this->fail("\Common\Identity constructor fails to reject construction without parameter");
    }
    
    public function testGetName()
    {
        $this->assertEquals($this->_identity->getName(), 'identity_name', "This should pass");
        $this->assertEquals($this->_identity1->getName(), '', "This should pass");
    }
    
    public function testSetName()
    {
        $this->_identity1->setName('mock_name');
        $this->assertEquals($this->_identity1->getName(), 'mock_name', "This should pass");
    }
    
    public function testToString()
    {
        $this->assertEquals($this->_identity->__toString(), 'identity_name', "This should pass");
    }
}
