<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Jul-25
*
* class for calculating the statistics pertaining to a job query
*/
class ActivePICountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		parent::__construct('count(distinct(jf.principalinvestigator_person_id))', 'active_pi_count', 'Number of PIs: Active', 'Number of PIs',0); 
	}

	public function getInfo()
	{
		return 'The total number of PIs that used '.ORGANIZATION_NAME.' resources.';
	}
}
?>