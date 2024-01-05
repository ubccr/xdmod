<?php

namespace UnitTests\Common;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\Identity
*
*/

use ArgumentCountError;

class IdentityTest extends \PHPUnit\Framework\TestCase
{
    private $_identity;
    private $_identity1;
    public function setUp(): void
    {
        $this->_identity = new \Common\Identity('identity_name');
        $this->_identity1 = new \Common\Identity('');
        set_error_handler([$this, 'errorHandler']);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline): void
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
    public function testNoDefaultParameterToConstructor(): void
    {
        $this->expectException(ArgumentCountError::class);
        new \Common\Identity(); // this construction should fail since the name parameter is not specified
    }

    public function testGetName(): void
    {
        $this->assertEquals('identity_name', $this->_identity->getName(), "This should pass" );
        $this->assertEquals('', $this->_identity1->getName(), "This should pass" );
    }

    public function testSetName(): void
    {
        $this->_identity1->setName('mock_name');
        $this->assertEquals('mock_name', $this->_identity1->getName(), "This should pass" );
    }

    public function testToString(): void
    {
        $this->assertEquals('identity_name', $this->_identity->__toString(), "This should pass" );
    }
}
