<?php
 namespace xd_regression;
/**
 * linear regression function
 * @param $x array x-coords
 * @param $y array y-coords
 * @returns array() m=>slope, b=>intercept, r=>coefficient of correlation, r_squared=>coeff of dependence
 */
function linear_regression($x, $y) {

  // calculate number points
  $n = count($x);
  
  // ensure both arrays of points are the same size
  if ($n != count($y)) {

    trigger_error("linear_regression(): Number of elements in coordinate arrays do not match.", E_USER_ERROR);
  
  }

  // calculate sums
  $x_sum = array_sum($x);
  $y_sum = array_sum($y);

  $xx_sum = 0;
  $xy_sum = 0;
  $yy_sum = 0;
  
  for($i = 0; $i < $n; $i++) {
  
    $xy_sum+=($x[$i]*$y[$i]);
    $xx_sum+=($x[$i]*$x[$i]);
    $yy_sum+=($y[$i]*$y[$i]);
  }
  
 
  // calculate slope
  $m = (($n * $xy_sum) - ($x_sum * $y_sum)) / (($n * $xx_sum) - ($x_sum * $x_sum));
  
  // calculate intercept
  $b = ($y_sum - ($m * $x_sum)) / $n;
  
  // calcuate r = coeff of correlation
  $den = (sqrt(($n * ($xx_sum)) - pow($x_sum, 2)) * sqrt(($n * $yy_sum) - pow($y_sum, 2)));
  if($den != 0)  
  {
    $r = (($n * $xy_sum) - ($x_sum * $y_sum)) / $den;
    if ($r > 1.0)
    {
      $r = 1.0;
    }
    else if ($r < -1.0)
    {
      $r = -1.0;
    }
  } 
  else
  {
    $r = 0.0;
  }
  $r_squared = pow($r,2);

  // return result
  return array($m, $b, $r, $r_squared);

}
