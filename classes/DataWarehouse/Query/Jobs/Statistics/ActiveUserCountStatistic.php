<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Jul-25
*
* class for calculating the statistics pertaining to a job query
*/
class ActiveUserCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('count(distinct(jf.person_id))', 'active_person_count', 'Number of Users: Active', 'Number of Users', 0);
    }

    public function getInfo()
    {
        return 'The total number of users that used '.ORGANIZATION_NAME.' resources.';
    }
}
