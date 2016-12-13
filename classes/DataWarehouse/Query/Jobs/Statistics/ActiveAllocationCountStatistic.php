<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Jul-25
*
* class for calculating the statistics pertaining to a job query
*/
class ActiveAllocationCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		parent::__construct('count(distinct(jf.account_id))', 'active_allocation_count', 'Number of Allocations: Active', 'Number of Allocations',0); 
	}

	public function getInfo()
	{
		return "The total number of funded projects that used ".ORGANIZATION_NAME." resources.";
	}
}
?>