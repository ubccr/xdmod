<?php
namespace DataWarehouse\Query\TimeAggregationUnits;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* This class encapsulates the properties and functionality required
* by a Query for aggregating time by day
*
*/
class DayAggregationUnit extends \DataWarehouse\Query\TimeAggregationUnit
{
    /*
    * public constructor.
    */
    public function __construct()
    {
        parent::__construct('day');
    } //__construct()

    /*
    * @returns the minimum integer value a day could have in the duration of a year
    */
    public function getMinPeriodPerYear()
    {
        return 1;
    } //getMinPeriodPerYear()

    /*
    * @returns the maximum integer value a day could have in the duration of a year
    */
    public function getMaxPeriodPerYear()
    {
        return 366;
    } //getMaxPeriodPerYear()

    public function getTimeLabel($timestamp)
    {
        return date('Y-m-d', $timestamp);
    }
}
