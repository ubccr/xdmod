<?php
namespace DataWarehouse\Query\TimeAggregationUnits;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* This class encapsulates the properties and functionality required
* by a Query for aggregating time by month
* 
*/
class MonthAggregationUnit extends \DataWarehouse\Query\TimeAggregationUnit
{
    /*
	* public constructor.
	*/
    public function __construct()
    {
        parent::__construct('month');
    }//__construct()
    
    /*
	* @returns the minimum integer value a month could have in the duration of a year
	*/
    public function getMinPeriodPerYear()
    {
        return 1;
    }//getMinPeriodPerYear()
    
    /*
	* @returns the maximum integer value a month could have in the duration of a year
	*/
    public function getMaxPeriodPerYear()
    {
        return 12;
    }//getMaxPeriodPerYear()

    
    public function getTimeLabel($timestamp)
    {
        
        return date('Y-m', $timestamp);
    }
}
