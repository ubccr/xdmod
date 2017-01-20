-- // Jobs Statistics

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_cpu_hours'                                                                                                                                                   name,
                 'Std Dev: CPU Hours: Per Job'                                                                                                                                         display,
                 'coalesce(sqrt((sum(jf.sum_cpu_time_squared)/sum(jf.running_job_count))-pow(sum(jf.cpu_time)/sum(jf.running_job_count),2))/sqrt(sum(jf.running_job_count)),0)/3600.0' formula,
                 'sem_avg_cpu_hours'                                                                                                                                                   alias,
                 'CPU Hour'                                                                                                                                                            unit,
                 2                                                                                                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_cpu_hours_aggregate'                                                                                                                 name,
                 'Std Dev: CPU Hours: Per Job'                                                                                                                 display,
                 'coalesce(sqrt((sum(jf.sum_cpu_time_squared)/sum(jf.job_count))-pow(sum(jf.cpu_time)/sum(jf.job_count),2))/sqrt(sum(jf.job_count)),0)/3600.0' formula,
                 'sem_avg_cpu_hours'                                                                                                                           alias,
                 'CPU Hour'                                                                                                                                    unit,
                 2                                                                                                                                             decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'utilization' name,
                 'Utilization' display,
                 '
                100.0 * (
                    COALESCE(
                        SUM(jf.cpu_time / 3600.0)
                        /
                        (
                            SELECT SUM( ra.percent * inner_days.hours * rs.processors / 100.0 )
                            FROM modw.resourcespecs rs,
                                 modw.resource_allocated ra,
                                 modw.days inner_days
                            WHERE
                                inner_days.day_middle_ts BETWEEN ra.start_date_ts AND coalesce(ra.end_date_ts, 2147483647) AND
                                inner_days.day_middle_ts BETWEEN rs.start_date_ts AND coalesce(rs.end_date_ts, 2147483647) AND
                                inner_days.day_middle_ts BETWEEN $date_table_start_ts AND $date_table_end_ts AND
                                ra.resource_id = rs.resource_id
                                AND FIND_IN_SET(
                                    rs.resource_id,
                                    GROUP_CONCAT(DISTINCT jf.resource_id)
                                ) <> 0
                        ),
                        0
                    )
                )
            ' formula,
                 'utilization' alias,
                 '%'           unit,
                 2             decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'job_count'                     name,
                 'Number of Jobs Ended'          display,
                 'coalesce(sum(jf.job_count),0)' formula,
                 'job_count'                     alias,
                 'Number of Jobs'                unit,
                 0                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_institution_count'                   name,
                 'Number of Institutions: Active'             display,
                 'count(distinct(jf.person_organization_id))' formula,
                 'active_institution_count'                   alias,
                 'Number of Institutions'                     unit,
                 0                                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'started_job_count'                     name,
                 'Number of Jobs Started'                display,
                 'coalesce(sum(jf.started_job_count),0)' formula,
                 'started_job_count'                     alias,
                 'Number of Jobs'                        unit,
                 0                                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_node_hours'                                                                                                                                                                         name,
                 'Std Dev: Node Hours: Per Job'                                                                                                                                                               display,
                 'coalesce(sqrt((sum(jf.sum_node_time_squared)/sum(jf.\'.$job_count_formula.\'))-pow(sum(jf.node_time)/sum(jf.\'.$job_count_formula.\'),2))/sqrt(sum(jf.\'.$job_count_formula.\')),0)/3600.0' formula,
                 'sem_avg_node_hours'                                                                                                                                                                         alias,
                 'Node Hour'                                                                                                                                                                                  unit,
                 2                                                                                                                                                                                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_node_hours'                     name,
                 'Node Hours: Total'                    display,
                 'coalesce(sum(jf.node_time/3600.0),0)' formula,
                 'total_node_hours'                     alias,
                 'Node Hour'                            unit,
                 0                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'normalized_avg_processors'                                       name,
                 'Job Size: Normalized'                                            display,
                 '100.0*coalesce(ceil(sum(jf.processors*jf.\'.$job_count_formula.\')/sum(jf.\'.$job_count_formula.\'))/(select sum(rrf.processors) from modw.resourcespecs rrf where find_in_set(rrf.resource_id,group_concat(distinct jf.resource_id)) <> 0 and \'.$query_instance
                 ->getAggregationUnit()->
                 getUnitName().\'_end_ts >= rrf.start_date_ts and (rrf.end_date_ts is null or \'.$query_instance
                 ->getAggregationUnit()->
                 getUnitName().\'_end_ts <= rrf.end_date_ts)),0)' formula,
                 'normalized_avg_processors'                                       alias,
                 '% of Total Cores'                                                unit,
                 1                                                                 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_node_hours'                                                        name,
                 'Node Hours: Per Job'                                                   display,
                 'coalesce(sum(jf.node_time/3600.0)/sum(jf.\'.$job_count_formula.\'),0)' formula,
                 'avg_node_hours'                                                        alias,
                 'Node Hour'                                                             unit,
                 2                                                                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'submitted_job_count'                     name,
                 'Number of Jobs Submitted'                display,
                 'coalesce(sum(jf.submitted_job_count),0)' formula,
                 'submitted_job_count'                     alias,
                 'Number of Jobs'                          unit,
                 0                                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'expansion_factor'                                                          name,
                 'User Expansion Factor'                                                     display,
                 'coalesce(sum(jf.sum_weighted_expansion_factor)/sum(jf.sum_job_weights),0)' formula,
                 'expansion_factor'                                                          alias,
                 'User Expansion Factor'                                                     unit,
                 1                                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_waitduration_hours'                                                                                                                                                                          name,
                 'Std Dev: Wait Hours: Per Job'                                                                                                                                                                        display,
                 'coalesce(sqrt((sum(coalesce(jf.sum_waitduration_squared,0))/sum(jf.started_job_count))-pow(sum(coalesce(jf.waitduration,0))/sum(jf.started_job_count),2))/sqrt(sum(jf.started_job_count)),0)/3600.0' formula,
                 'sem_avg_waitduration_hours'                                                                                                                                                                          alias,
                 'Hour'                                                                                                                                                                                                unit,
                 2                                                                                                                                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'rate_of_usage'                                                                                                                                                                                                                                                                                             name,
                 'Allocation Usage Rate'                                                                                                                                                                                                                                                                                     display,
                 'coalesce(sum(jf.local_charge)/".($query_instance != NULL?$query_instance->getDurationFormula():1).",0)coalesce(sqrt((sum(coalesce(jf.sum_waitduration_squared,0))/sum(jf.started_job_count))-pow(sum(coalesce(jf.waitduration,0))/sum(jf.started_job_count),2))/sqrt(sum(jf.started_job_count)),0)/3600.0' formula,
                 'sem_avg_waitduration_hours'                                                                                                                                                                                                                                                                                alias,
                 'Hour'                                                                                                                                                                                                                                                                                                      unit,
                 2                                                                                                                                                                                                                                                                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'min_processors'                                                                      name,
                 'Job Size: Min'                                                                       display,
                 'coalesce(ceil(min(case when jf.processors = 0 then null else jf.processors end)),0)' formula,
                 'min_processors'                                                                      alias,
                 'Core Count'                                                                          unit,
                 0                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_waitduration_hours'                                            name,
                 'Wait Hours: Per Job'                                               display,
                 'coalesce(sum(jf.waitduration/3600.0)/sum(jf.started_job_count),0)' formula,
                 'avg_waitduration_hours'                                            alias,
                 'Hour'                                                              unit,
                 2                                                                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_waitduration_hours'                name,
                 'Wait Hours: Total'                       display,
                 'coalesce(sum(jf.waitduration/3600.0),0)' formula,
                 'total_waitduration_hours'                alias,
                 'Hour'                                    unit,
                 0                                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_wallduration_hours'                name,
                 'Wall Hours: Total'                       display,
                 'coalesce(sum(jf.wallduration/3600.0),0)' formula,
                 'total_wallduration_hours'                alias,
                 'Hour'                                    unit,
                 0                                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_wallduration_hours'                                                                                                                                                  name,
                 'Std Dev: Wall Hours: Per Job'                                                                                                                                                display,
                 'coalesce(sqrt((sum(jf.sum_wallduration_squared)/sum(jf.running_job_count))-pow(sum(jf.wallduration)/sum(jf.running_job_count),2))/sqrt(sum(jf.running_job_count)),0)/3600.0' formula,
                 'sem_avg_wallduration_hours'                                                                                                                                                  alias,
                 'Hour'                                                                                                                                                                        unit,
                 2                                                                                                                                                                             decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_wallduration_hours_aggregate'                                                                                                                name,
                 'Std Dev: Wall Hours: Per Job'                                                                                                                        display,
                 'coalesce(sqrt((sum(jf.sum_wallduration_squared)/sum(jf.job_count))-pow(sum(jf.wallduration)/sum(jf.job_count),2))/sqrt(sum(jf.job_count)),0)/3600.0' formula,
                 'sem_avg_wallduration_hours'                                                                                                                          alias,
                 'Hour'                                                                                                                                                unit,
                 2                                                                                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_pi_count'                                     name,
                 'Number of PIs: Active'                               display,
                 'count(distinct(jf.principalinvestigator_person_id))' formula,
                 'active_pi_count'                                     alias,
                 'Number of PIs'                                       unit,
                 0                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'max_processors'                       name,
                 'Job Size: Max'                        display,
                 'coalesce(ceil(max(jf.processors)),0)' formula,
                 'max_processors'                       alias,
                 'Core Count'                           unit,
                 0                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_processors'                                                                      name,
                 'Job Size: Per Job'                                                                   display,
                 'coalesce(ceil(sum(jf.processors*jf.running_job_count)/sum(jf.running_job_count)),0)' formula,
                 'avg_processors'                                                                      alias,
                 'Core Count'                                                                          unit,
                 1                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_processors_aggregate'                                            name,
                 'Job Size: Per Job'                                                   display,
                 'coalesce(ceil(sum(jf.processors*jf.job_count)/sum(jf.job_count)),0)' formula,
                 'avg_processors'                                                      alias,
                 'Core Count'                                                          unit,
                 1                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_resource_count'           name,
                 'Number of Resources: Active'     display,
                 'count(distinct(jf.resource_id))' formula,
                 'active_resource_count'           alias,
                 'Number of Resources'             unit,
                 0                                 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'node_utilization' name,
                 'Node Utilization' display,
                 '
                100.0 * (
                    COALESCE(
                        SUM(jf.node_time / 3600.0)
                        /
                        (
                            SELECT
                                SUM(ra.percent * inner_days.hours * rs.q_nodes / 100.0)
                            FROM
                                modw.resourcespecs rs,
                                modw.resource_allocated ra,
                                modw.days inner_days
                            WHERE
                                    inner_days.day_middle_ts BETWEEN ra.start_date_ts AND COALESCE(ra.end_date_ts, 2147483647)
                                AND inner_days.day_middle_ts BETWEEN rs.start_date_ts AND COALESCE(rs.end_date_ts, 2147483647)
                                AND inner_days.day_middle_ts BETWEEN $date_table_start_ts AND $date_table_end_ts
                                AND ra.resource_id = rs.resource_id
                                AND FIND_IN_SET(
                                        rs.resource_id,
                                        GROUP_CONCAT(DISTINCT jf.resource_id)
                                    ) <> 0
                        ),
                        0
                    )
                )
            '      formula,
                 'node_utilization' alias,
                 '%'                unit,
                 2                  decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_job_size_weighted_by_cpu_hours' name,
                 'Job Size: Weighted By CPU Hours'    display,
                 'COALESCE(
                    SUM(jf.processors * jf.cpu_time) / SUM(jf.cpu_time),
                    0
                  )'                 formula,
                 'avg_job_size_weighted_by_cpu_hours' alias,
                 'Core Count'                         unit,
                 1                                    decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_allocation_count'        name,
                 'Number of Allocations: Active'  display,
                 'count(distinct(jf.account_id))' formula,
                 'active_allocation_count'        alias,
                 'Number of Allocations'          unit,
                 0                                decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_cpu_hours'                     name,
                 'CPU Hours: Total'                    display,
                 'coalesce(sum(jf.cpu_time/3600.0),0)' formula,
                 'total_cpu_hours'                     alias,
                 'CPU Hour'                            unit,
                 0                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'running_job_count'                     name,
                 'Number of Jobs Running'                display,
                 'coalesce(sum(jf.running_job_count),0)' formula,
                 'running_job_count'                     alias,
                 'Number of Jobs'                        unit,
                 0                                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'burn_rate'                                                                                                                                                                                                  name,
                 'Allocation Burn Rate'                                                                                                                                                                                       display,
                 '100.00*coalesce((sum(jf.local_charge)/($query_instance != NULL?$query_instance->getDurationFormula():1))/(select sum(alc.base_allocation*coalesce((select conversion_factor
                from modw.allocationadjustment  aladj
                where aladj.allocation_resource_id = 1546
                and aladj.site_resource_id = alc.resource_id
                and aladj.start_date <= alc.initial_start_date and (aladj.end_date is null or alc.initial_start_date <= aladj.end_date)
                limit 1
             ), 1.0)/((unix_timestamp(alc.end_date) - unix_timestamp(alc.initial_start_date))/3600.0)) from modw.allocation alc where find_in_set(alc.id,group_concat(distinct jf.allocation_id)) <> 0 ),0)' formula,
                 'burn_rate'                                                                                                                                                                                                  alias,
                 '%'                                                                                                                                                                                                          unit,
                 2                                                                                                                                                                                                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_wallduration_hours'                                            name,
                 'Wall Hours: Per Job'                                               display,
                 'coalesce(sum(jf.wallduration/3600.0)/sum(jf.running_job_count),0)' formula,
                 'avg_wallduration_hours'                                            alias,
                 'Hour'                                                              unit,
                 2                                                                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_wallduration_hours_aggregate'                          name,
                 'Wall Hours: Per Job'                                       display,
                 'coalesce(sum(jf.wallduration/3600.0)/sum(jf.job_count),0)' formula,
                 'avg_wallduration_hours'                                    alias,
                 'Hour'                                                      unit,
                 2                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_cpu_hours'                                                 name,
                 'CPU Hours: Per Job'                                            display,
                 'coalesce(sum(jf.cpu_time/3600.0)/sum(jf.running_job_count),0)' formula,
                 'avg_cpu_hours'                                                 alias,
                 'CPU Hour'                                                      unit,
                 2                                                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_cpu_hours_aggregate'                               name,
                 'CPU Hours: Per Job'                                    display,
                 'coalesce(sum(jf.cpu_time/3600.0)/sum(jf.job_count),0)' formula,
                 'avg_cpu_hours'                                         alias,
                 'CPU Hour'                                              unit,
                 2                                                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_processors_aggregate'                                                                                                              name,
                 'Std Dev: Job Size: Per Job'                                                                                                                display,
                 'coalesce(sqrt( (sum(pow(jf.processors,2)*jf.job_count)/sum(jf.job_count)) - pow(sum(jf.processors*jf.job_count) / sum(jf.job_count), 2) )' formula,
                 'sem_avg_processors'                                                                                                                        alias,
                 'Core Count'                                                                                                                                unit,
                 2                                                                                                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_processors'                                                                                                                                                        name,
                 'Std Dev: Job Size: Per Job'                                                                                                                                                display,
                 'coalesce(sqrt( (sum(pow(jf.processors,2)*jf.running_job_count)/sum(jf.running_job_count)) - pow(sum(jf.processors*jf.running_job_count) / sum(jf.running_job_count), 2) )' formula,
                 'sem_avg_processors'                                                                                                                                                        alias,
                 'Core Count'                                                                                                                                                                unit,
                 2                                                                                                                                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_person_count'           name,
                 'Number of Users: Active'       display,
                 'count(distinct(jf.person_id))' formula,
                 'active_person_count'           alias,
                 'Number of Users'               unit,
                 0                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Jobs Statistics

-- // Account Statistics

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'weight' name,
                 'Weight' display,
                 '1' formula,
                 'weight' alias,
                 'Weight' unit,
                 0 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'new_account_count' name,
                 'Number of User Accounts: Created' display,
                 'union_string_count(jf.person_ids_new, 1, 100000)' formula,
                 'new_account_count' alias,
                 'Number of User Accounts' unit,
                 0 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'open_account_count' name,
                 'Number of User Accounts: Open' display,
                 'union_string_count(jf.person_ids_open, 1, 100000)' formula,
                 'open_account_count' alias,
                 'Number of User Accounts' unit,
                 0 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'closed_account_count' name,
                 'Number of User Accounts: Closed' display,
                 'union_string_count(jf.person_ids_closed, 1, 100000)' formula,
                 'closed_account_count' alias,
                 'Number of User Accounts' unit,
                 0 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Account Statistics

-- // Allocation Statistics
INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'weight'                                     name,
                 'Number of Allocations'                      display,
                 'coalesce(count( distinct allocation_id),0)' formula,
                 'Number of Allocations'                      alias,
                 'Number of Allocations'                      unit,
                 0                                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_used_su'                                  name,
                 'XD SUs: Per Job'                              display,
                 'coalesce(sum(used)/sum(running_job_count),0)' formula,
                 'avg_used_su'                                  alias,
                 'XD SU'                                        unit,
                 2                                              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocated_nu'                                                  name,
                 'NUs: Allocated'                                                display,
                 'coalesce(sum(jf.base_fraction*jf.conversion_factor*21.576),0)' formula,
                 'allocated_nu'                                                  alias,
                 'NU'                                                            unit,
                 0                                                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'rate_of_usage'                                                                               name,
                 'Allocation Usage Rate'                                                                       display,
                 'coalesce(sum(used)/".($query_instance != NULL?$query_instance->getDurationFormula():1).",0)' formula,
                 'rate_of_usage'                                                                               alias,
                 'XD SU/Hour'                                                                                  unit,
                 0                                                                                             decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocated_su'                                           name,
                 'XD SUs: Allocated'                                      display,
                 'coalesce(sum(jf.base_fraction*jf.conversion_factor),0)' formula,
                 'allocated_su'                                           alias,
                 'XD SU'                                                  unit,
                 0                                                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'used_su'      name,
                 'XD SUs: Used' display,
                 'sum(used)'    formula,
                 'used_su'      alias,
                 'XD SU'        unit,
                 2              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'active_allocation_count'        name,
                 'Number of Allocations: Active'  display,
                 'count(distinct(jf.account_id))' formula,
                 'active_allocation_count'        alias,
                 'Number of Allocations'          unit,
                 0                                decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocated_raw_su'                  name,
                 'CPU Core Hours: Allocated'         display,
                 'coalesce(sum(jf.base_fraction),0)' formula,
                 'allocated_raw_su'                  alias,
                 'CPU Core Hours'                    unit,
                 0                                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'burn_rate'                                                                  name,
                 'Allocation Burn Rate'                                                       display,
                 '100.00*coalesce(sum(jf.used)/sum(jf.base_fraction*jf.conversion_factor),0)' formula,
                 'burn_rate'                                                                  alias,
                 '%'                                                                          unit,
                 2                                                                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Allocation Statistics

-- // Grant Statistics
INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'award_amount'                      name,
                 'Award Amount'                      display,
                 'coalesce(sum(jf.amount_dollar),0)' formula,
                 'award_amount'                      alias,
                 '$'                                 unit,
                 0                                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'award_count'                                  name,
                 'Number of Awards'                             display,
                 'coalesce(count(distinct(jf.proposal_num)),0)' formula,
                 'award_count'                                  alias,
                 'Number of Awards'                             unit,
                 0                                              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Grant Statistics

-- // Job Statistics: XSEDE
INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_su'                         name,
                 'XD SUs Charged: Total'            display,
                 'coalesce(sum(jf.local_charge),0)' formula,
                 'total_su'                         alias,
                 'XD SU'                            unit,
                 0                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_job_size_weighted_by_xd_su'                                      name,
                 'Job Size: Weighted By XD SUs'                                        display,
                 'coalesce(sum(jf.processors*jf.local_charge)/sum(jf.local_charge),0)' formula,
                 'avg_job_size_weighted_by_xd_su'                                      alias,
                 'Core Count'                                                          unit,
                 1                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_su'                                                                                                                                                                                name,
                 'Std Dev: XD SUs Charged: Per Job'                                                                                                                                                          display,
                 'coalesce(sqrt((sum(jf.sum_local_charge_squared)/sum(jf.\'.$job_count_formula.\'))-pow(sum(jf.local_charge)/sum(jf.\'.$job_count_formula.\'),2))/sqrt(sum(jf.\'.$job_count_formula.\')),0)' formula,
                 'sem_avg_su'                                                                                                                                                                                alias,
                 'XD SU'                                                                                                                                                                                     unit,
                 2                                                                                                                                                                                           decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'sem_avg_nu'                                                                                                                                                                                       name,
                 'Std Dev: NUs Charged: Per Job'                                                                                                                                                                    display,
                 'coalesce(sqrt((sum(jf.sum_local_charge_squared)/sum(jf.\'.$job_count_formula.\'))-pow(sum(jf.local_charge)/sum(jf.\'.$job_count_formula.\'),2))/sqrt(sum(jf.\'.$job_count_formula.\')),0)*21.576' formula,
                 'sem_avg_nu'                                                                                                                                                                                       alias,
                 'NU'                                                                                                                                                                                               unit,
                 2                                                                                                                                                                                                  decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_nu'                                                                 name,
                 'NUs Charged: Per Job'                                                   display,
                 'coalesce(sum(jf.local_charge*21.576)/sum(jf.\'.$job_count_formula.\'),0)' formula,
                 'avg_nu'                                                                 alias,
                 'NU'                                                                     unit,
                 1                                                                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'gateway_job_count'                                                                                                   name,
                 'Number of Jobs via Gateway'                                                                                          display,
                 'coalesce(sum(case when jf.person_id in (select person_id from modw.gatewayperson ) then jf.job_count else 0 end),0)' formula,
                 'gateway_job_count'                                                                                                   alias,
                 'Number of Jobs'                                                                                                      unit,
                 0                                                                                                                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'avg_su'                                                          name,
                 'XD SUs Charged: Per Job'                                         display,
                 'coalesce(sum(jf.local_charge)/sum(jf.\'.$job_count_formula.\'),0)' formula,
                 'avg_su'                                                          alias,
                 'XD SU'                                                           unit,
                 1                                                                 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'total_nu'                                name,
                 'NUs Charged: Total'                      display,
                 'coalesce(sum(jf.local_charge)*21.576,0)' formula,
                 'total_nu'                                alias,
                 'NU'                                      unit,
                 0                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Job Statistics: XSEDE

-- // Performance Statistics
INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_3d_cwa'                        name,
                 'IONET:MPI-Tile-IO - 3D Col Write' display,
                 'avg(jf.io_3d_cwa)'                formula,
                 'io_3d_cwa'                        alias,
                 'IONET:MPI-Tile-IO - 3D Col Write' unit,
                 4                                  decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'teps_graph500'         name,
                 'Net:Graph500 - TEPS'   display,
                 'avg(jf.teps_graph500)' formula,
                 'teps_graph500'         alias,
                 'Net:Graph500 - TEPS'   unit,
                 4                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpuio_walltime_mwchem'         name,
                 'CPUIO:NWCHEM - Performance'    display,
                 'avg(jf.cpuio_walltime_mwchem)' formula,
                 'cpuio_walltime_mwchem'         alias,
                 'CPUIO:NWCHEM - Performance'    unit,
                 4                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_mpi_io_cwa'            name,
                 'IO:IOR - MPIIO Col Write' display,
                 'avg(jf.io_mpi_io_cwa)'    formula,
                 'io_mpi_io_cwa'            alias,
                 'IO:IOR - MPIIO Col Write' unit,
                 4                          decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'weight'                name,
                 'Number of Data Points' display,
                 '1'                     formula,
                 'weight'                alias,
                 'Number of Data Points' unit,
                 0                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'net_mpi_ra_hpcc'              name,
                 'Net:HPCC - MPI Random Access' display,
                 'avg(jf.net_mpi_ra_hpcc)'      formula,
                 'net_mpi_ra_hpcc'              alias,
                 'Net:HPCC - MPI Random Access' unit,
                 4                              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'memory'                 name,
                 'Avg Memory Performance' display,
                 'avg(jf.memory)'         formula,
                 'memory'                 alias,
                 'Avg Memory Performance' unit,
                 4                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io'                 name,
                 'Avg IO Performance' display,
                 'avg(jf.io)'         formula,
                 'io'                 alias,
                 'Avg IO Performance' unit,
                 4                    decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_sp'         name,
                 'CPUNET:NPB - SP'   display,
                 'avg(jf.cpunet_sp)' formula,
                 'cpunet_sp'         alias,
                 'CPUNET:NPB - SP'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_3d_cra'                       name,
                 'IONET:MPI-Tile-IO - 3D Col Read' display,
                 'avg(jf.io_3d_cra)'               formula,
                 'io_3d_cra'                       alias,
                 'IONET:MPI-Tile-IO - 3D Col Read' unit,
                 4                                 decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_mpi_io_cra'           name,
                 'IO:IOR - MPIIO Col Read' display,
                 'avg(jf.io_mpi_io_cra)'   formula,
                 'io_mpi_io_cra'           alias,
                 'IO:IOR - MPIIO Col Read' unit,
                 4                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'fpu_linpack_hpcc'         name,
                 'CPUNET:HPCC - LINPACK'    display,
                 'avg(jf.fpu_linpack_hpcc)' formula,
                 'fpu_linpack_hpcc'         alias,
                 'CPUNET:HPCC - LINPACK'    unit,
                 4                          decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_cg'         name,
                 'CPUNET:NPB - CG'   display,
                 'avg(jf.cpunet_cg)' formula,
                 'cpunet_cg'         alias,
                 'CPUNET:NPB - CG'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'net_ptrans_hpcc'         name,
                 'Net:HPCC - PTRANS'       display,
                 'avg(jf.net_ptrans_hpcc)' formula,
                 'net_ptrans_hpcc'         alias,
                 'Net:HPCC - PTRANS'       unit,
                 4                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_mg'         name,
                 'CPUNET:NPB - MG'   display,
                 'avg(jf.cpunet_mg)' formula,
                 'cpunet_mg'         alias,
                 'CPUNET:NPB - MG'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_mpi_io_iwa'            name,
                 'IO:IOR - MPIIO Ind Write' display,
                 'avg(jf.io_mpi_io_iwa)'    formula,
                 'io_mpi_io_iwa'            alias,
                 'IO:IOR - MPIIO Ind Write' unit,
                 4                          decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpu'                 name,
                 'Avg CPU Performance' display,
                 'avg(jf.cpu)'         formula,
                 'cpu'                 alias,
                 'Avg CPU Performance' unit,
                 4                     decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_lu'         name,
                 'CPUNET:NPB - LU'   display,
                 'avg(jf.cpunet_lu)' formula,
                 'cpunet_lu'         alias,
                 'CPUNET:NPB - LU'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'overall'                name,
                 'Avg System Performance' display,
                 'avg(jf.overall)'        formula,
                 'overall'                alias,
                 'Avg System Performance' unit,
                 4                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'mem_bandwidth_hpcc'         name,
                 'Mem:HPCC - Bandwidth'       display,
                 'avg(jf.mem_bandwidth_hpcc)' formula,
                 'mem_bandwidth_hpcc'         alias,
                 'Mem:HPCC - Bandwidth'       unit,
                 4                            decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'io_mpi_io_ira'           name,
                 'IO:IOR - MPIIO Ind Read' display,
                 'avg(jf.io_mpi_io_ira)'   formula,
                 'io_mpi_io_ira'           alias,
                 'IO:IOR - MPIIO Ind Read' unit,
                 4                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'fpu_dgemm_hpcc'         name,
                 'CPUNET:HPCC - DGEMM'    display,
                 'avg(jf.fpu_dgemm_hpcc)' formula,
                 'fpu_dgemm_hpcc'         alias,
                 'CPUNET:HPCC - DGEMM'    unit,
                 4                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpu_walltime_graph500'         name,
                 'CPU:Graph500 - Performance'    display,
                 'avg(jf.cpu_walltime_graph500)' formula,
                 'cpu_walltime_graph500'         alias,
                 'CPU:Graph500 - Performance'    unit,
                 4                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_bt'         name,
                 'CPUNET:NPB - BT'   display,
                 'avg(jf.cpunet_bt)' formula,
                 'cpunet_bt'         alias,
                 'CPUNET:NPB - BT'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpunet_ft'         name,
                 'CPUNET:NPB - FT'   display,
                 'avg(jf.cpunet_ft)' formula,
                 'cpunet_ft'         alias,
                 'CPUNET:NPB - FT'   unit,
                 4                   decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'fpu_fftw_hpcc'         name,
                 'CPUNET:HPCC - FFTW'    display,
                 'avg(jf.fpu_fftw_hpcc)' formula,
                 'fpu_fftw_hpcc'         alias,
                 'CPUNET:HPCC - FFTW'    unit,
                 4                       decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'network'                 name,
                 'Avg Network Performance' display,
                 'avg(jf.network)'         formula,
                 'network'                 alias,
                 'Avg Network Performance' unit,
                 4                         decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'cpu_inv_mean_noise'            name,
                 'CPU:OSJitter - Inv Mean Noise' display,
                 'avg(jf.cpu_inv_mean_noise)'    formula,
                 'cpu_inv_mean_noise'            alias,
                 'CPU:OSJitter - Inv Mean Noise' unit,
                 4                               decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Performance Statistics

-- // Proposal Statistics

INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'proposal_count'                              name,
                 'Number of Proposals'                         display,
                 'coalesce(count(distinct(jf.proposal_id)),0)' formula,
                 'proposal_count'                              alias,
                 'Number of Proposals'                         unit,
                 0                                             decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'project_count'                                name,
                 'Number of Projects'                           display,
                 'coalesce(count(distinct(jf.proposal_num)),0)' formula,
                 'project_count'                                alias,
                 'Number of Projects'                           unit,
                 0                                              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Proposal Statistics

-- // Resource Allocation Statistics
INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'requested_xd_su'                                        name,
                 'XD SUs Requested'                                       display,
                 'coalesce(sum(jf.requested * {$this->xd_su_cf_sql}), 0)' formula,
                 'requested_xd_su'                                        alias,
                 'XD SU'                                                  unit,
                 0                                                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'weight' name,
                 'Weight' display,
                 '1'      formula,
                 'weight' alias,
                 'Weight' unit,
                 0        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'available_native_su'            name,
                 'CPU Core Hours Available'       display,
                 'coalesce(sum(jf.available), 0)' formula,
                 'available_native_su'            alias,
                 'CPU Core Hours'                 unit,
                 0                                decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'awarded_native_su'            name,
                 'CPU Core Hours Awarded'       display,
                 'coalesce(sum(jf.awarded), 0)' formula,
                 'awarded_native_su'            alias,
                 'CPU Core Hours'               unit,
                 0                              decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'awarded_xd_su'                                        name,
                 'XD SUs Awarded'                                       display,
                 'coalesce(sum(jf.awarded * {$this->xd_su_cf_sql}), 0)' formula,
                 'awarded_xd_su'                                        alias,
                 'XD SU'                                                unit,
                 0                                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'recommended_native_su'            name,
                 'CPU Core Hours Recommended'       display,
                 'coalesce(sum(jf.recommended), 0)' formula,
                 'recommended_native_su'            alias,
                 'CPU Core Hours'                   unit,
                 0                                  decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'requested_native_su'            name,
                 'CPU Core Hours Requested'       display,
                 'coalesce(sum(jf.requested), 0)' formula,
                 'requested_native_su'            alias,
                 'CPU Core Hours'                 unit,
                 0                                decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'requested_nu'                                                                         name,
                 'NUs Requested'                                                                        display,
                 'coalesce(sum(jf.requested * {$this->xd_su_cf_sql}) * {$this->xd_su_to_nu_string}, 0)' formula,
                 'requested_nu'                                                                         alias,
                 'NU'                                                                                   unit,
                 0                                                                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'awarded_nu'                                                                         name,
                 'NUs Awarded'                                                                        display,
                 'coalesce(sum(jf.awarded * {$this->xd_su_cf_sql}) * {$this->xd_su_to_nu_string}, 0)' formula,
                 'awarded_nu'                                                                         alias,
                 'NU'                                                                                 unit,
                 0                                                                                    decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'available_nu'                                                                         name,
                 'NUs Available'                                                                        display,
                 'coalesce(sum(jf.available * {$this->xd_su_cf_sql}) * {$this->xd_su_to_nu_string}, 0)' formula,
                 'available_nu'                                                                         alias,
                 'NU'                                                                                   unit,
                 0                                                                                      decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'recommended_nu'                                                                         name,
                 'NUs Recommended'                                                                        display,
                 'coalesce(sum(jf.recommended * {$this->xd_su_cf_sql}) * {$this->xd_su_to_nu_string}, 0)' formula,
                 'recommended_nu'                                                                         alias,
                 'NU'                                                                                     unit,
                 0                                                                                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'recommended_xd_su'                                        name,
                 'XD SUs Recommended'                                       display,
                 'coalesce(sum(jf.recommended * {$this->xd_su_cf_sql}), 0)' formula,
                 'recommended_xd_su'                                        alias,
                 'XD SU'                                                    unit,
                 0                                                          decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;


INSERT INTO moddb.statistics (module_id, NAME, display, formula, alias, unit, decimals)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'available_xd_su'                                        name,
                 'XD SUs Available'                                       display,
                 'coalesce(sum(jf.available * {$this->xd_su_cf_sql}), 0)' formula,
                 'available_xd_su'                                        alias,
                 'XD SU'                                                  unit,
                 0                                                        decimals
             FROM moddb.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN moddb.statistics cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.alias = inc.alias
               AND cur.unit = inc.unit
               AND cur.decimals = inc.decimals
    WHERE cur.statistic_id IS NULL;

-- // Resource Allocation Statistics
