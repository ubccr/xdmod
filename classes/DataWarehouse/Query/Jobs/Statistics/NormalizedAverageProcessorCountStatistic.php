<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class NormalizedAverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        $job_count = 'jf.running_job_count';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');
                $job_count = 'CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.running_job_count ELSE jf.started_job_count END';
            }
        }

        parent::__construct(
            '100.0 *
                COALESCE(
                    SUM(jf.processor_count * ' . $job_count . ')
                    /
                    SUM(' . $job_count . ')
                    /
                    (
                        SELECT
                            SUM(rrf.processors)
                        FROM
                            modw.resourcespecs rrf
                        WHERE
                            FIND_IN_SET(
                                rrf.resource_id,
                                GROUP_CONCAT(distinct jf.task_resource_id)
                            ) <> 0
                            AND ' . $query_instance->getAggregationUnit()->getUnitName() . '_end_ts >= rrf.start_date_ts
                            AND (
                                rrf.end_date_ts IS NULL
                                OR ' . $query_instance->getAggregationUnit()->getUnitName() . '_end_ts <= rrf.end_date_ts
                            )
                    ),
                0)',
            'normalized_avg_processors',
            'Job Size: Normalized',
            '% of Total Cores',
            1
        );
    }

    public function getInfo()
    {
        return  'The percentage average size ' . ORGANIZATION_NAME . ' job over total machine cores.<br><i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.';
    }
}
