<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class RateOfUsagePercentageStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $duration_formula = "1";
        if ($query_instance != null) {
            $duration_formula = $query_instance->getDurationFormula();
        }
        $coversion_factor_statement = "COALESCE((SELECT conversion_factor
                    FROM modw.allocationadjustment  aladj
                    WHERE aladj.allocation_resource_id = 1546
                    AND aladj.site_resource_id = alc.resource_id
                    AND aladj.start_date <= alc.initial_start_date AND (aladj.end_date IS NULL OR alc.initial_start_date <= aladj.end_date)
                    LIMIT 1
                 ), 1.0)";

        parent::__construct(
            "100.00
            *
            COALESCE(
                (
                    SUM(jf.local_charge_su)
                    /
                    " . ($query_instance != null ? $query_instance->getDurationFormula() : 1) . "
                )
                /
                (
                    SELECT
                        SUM(
                            alc.base_allocation
                            *
                            $coversion_factor_statement
                            /
                            (
                                (unix_timestamp(alc.end_date) - unix_timestamp(alc.initial_start_date))
                            )
                        )
                    FROM
                        modw.allocation alc
                    WHERE
                        find_in_set(alc.id, GROUP_CONCAT(DISTINCT jf.allocation_id)) <> 0
                )
                ,0
            )
            /
            3600.0",
            'burn_rate',
            'Allocation Burn Rate',
            '%',
            2
        );
    }

    public function getInfo()
    {
        return 'The percentage of ' . ORGANIZATION_NAME . ' allocation usage in the given duration.';
    }
}
