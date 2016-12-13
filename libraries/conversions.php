<?php

	namespace xd_conversions;

	// --------------------------------
	
	function value_to_mega_value(&$value, $key) {
	        $value = $value / 1000000.0;
	}
	
	function value_to_kilo_value(&$value, $key) {
	        $value = $value / 1000.0;
	}
	
	function hour_to_minute(&$value, $key) {
	        $value = $value * 60.0;
	}
	
	function hour_to_day(&$value, $key) {
	        $value = $value / 24.0;
	}
	
	function hour_to_year(&$value, $key) {
	        $value = $value / 8765.81277;
	}
	
	function day_to_hour(&$value, $key) {
	        $value = $value * 24.0;
	}
	
	function day_to_year(&$value, $key) {
	        $value = $value / 365.242199;
	}
	
	function day_to_millenium(&$value, $key) {
	        $value = $value / 365242.199;
	}

?>
