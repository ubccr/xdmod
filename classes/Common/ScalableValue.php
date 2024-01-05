<?php
namespace Common;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class holds two values, one the original double and the other is the scaled value.
* 
*/
class ScalableValue implements \Stringable
{
    private $_value;
    private $_scaled_value;

    public function __construct($value = 0, $scale = 1.0, private $_scale_exponent = 1.0)
    {
		$this->set($value, $scale);
    }

    public function set($value = 0, $scale = 1.0): void
    {	
        $this->_value = $value;
		if($scale < 1)
		{
        	$this->_scaled_value = $value * $scale ** $this->_scale_exponent;
		}else
		{
			$this->_scaled_value = $value * $scale;
		}
    }

    public function get($scaled = true)
    {
        if($scaled) return $this->_scaled_value;
        else return $this->_value;
    }

    public function __toString(): string
    {
        return "value: {$this->get(false)}, scaled: {$this->get(true)}";
    }

}
