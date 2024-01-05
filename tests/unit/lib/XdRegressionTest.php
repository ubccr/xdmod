<?php

namespace UnitTests;


use Exception;

class XdRegressionTest extends \PHPUnit\Framework\TestCase
{
    public function testLinearRegression1(): void {

        $xVals = [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0];
        $yVals = [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0];

        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(0.0, $slope, '', 1.0e-10);
        $this->assertEquals(1.0, $intersect, '', 1.0e-10);
        $this->assertEquals(0.0, $correlation, '', 1.0e-10);
        $this->assertEquals(0.0, $r_squared, '', 1.0e-10);
    }

    public function testLinearRegression2(): void {

        $xVals = [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0];
        $yVals = [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0];

        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(1.0, $slope, '', 1.0e-10);
        $this->assertEquals(0.0, $intersect, '', 1.0e-10);
        $this->assertEquals(1.0, $correlation, '', 1.0e-10);
        $this->assertEquals(1.0, $r_squared, '', 1.0e-10);
    }

    public function testLinearRegression3(): void {

        $xVals = [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0];
        $yVals = [1.0, 1.0, 1.0, 2.0, 1.0, 1.0, 1.0, 1.0, 1.0];


        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(-0.01666666666666666666, $slope, '', 1.0e-10);
        $this->assertEquals(1.1944444444444444, $intersect, '', 1.0e-10);
        $this->assertEquals(-0.13693063937629155, $correlation, '', 1.0e-10);
        $this->assertEquals(0.018750000000000006, $r_squared, '', 1.0e-10);
    }

    public function testLinearRegression4(): void {

        $xVals = [];
        $yVals = [];
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = 2e-71 - 2e-71 * ($i/10000.0);
        }

        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(-2.0, $slope, 1.0e-10);
        $this->assertEqualsWithDelta(4.0e-71, $intersect,1.0e-10);
        $this->assertEqualsWithDelta(-1.0, $correlation, 1.0e-10);
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10);
    }

    public function testLinearRegression5(): void {

        $xVals = [];
        $yVals = [];
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = - 2e-71 + 2e-71 * ($i/10000.0);
        }

        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(2.0, $slope, 1.0e-10);
        $this->assertEqualsWithDelta(4.0e-71, $intersect, 1.0e-10);
        $this->assertEqualsWithDelta(1.0, $correlation, 1.0e-10);
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10);
    }

    public function testLinearRegressionUnequalXIntervals(): void
    {
        $xVals = [1.0, 3.0, 7.0, 8.0, 9.0];
        $yVals = [1.0, 3.0, 7.0, 8.0, 9.0];

        [$slope, $intersect, $correlation, $r_squared] = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEqualsWithDelta(1.0, $slope, 1.0e-10);
        $this->assertEqualsWithDelta(0.0, $intersect, 1.0e-10);
        $this->assertEqualsWithDelta(1.0, $correlation, 1.0e-10);
        $this->assertEqualsWithDelta(1.0, $r_squared, 1.0e-10);
    }

    public function testRegressionMismatch(): void {
        set_error_handler(static function (int $errno, string $errstr) {
            throw new Exception($errstr, $errno);
        }, E_USER_ERROR);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('linear_regression(): Number of elements in coordinate arrays do not match.');

        $xVals = [1.0, 2.0];
        $yVals = [1.0, 1.0, 1.0];

        \xd_regression\linear_regression($xVals, $yVals);

        restore_error_handler();
    }
}
