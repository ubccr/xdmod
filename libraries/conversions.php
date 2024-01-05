<?php

	namespace xd_conversions;

	// --------------------------------
	
	function value_to_mega_value(&$value, $key): void {
	        $value = $value / 1_000_000.0;
	}
	
	function value_to_kilo_value(&$value, $key): void {
	        $value = $value / 1000.0;
	}
	
	function hour_to_minute(&$value, $key): void {
	        $value = $value * 60.0;
	}
	
	function hour_to_day(&$value, $key): void {
	        $value = $value / 24.0;
	}
	
	function hour_to_year(&$value, $key): void {
	        $value = $value / 8765.81277;
	}
	
	function day_to_hour(&$value, $key): void {
	        $value = $value * 24.0;
	}
	
	function day_to_year(&$value, $key): void {
	        $value = $value / 365.242199;
	}
	
	function day_to_millenium(&$value, $key): void {
	        $value = $value / 365242.199;
	}
