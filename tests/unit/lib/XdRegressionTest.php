<?php

namespace UnitTests;

use Exception;

class XdRegressionTest extends \PHPUnit\Framework\TestCase
{
    public function testLinearRegression1() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0);

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(0.0, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(0.0, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(0.0, $r_squared, 1.0e-10, '');
    }

    public function testLinearRegression2() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(1.0, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(0.0, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10, '');
    }

    public function testLinearRegression3() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 1.0, 1.0, 2.0, 1.0, 1.0, 1.0, 1.0, 1.0);


        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(-0.01666666666666666666, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.1944444444444444, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(-0.13693063937629155, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(0.018750000000000006, $r_squared, 1.0e-10, '');
    }

    public function testLinearRegression4() {

        $xVals = array();
        $yVals = array();
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = 2e-71 - 2e-71 * ($i/10000.0);
        }

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(-2.0, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(4.0e-71, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(-1.0, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10, '');
    }

    public function testLinearRegression5() {

        $xVals = array();
        $yVals = array();
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = - 2e-71 + 2e-71 * ($i/10000.0);
        }

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(2.0, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(4.0e-71, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10, '');
    }

    public function testLinearRegressionUnequalXIntervals()
    {
        $xVals = array(1.0, 3.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 3.0, 7.0, 8.0, 9.0);

        list($slope, $intersect, $correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(1.0, $slope, 1.0e-10, '');
        $this->assertEqualsWithDelta(0.0, $intersect, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $correlation, 1.0e-10, '');
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10, '');
    }

    public function testRegressionMismatch() {
        // We setup a custom error handler that throws an exception due to PHPUnit9+ no longer handles errors, only exceptions.
        // we can then use the standard `expectException` / `expectExceptionMessage` functions.
        set_error_handler(static function (int $errno, string $errstr) {
            throw new Exception($errstr);
        }, E_USER_ERROR);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('linear_regression(): Number of elements in coordinate arrays do not match.');

        $xVals = array(1.0, 2.0);
        $yVals = array(1.0, 1.0, 1.0);

        \xd_regression\linear_regression($xVals, $yVals);

        // We make sure to restore the previous error_handler before we exit the function.
        restore_error_handler();

    }
}
