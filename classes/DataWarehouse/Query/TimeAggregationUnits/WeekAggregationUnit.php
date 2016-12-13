<?php
namespace DataWarehouse\Query\TimeAggregationUnits;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* This class encapsulates the properties and functionality required
* by a Query for aggregating time by week
* 
*/
class WeekAggregationUnit extends \DataWarehouse\Query\TimeAggregationUnit
{
	/*
	* public constructor.
	*/	
	public function __construct()
	{
		parent::__construct('week');
	}//__construct() 
	
	/*
	* @returns the minimum integer value a week could have in the duration of a year
	*/
	public function getMinPeriodPerYear()
	{
		return 0;
	}//getMinPeriodPerYear()
	
	/*
	* @returns the maximum integer value a week could have in the duration of a year
	*/
	public function getMaxPeriodPerYear()
	{
		return 51;
	}//getMaxPeriodPerYear()
	
		
	public function getTimeLabel($timestamp)
	{
		$date = getdate($timestamp);
		return 'Week '.(1+intval($date['yday']/7)).' starting '.date('Y-m-d',$timestamp);
	}
	
}



?>