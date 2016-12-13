<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the statistics pertaining to a jobs query
*/
class RateOfUsageStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance)
	{
		$formula = "coalesce(sum(jf.local_charge)/".($query_instance != NULL?$query_instance->getDurationFormula():1).",0)";
		parent::__construct($formula, 'rate_of_usage', 'Allocation Usage Rate', 'XD SU/Hour',2);  
	}

	public function getInfo()
	{
		return 	"The rate of ".ORGANIZATION_NAME." allocation usage in XD SUs per hour.";
	}
}
?>