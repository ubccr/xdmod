<?php

require_once __DIR__.'/../../configuration/linker.php';

class XdRegressionTest extends PHPUnit_Framework_TestCase
{
    function testLinearRegression1() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0);

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(0.0, $slope, '', 1.0e-10);
        $this->assertEquals(1.0, $intersect, '', 1.0e-10);
        $this->assertEquals(0.0, $correlation, '', 1.0e-10);
        $this->assertEquals(0.0, $r_squared, '', 1.0e-10);
    }

    function testLinearRegression2() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(1.0, $slope, '', 1.0e-10);
        $this->assertEquals(0.0, $intersect, '', 1.0e-10);
        $this->assertEquals(1.0, $correlation, '', 1.0e-10);
        $this->assertEquals(1.0, $r_squared, '', 1.0e-10);
    }

    function testLinearRegression3() {

        $xVals = array(1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 1.0, 1.0, 2.0, 1.0, 1.0, 1.0, 1.0, 1.0);


        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(-0.01666666666666666, $slope, '', 1.0e-10);
        $this->assertEquals(1.1944444444444444,   $intersect, '', 1.0e-10);
        $this->assertEquals(-0.13693063937629155, $correlation, '', 1.0e-10);
        $this->assertEquals(0.018750000000000006, $r_squared, '', 1.0e-10);
    }

    function testLinearRegression4() {

        $xVals = array();
        $yVals = array();
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = 2e-71 - 2e-71 * ($i/10000.0);
        }

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(-2.0, $slope, '', 1.0e-10);
        $this->assertEquals(4.0e-71, $intersect, '', 1.0e-10);
        $this->assertEquals(-1.0, $correlation, '', 1.0e-10);
        $this->assertEquals(1.0, $r_squared, '', 1.0e-10);
    }

    function testLinearRegression5() {

        $xVals = array();
        $yVals = array();
        for($i=0; $i < 100000; $i++) {
            $xVals[] = 1e-71 + 1e-71 * ($i/10000.0);
            $yVals[] = - 2e-71 + 2e-71 * ($i/10000.0);
        }

        list($slope, $intersect,$correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(2.0, $slope, '', 1.0e-10);
        $this->assertEquals(4.0e-71, $intersect, '', 1.0e-10);
        $this->assertEquals(1.0, $correlation, '', 1.0e-10);
        $this->assertEquals(1.0, $r_squared, '', 1.0e-10);
    }

    public function testLinearRegressionUnequalXIntervals()
    {
        $xVals = array(1.0, 3.0, 7.0, 8.0, 9.0);
        $yVals = array(1.0, 3.0, 7.0, 8.0, 9.0);

        list($slope, $intersect, $correlation, $r_squared) = \xd_regression\linear_regression($xVals, $yVals);

        $this->assertEquals(1.0, $slope, '', 1.0e-10);
        $this->assertEquals(0.0, $intersect, '', 1.0e-10);
        $this->assertEquals(1.0, $correlation, '', 1.0e-10);
        $this->assertEquals(1.0, $r_squared, '', 1.0e-10);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    function testRegressionMismatch() {

        $xVals = array(1.0, 2.0);
        $yVals = array(1.0, 1.0, 1.0);

        \xd_regression\linear_regression($xVals, $yVals);

    }
}

?>
