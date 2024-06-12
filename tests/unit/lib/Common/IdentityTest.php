<?php

namespace UnitTests\Common;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\Identity
*
*/

class IdentityTest extends \PHPUnit\Framework\TestCase
{
    private $_identity;
    private $_identity1;
    public function setup(): void
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

    public function tearDown(): void {
        restore_error_handler();
        $this->_identity = null;
        $this->_identity1 = null;
    }

    /**
     *
     * @requires PHP 7.1
     */
    public function testNoDefaultParameterToConstructor()
    {
        $this->expectException(\ArgumentCountError::class);
        new \Common\Identity(); // this construction should fail since the name parameter is not specified
    }

    public function testGetName()
    {
        $this->assertEquals('identity_name', $this->_identity->getName(), "This should pass" );
        $this->assertEquals('', $this->_identity1->getName(), "This should pass" );
    }

    public function testSetName()
    {
        $this->_identity1->setName('mock_name');
        $this->assertEquals('mock_name', $this->_identity1->getName(), "This should pass" );
    }

    public function testToString()
    {
        $this->assertEquals('identity_name', $this->_identity->__toString(), "This should pass" );
    }
}
