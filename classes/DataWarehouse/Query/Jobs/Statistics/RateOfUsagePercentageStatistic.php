<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the burn rate %
*/

class RateOfUsagePercentageStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $duration_formula = "1";
        if($query_instance != null)
        {
            $duration_formula = $query_instance->getDurationFormula();
        }
        $coversion_factor_statement = "coalesce((select conversion_factor
                    from modw.allocationadjustment  aladj
                    where aladj.allocation_resource_id = 1546
                    and aladj.site_resource_id = alc.resource_id
                    and aladj.start_date <= alc.initial_start_date and (aladj.end_date is null or alc.initial_start_date <= aladj.end_date)
                    limit 1
                 ), 1.0)";

        parent::__construct(
            "100.00*coalesce((sum(jf.local_charge_su)/".($query_instance != null ? $query_instance->getDurationFormula() : 1) . ")
                /
                (select sum(alc.base_allocation*$coversion_factor_statement/((unix_timestamp(alc.end_date) - unix_timestamp(alc.initial_start_date))/3600.0)) from modw.allocation alc where find_in_set(alc.id,group_concat(distinct jf.allocation_id)) <> 0 ),0)
            ",
            'burn_rate',
            'Allocation Burn Rate',
            '%',
            2
        );
    }

    public function getInfo()
    {
        return "The percentage of " . ORGANIZATION_NAME . " allocation usage in the given duration.";
    }
}
