<?php
/**
 * Query the data warehouse for aggregate data specific to a given Realm. An aggregate query returns
 * statistics grouped by the distinct dimensional values for a specific GroupBy. For example, in the
 * Jobs Realm using group by "none", the number of CPU hours will be a single value for the total
 * number of CPU hours for all resources in the realm combined. Using group by "resource" will
 * return the total CPU hours for each distinct value of the resource dimension. Any code specific
 * to aggregate queries should be included in this class.
 *
 * For example, the following query will be generated to obtain the total cpu hours grouped by
 * resource:
 *
 * select
 * rf.code as 'resource_order_id',
 * rf.id as 'resource_id',
 * replace(rf.code,'-',' ') as resource_name,
 * replace(rf.code,'-',' ') as resource_short_name,
 * coalesce(sum(jf.cpu_time),0)/3600.0 as total_cpu_hours
 * from modw_aggregates.jobfact_by_day jf,
 * modw.days d,
 * modw.resourcefact rf
 * where d.id = jf.day_id
 * and jf.day_id between 201600357 and 201700001
 * and rf.id = jf.task_resource_id
 * group by rf.id
 * order by total_cpu_hours desc, rf.code asc;
 *
 * +-------------------+-------------+---------------+---------------------+-----------------+
 * | resource_order_id | resource_id | resource_name | resource_short_name | total_cpu_hours |
 * +-------------------+-------------+---------------+---------------------+-----------------+
 * | robertson         |           5 | robertson     | robertson           |     461887.9808 |
 * | frearson          |           1 | frearson      | frearson            |     217650.7417 |
 * | mortorq           |           2 | mortorq       | mortorq             |      77264.8497 |
 * | pozidriv          |           4 | pozidriv      | pozidriv            |      71984.2764 |
 * | phillips          |           3 | phillips      | phillips            |      11767.9883 |
 * +-------------------+-------------+---------------+---------------------+-----------------+
 */

namespace DataWarehouse\Query;

use Log as Logger;  // CCR implementation of PEAR logger

class AggregateQuery extends Query implements iQuery
{
    /**
     * @see iQuery::getQueryType()
     */

    public function getQueryType()
    {
        return 'aggregate';
    }
}
