<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class ActiveInstitutionCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COUNT(DISTINCT(jf.person_organization_id))', 'active_institution_count', 'Number of Institutions: Active', 'Number of Institutions', 0);
    }

    public function getInfo()
    {
        return 'The total number of institutions that used ' . ORGANIZATION_NAME . ' resources.';
    }
}
