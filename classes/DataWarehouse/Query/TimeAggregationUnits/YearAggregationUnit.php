<?php
namespace DataWarehouse\Query\TimeAggregationUnits;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* This class encapsulates the properties and functionality required
* by a Query for aggregating time by year
* NOTE: this class is not in use yet.
* 
*/

class YearAggregationUnit extends \DataWarehouse\Query\TimeAggregationUnit
{
    /*
	* public constructor.
	*/
    public function __construct()
    {
        parent::__construct('year');
    }//__construct()
    
    /*
	* @returns the minimum integer value a year could have in the duration of a year
	*/
    public function getMinPeriodPerYear()
    {
        return 1;
    }//getMinPeriodPerYear()
    
    /*
	* @returns the maximum integer value a year could have in the duration of a year
	*/
    public function getMaxPeriodPerYear()
    {
        return 1;
    }//getMaxPeriodPerYear()
        
    public function getTimeLabel($timestamp)
    {
        $date = getdate($timestamp);
        return $date['year'];
    }
}
