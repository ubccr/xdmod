<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Jul-25
*
* class for calculating the statistics pertaining to a job query
*/
class ActiveInstitutionCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		parent::__construct('count(distinct(jf.person_organization_id))', 'active_institution_count', 'Number of Institutions: Active', 'Number of Institutions',0); 
	}

	public function getInfo()
	{
		return "The total number of institutions that used ".ORGANIZATION_NAME." resources.";
	}
}
?>