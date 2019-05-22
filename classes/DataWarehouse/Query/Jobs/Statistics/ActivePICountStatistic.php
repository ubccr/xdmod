<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class ActivePICountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COUNT(DISTINCT(jf.principalinvestigator_person_id))', 'active_pi_count', 'Number of PIs: Active', 'Number of PIs', 0);
    }

    public function getInfo()
    {
        return 'The total number of PIs that used ' . ORGANIZATION_NAME . ' resources.';
    }
}
