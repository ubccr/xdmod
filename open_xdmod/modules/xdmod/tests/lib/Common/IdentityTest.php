<?php

namespace UnitTests\Common;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\Identity
* 
*/

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    private $_identity;
    private $_identity1;
    public function setUp()
    {
        $this->_identity = new \Common\Identity('identity_name');
        $this->_identity1 = new \Common\Identity('');
        set_error_handler(array($this, 'errorHandler'));
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new \InvalidArgumentException(
            sprintf(
                'Missing argument. %s %s %s %s',
                $errno,
                $errstr,
                $errfile,
                $errline
            )
        );
    }

    public function tearDown() {
        restore_error_handler();
        $this->_identity = null;
        $this->_identity1 = null;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNoDefaultParameterToConstructor()
    {
        $identity =  new \Common\Identity(); // this construction should fail since the name parameter is not specified
    }

    public function testGetName()
    {
        $this->assertEquals($this->_identity->getName(), 'identity_name', "This should pass" );
        $this->assertEquals($this->_identity1->getName(), '', "This should pass" );
    }

    public function testSetName()
    {
        $this->_identity1->setName('mock_name');
        $this->assertEquals($this->_identity1->getName(), 'mock_name', "This should pass" );
    }

    public function testToString()
    {
        $this->assertEquals($this->_identity->__toString(), 'identity_name', "This should pass" );
    }
}
