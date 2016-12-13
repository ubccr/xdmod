<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2013-8-29
*
* class for calculating the total cpu hours 
*/

class TotalNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		parent::__construct('coalesce(sum(jf.node_time/3600.0),0)', 'total_node_hours', 'Node Hours: Total', 'Node Hour');
	}

	public function getInfo()
	{
		return 'The total node hours (number of nodes x wall time hours) used by '.ORGANIZATION_NAME.' jobs.';
	}
}
 
?>