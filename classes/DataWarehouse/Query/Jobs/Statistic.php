<?php
namespace DataWarehouse\Query\Jobs;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* Abstract class for defining classes pertaining to a query field that calculates some statistic.
* 
*/
class Statistic extends \DataWarehouse\Query\Statistic
{	
	public function getWeightStatName()
	{
		return 'running_job_count';
	}
}

?>