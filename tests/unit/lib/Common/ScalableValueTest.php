<?php

namespace UnitTests\Common;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed for class \Common\ScalableValue
*
*/

class ScalableValueTest extends \PHPUnit\Framework\TestCase
{
    private $_scalableValue;
    public function setup(): void
    {
        $this->_scalableValue = new \Common\ScalableValue(10, .5, .2);
    }

    public function tearDown(): void
    {
        $this->_scalableValue = null;
    }

    public function testGet()
    {
        $this->assertEquals($this->_scalableValue->get(false), 10, "This should pass" );
        $this->assertEquals($this->_scalableValue->get(true), 10 * pow(.5, .2), "This should pass" );
    }
}
