INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_allocation_count' AS NAME, 
		'Number of Allocations: Active' AS display, 
		'active_allocation_count' AS alias, 
		'Number of Allocations' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.account_id))' AS formula, 
		'The total number of funded projects that used resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_institution_count' AS NAME, 
		'Number of Institutions: Active' AS display, 
		'active_institution_count' AS alias, 
		'Number of Institutions' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.person_organization_id))' AS formula, 
		'The total number of institutions that used resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_pi_count' AS NAME, 
		'Number of PIs: Active' AS display, 
		'active_pi_count' AS alias, 
		'Number of PIs' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.principalinvestigator_person_id))' AS formula, 
		'The total number of PIs that used resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_resource_count' AS NAME, 
		'Number of Resources: Active' AS display, 
		'active_resource_count' AS alias, 
		'Number of Resources' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.resource_id))' AS formula, 
		'The total number of active resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_person_count' AS NAME, 
		'Number of Users: Active' AS display, 
		'active_person_count' AS alias, 
		'Number of Users' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.person_id))' AS formula, 
		'The total number of users that used resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_cpu_hours' AS NAME, 
		'CPU Hours: Per Job' AS display, 
		'avg_cpu_hours' AS alias, 
		'CPU Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(jf.cpu_time/3600.0)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),0)' AS formula, 
		'The average CPU hours (number of CPU cores x wall time hours) per job.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_node_hours' AS NAME, 
		'Node Hours: Per Job' AS display, 
		'avg_node_hours' AS alias, 
		'Node Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(jf.node_time/3600.0)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),0)' AS formula, 
		'The average node hours (number of nodes x wall time hours) per job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_processors' AS NAME, 
		'Job Size: Per Job' AS display, 
		'avg_processors' AS alias, 
		'Core Count' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(ceil(sum(jf.processors*jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)' AS formula, 
		'The average job size per job.<br> <i>Job Size: </i>The number of processor cores used by a (parallel) job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_waitduration_hours' AS NAME, 
		'Wait Hours: Per Job' AS display, 
		'avg_waitduration_hours' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(jf.waitduration/3600.0)/sum(jf.started_job_count),0)' AS formula, 
		'The average time, in hours, a job waits before execution on the designated resource.<br/> <i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_wallduration_hours' AS NAME, 
		'Wall Hours: Per Job' AS display, 
		'avg_wallduration_hours' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(jf.wallduration/3600.0)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),0)' AS formula, 
		'The average time, in hours, a job takes to execute.<br/> <i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'expansion_factor' AS NAME, 
		'User Expansion Factor' AS display, 
		'expansion_factor' AS alias, 
		'User Expansion Factor' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.sum_weighted_expansion_factor)/sum(jf.sum_job_weights),0)' AS formula, 
		'Gauging job-turnaround time, it measures the ratio of wait time and the total time from submission to end of execution.<br/> <i>User Expansion Factor = ((wait duration + wall duration) / wall duration). </i>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'job_count' AS NAME, 
		'Number of Jobs Ended' AS display, 
		'job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.job_count),0)' AS formula, 
		'The total number of jobs that ended within the selected duration.<br/> <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_job_size_weighted_by_cpu_hours' AS NAME, 
		'Job Size: Weighted By CPU Hours' AS display, 
		'avg_job_size_weighted_by_cpu_hours' AS alias, 
		'Core Count' AS unit, 
		'1'+0 AS decimals, 
		'
                COALESCE(
                    SUM(jf.processors * jf.cpu_time) / SUM(jf.cpu_time),
                    0
                )
            ' AS formula, 
		'The average job size weighted by CPU Hours. Defined as <br><i>Average Job Size Weighted By CPU Hours: </i> sum(i = 0 to n){job i core count*job i cpu hours}/sum(i = 0 to n){job i cpu hours}' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'max_processors' AS NAME, 
		'Job Size: Max' AS display, 
		'max_processors' AS alias, 
		'Core Count' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(ceil(max(jf.processors)),0)' AS formula, 
		'The maximum size job in number of cores.<br/> <i>Job Size: </i>The total number of processor cores used by a (parallel) job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'min_processors' AS NAME, 
		'Job Size: Min' AS display, 
		'min_processors' AS alias, 
		'Core Count' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(ceil(min(case when jf.processors = 0 then null else jf.processors end)),0)' AS formula, 
		'The minimum size job in number of cores.<br/> <i>Job Size: </i>The total number of processor cores used by a (parallel) job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'node_utilization' AS NAME, 
		'Node Utilization' AS display, 
		'node_utilization' AS alias, 
		'%' AS unit, 
		'2'+0 AS decimals, 
		'\n 100.0 * (\n COALESCE(\n SUM(jf.node_time / 3600.0)\n /\n (\n SELECT\n SUM(ra.percent * inner_days.hours * rs.q_nodes / 100.0)\n FROM\n modw.resourcespecs rs,\n modw.resource_allocated ra,\n modw.days inner_days\n WHERE\n inner_days.day_middle_ts BETWEEN ra.start_date_ts AND COALESCE(ra.end_date_ts, 2147483647)\n AND inner_days.day_middle_ts BETWEEN rs.start_date_ts AND COALESCE(rs.end_date_ts, 2147483647)\n AND inner_days.day_middle_ts BETWEEN $date_table_start_ts AND $date_table_end_ts\n AND ra.resource_id = rs.resource_id\n AND FIND_IN_SET(\n rs.resource_id,\n GROUP_CONCAT(DISTINCT jf.resource_id)\n ) <> 0\n ),\n 0\n )\n )\n ' AS formula, 
		'The percentage of resource nodes utilized by jobs.<br/><i> Node Utilization:</i> the ratio of the total node hours consumed by jobs over a given time period divided by the total node hours that the system could have potentially provided during that period. It does not include non- jobs.<br/>This value is only accurate if node sharing is not allowed' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'normalized_avg_processors' AS NAME, 
		'Job Size: Normalized' AS display, 
		'normalized_avg_processors' AS alias, 
		'% of Total Cores' AS unit, 
		'1'+0 AS decimals, 
		'100.0*coalesce(ceil(sum(jf.processors*jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))/(select sum(rrf.processors) from modw.resourcespecs rrf where find_in_set(rrf.resource_id,group_concat(distinct jf.resource_id)) <> 0 and \' . $query_instance->getAggregationUnit()->getUnitName() . \'_end_ts >= rrf.start_date_ts and (rrf.end_date_ts is null or \' . $query_instance->getAggregationUnit()->getUnitName() . \'_end_ts <= rrf.end_date_ts)),0)' AS formula, 
		'The percentage average size job over total machine cores.<br> <i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'burn_rate' AS NAME, 
		'Allocation Burn Rate' AS display, 
		'burn_rate' AS alias, 
		'%' AS unit, 
		'2'+0 AS decimals, 
		'100.00*coalesce((sum(jf.local_charge)/" . ($query_instance != NULL ? $query_instance->getDurationFormula() : 1) . ")\n\t\t\t\t\t\t\t/\n\t\t\t\t\t\t\t(select sum(alc.base_allocation*"coalesce((select conversion_factor \n\t\t\t\t from modw.allocationadjustment aladj\n\t\t\t\t where aladj.allocation_resource_id = 1546\n\t\t\t\t and aladj.site_resource_id = alc.resource_id\n\t\t\t\t and aladj.start_date <= alc.initial_start_date and (aladj.end_date is null or alc.initial_start_date <= aladj.end_date)\n\t\t\t\t limit 1\n\t\t\t\t ), 1.0)"/((unix_timestamp(alc.end_date) - unix_timestamp(alc.initial_start_date))/3600.0)) from modw.allocation alc where find_in_set(alc.id,group_concat(distinct jf.allocation_id)) <> 0 ),0)\n\t\t\t\t\t\t\t' AS formula, 
		'The percentage of allocation usage in the given duration.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'rate_of_usage' AS NAME, 
		'Allocation Usage Rate' AS display, 
		'rate_of_usage' AS alias, 
		'XD SU/Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(jf.local_charge)/" . ($query_instance != NULL ? $query_instance->getDurationFormula() : 1) . ",0)' AS formula, 
		'The rate of allocation usage in XD SUs per hour.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'running_job_count' AS NAME, 
		'Number of Jobs Running' AS display, 
		'running_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.running_job_count),0)' AS formula, 
		'The total number of running jobs.<br/> <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_cpu_hours' AS NAME, 
		'Std Dev: CPU Hours: Per Job' AS display, 
		'sem_avg_cpu_hours' AS alias, 
		'CPU Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(jf.sum_cpu_time_squared)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))-pow(sum(jf.cpu_time)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2))/sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)/3600.0' AS formula, 
		'The standard error of the average CPU hours by each job.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_node_hours' AS NAME, 
		'Std Dev: Node Hours: Per Job' AS display, 
		'sem_avg_node_hours' AS alias, 
		'Node Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(jf.sum_node_time_squared)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))-pow(sum(jf.node_time)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2))/sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)/3600.0' AS formula, 
		'The standard error of the average node hours by each job.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_processors' AS NAME, 
		'Std Dev: Job Size: Per Job' AS display, 
		'sem_avg_processors' AS alias, 
		'Core Count' AS unit, 
		'2'+0 AS decimals, 
		'coalesce( sqrt( (sum(pow(jf.processors,2)*jf.job_count)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')) - pow(sum(jf.processors*jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2) ) /sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')) ,0)' AS formula, 
		'The standard error of the average size job in number of cores. <br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_waitduration_hours' AS NAME, 
		'Std Dev: Wait Hours: Per Job' AS display, 
		'sem_avg_waitduration_hours' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(coalesce(jf.sum_waitduration_squared,0))/sum(jf.started_job_count))-pow(sum(coalesce(jf.waitduration,0))/sum(jf.started_job_count),2))/sqrt(sum(jf.started_job_count)),0)/3600.0' AS formula, 
		'The standard error of the average time, in hours, an job had to wait until it began to execute.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_wallduration_hours' AS NAME, 
		'Std Dev: Wall Hours: Per Job' AS display, 
		'sem_avg_wallduration_hours' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(jf.sum_wallduration_squared)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))-pow(sum(jf.wallduration)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2))/sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)/3600.0' AS formula, 
		'The standard error of the average time each job took to execute.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'started_job_count' AS NAME, 
		'Number of Jobs Started' AS display, 
		'started_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.started_job_count),0)' AS formula, 
		'The total number of jobs that started executing within the selected duration.<br/> <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'submitted_job_count' AS NAME, 
		'Number of Jobs Submitted' AS display, 
		'submitted_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.submitted_job_count),0)' AS formula, 
		'' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_cpu_hours' AS NAME, 
		'CPU Hours: Total' AS display, 
		'total_cpu_hours' AS alias, 
		'CPU Hour' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.cpu_time/3600.0),0)' AS formula, 
		'The total CPU hours (number of CPU cores x wall time hours) used by jobs.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_node_hours' AS NAME, 
		'Node Hours: Total' AS display, 
		'total_node_hours' AS alias, 
		'Node Hour' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.node_time/3600.0),0)' AS formula, 
		'The total node hours (number of nodes x wall time hours) used by jobs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_waitduration_hours' AS NAME, 
		'Wait Hours: Total' AS display, 
		'total_waitduration_hours' AS alias, 
		'Hour' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.waitduration/3600.0),0)' AS formula, 
		'The total time, in hours, jobs waited before execution on their designated resource.<br/> <i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_wallduration_hours' AS NAME, 
		'Wall Hours: Total' AS display, 
		'total_wallduration_hours' AS alias, 
		'Hour' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.wallduration/3600.0),0)' AS formula, 
		'The total time, in hours, jobs took to execute.<br/> <i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'utilization' AS NAME, 
		'Utilization' AS display, 
		'utilization' AS alias, 
		'%' AS unit, 
		'2'+0 AS decimals, 
		'\n 100.0 * (\n COALESCE(\n SUM(jf.cpu_time / 3600.0)\n /\n (\n SELECT SUM( ra.percent * inner_days.hours * rs.processors / 100.0 )\n FROM modw.resourcespecs rs,\n modw.resource_allocated ra,\n modw.days inner_days\n WHERE\n inner_days.day_middle_ts BETWEEN ra.start_date_ts AND coalesce(ra.end_date_ts, 2147483647) AND\n inner_days.day_middle_ts BETWEEN rs.start_date_ts AND coalesce(rs.end_date_ts, 2147483647) AND\n inner_days.day_middle_ts BETWEEN $date_table_start_ts AND $date_table_end_ts AND\n ra.resource_id = rs.resource_id\n AND FIND_IN_SET(\n rs.resource_id,\n GROUP_CONCAT(DISTINCT jf.resource_id)\n ) <> 0\n ),\n 0\n )\n )\n ' AS formula, 
		'The percentage of the obligation of a resource that has been utilized by jobs.<br/><i> Utilization:</i> The ratio of the total CPU hours consumed by jobs over a given time period divided by the total CPU hours that the system is contractually required to provide to during that period. It does not include non- jobs.<br/>It is worth noting that this value is a rough estimate in certain cases where the resource providers don\'t provide accurate records of their system specifications, over time.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'closed_account_count' AS NAME, 
		'Number of User Accounts: Closed' AS display, 
		'closed_account_count' AS alias, 
		'Number of User Accounts' AS unit, 
		'0'+0 AS decimals, 
		'union_string_count(jf.person_ids_closed, 1, 100000)' AS formula, 
		'The total number of users that had expired accounts.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'new_account_count' AS NAME, 
		'Number of User Accounts: Created' AS display, 
		'new_account_count' AS alias, 
		'Number of User Accounts' AS unit, 
		'0'+0 AS decimals, 
		'union_string_count(jf.person_ids_new, 1, 100000)' AS formula, 
		'The total number of users that opened new accounts.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'open_account_count' AS NAME, 
		'Number of User Accounts: Open' AS display, 
		'open_account_count' AS alias, 
		'Number of User Accounts' AS unit, 
		'0'+0 AS decimals, 
		'union_string_count(jf.person_ids_open, 1, 100000)' AS formula, 
		'The total number of users that had open accounts.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'weight' AS NAME, 
		'Weight' AS display, 
		'weight' AS alias, 
		'Weight' AS unit, 
		'0'+0 AS decimals, 
		'1' AS formula, 
		'' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'active_allocation_count' AS NAME, 
		'Number of Allocations: Active' AS display, 
		'active_allocation_count' AS alias, 
		'Number of Allocations' AS unit, 
		'0'+0 AS decimals, 
		'count(distinct(jf.account_id))' AS formula, 
		'The number of allocations that were valid.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'allocated_nu' AS NAME, 
		'NUs: Allocated' AS display, 
		'allocated_nu' AS alias, 
		'NU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.base_fraction*jf.conversion_factor*21.576),0)' AS formula, 
		'The total allocated amount in NUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'allocated_raw_su' AS NAME, 
		'CPU Core Hours: Allocated' AS display, 
		'allocated_raw_su' AS alias, 
		'CPU Core Hours' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.base_fraction),0)' AS formula, 
		'The total allocated amount in CPU core hours on the resource the allocation was made on.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'allocated_su' AS NAME, 
		'XD SUs: Allocated' AS display, 
		'allocated_su' AS alias, 
		'XD SU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.base_fraction*jf.conversion_factor),0)' AS formula, 
		'The total allocated amount in XD SUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_used_su' AS NAME, 
		'XD SUs: Per Job' AS display, 
		'avg_used_su' AS alias, 
		'XD SU' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(used)/sum(running_job_count),0)' AS formula, 
		'' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'burn_rate' AS NAME, 
		'Allocation Burn Rate' AS display, 
		'burn_rate' AS alias, 
		'%' AS unit, 
		'2'+0 AS decimals, 
		'100.00*coalesce(sum(jf.used)/sum(jf.base_fraction*jf.conversion_factor),0)' AS formula, 
		'The percentage of allocation usage in the given duration.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'rate_of_usage' AS NAME, 
		'Allocation Usage Rate' AS display, 
		'rate_of_usage' AS alias, 
		'XD SU/Hour' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sum(used)/" . ($query_instance != NULL ? $query_instance->getDurationFormula() : 1) . ",0)' AS formula, 
		'The rate of allocation usage in XD SUs per hour.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'used_su' AS NAME, 
		'XD SUs: Used' AS display, 
		'used_su' AS alias, 
		'XD SU' AS unit, 
		'2'+0 AS decimals, 
		'sum(used)' AS formula, 
		'The total amount of XD SUs used by jobs.<br/>\n\t\t<i>XD SU: </i>1 XSEDE SU is defined as one CPU-hour on a Phase-1 DTF cluster.<br/>\t\n\t\t<i>SU - Service Units: </i>Computational resources on the XSEDE are allocated and charged in service units (SUs). SUs are defined locally on each system, with conversion factors among systems based on HPL benchmark results (see the XSEDE SU Conversion Calculator: https://www.xsede.org/su-converter).<br/>\n\n\t\tCurrent TeraGrid supercomputers have complex multi-core and memory hierarchies. Each resource has a specific configuration that determines the number (N) of cores that can be dedicated to a job without slowing the code (and other user and system codes). Each resource defines for its system the minimum number of SUs charged for a job running in the default batch queue, calculated as wallclock runtime multiplied by N. Minimum charges may apply.<br/>\n\n\t\tNote: The actual charge will depend on the specific requirements of the job (e.g., the mapping of the cores across the machine, or the priority you wish to obtain). Consult each system\'s user guide for details. If you have questions, contact help@teragrid.org .<br/>\n\t\t\n\t\tNote 2: The SUs show here have been normalized against the XSEDE Roaming service. Therefore they are comparable across resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'weight' AS NAME, 
		'Number of Allocations' AS display, 
		'weight' AS alias, 
		'Number of Allocations' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(count( distinct allocation_id),0)' AS formula, 
		'The number of allocations.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'award_amount' AS NAME, 
		'Award Amount' AS display, 
		'award_amount' AS alias, 
		'$' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.amount_dollar),0)' AS formula, 
		'The total amount of awards in US Dollars within the selected duration. This value is self reported by the grant applicant.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'award_count' AS NAME, 
		'Number of Awards' AS display, 
		'award_count' AS alias, 
		'Number of Awards' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(count(distinct(jf.proposal_num)),0)' AS formula, 
		'The total number of awards within the selected duration.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_nu' AS NAME, 
		'NUs Charged: Per Job' AS display, 
		'avg_nu' AS alias, 
		'NU' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.local_charge*21.576)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),0)' AS formula, 
		'The average amount of NUs charged per job.<br/> <i>NU - Normalized Units: </i>Roaming allocations are awarded in XSEDE Service Units (SUs). 1 XSEDE SU is defined as one CPU-hour on a Phase-1 DTF cluster. For usage on a resource that is charged to a Roaming allocation, a normalization factor is applied. The normalization factor is based on the method historically used to calculate \'Normalized Units\' (Cray X-MP-equivalent SUs), which derives from a resource\'s performance on the HPL benchmark.<br/>Specifically, 1 Phase-1 DTF SU = 21.576 NUs, and the XD SU conversion factor for a resource is calculated by taking its NU conversion factor and dividing it by 21.576. The standard formula for calculating a resource\'s NU conversion factor is: (Rmax * 1000 / 191) / P where Rmax is the resource\'s Rmax result on the HPL benchmark in Gflops and P is the number of processors used in the benchmark. In the absence of an HPL benchmark run, a conversion factor can be agreed upon, based on that of an architecturally similar platform and scaled according to processor performance differences.<br/>Conversion to Roaming SUs is handled by the XSEDE central accounting system, and RPs are only required to report usage in local SUs for all allocations.<br/>Defining an SU charge for specialized compute resources (such as visualization hardware) or non-compute resources (such as storage) is possible, but there is no XSEDE-wide policy for doing so.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_su' AS NAME, 
		'XD SUs Charged: Per Job' AS display, 
		'avg_su' AS alias, 
		'XD SU' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.local_charge)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),0)' AS formula, 
		'The average amount of XD SUs charged per job.<br/> <i>XD SU: </i>1 XSEDE SU is defined as one CPU-hour on a Phase-1 DTF cluster.<br/> <i>SU - Service Units: </i>Computational resources on the XSEDE are allocated and charged in service units (SUs). SUs are defined locally on each system, with conversion factors among systems based on HPL benchmark results (see the XSEDE SU Conversion Calculator: https://www.xsede.org/su-converter).<br/> Current TeraGrid supercomputers have complex multi-core and memory hierarchies. Each resource has a specific configuration that determines the number (N) of cores that can be dedicated to a job without slowing the code (and other user and system codes). Each resource defines for its system the minimum number of SUs charged for a job running in the default batch queue, calculated as wallclock runtime multiplied by N. Minimum charges may apply.<br/> Note: The actual charge will depend on the specific requirements of the job (e.g., the mapping of the cores across the machine, or the priority you wish to obtain). Consult each system\'s user guide for details. If you have questions, contact help@teragrid.org .<br/> Note 2: The SUs show here have been normalized against the XSEDE Roaming service. Therefore they are comparable across resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'gateway_job_count' AS NAME, 
		'Number of Jobs via Gateway' AS display, 
		'gateway_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(case when jf.person_id in (select person_id from modw.gatewayperson ) then jf.job_count else 0 end),0)' AS formula, 
		'The total number of jobs submitted through gateways (e.g., via a community user account) that ended within the selected duration.<br/> <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_job_size_weighted_by_xd_su' AS NAME, 
		'Job Size: Weighted By XD SUs' AS display, 
		'avg_job_size_weighted_by_xd_su' AS alias, 
		'Core Count' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.processors*jf.local_charge)/sum(jf.local_charge),0)' AS formula, 
		'The average job size weighted by charge in XD SUs. Defined as <br> <i>Average Job Size Weighted By XD SUs: </i> sum(i = 0 to n){job i core count*job i charge in xd sus}/sum(i = 0 to n){job i charge in xd sus}' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_nu' AS NAME, 
		'Std Dev: NUs Charged: Per Job' AS display, 
		'sem_avg_nu' AS alias, 
		'NU' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(jf.sum_local_charge_squared)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))-pow(sum(jf.local_charge)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2))/sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)*21.576' AS formula, 
		'The standard error of the average NUs charged by each job.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'sem_avg_su' AS NAME, 
		'Std Dev: XD SUs Charged: Per Job' AS display, 
		'sem_avg_su' AS alias, 
		'XD SU' AS unit, 
		'2'+0 AS decimals, 
		'coalesce(sqrt((sum(jf.sum_local_charge_squared)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'))-pow(sum(jf.local_charge)/sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \'),2))/sqrt(sum(jf.\' . $query_instance->getQueryType() == \'aggregate\' ? \'job_count\' : \'running_job_count\' . \')),0)' AS formula, 
		'The standard error of the average XD SUs charged by each job.<br/> <i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_nu' AS NAME, 
		'NUs Charged: Total' AS display, 
		'total_nu' AS alias, 
		'NU' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.local_charge)*21.576,0)' AS formula, 
		'The total amount of NUs charged by jobs.<br/> <i>NU - Normalized Units: </i>Roaming allocations are awarded in XSEDE Service Units (SUs). 1 XSEDE SU is defined as one CPU-hour on a Phase-1 DTF cluster. For usage on a resource that is charged to a Roaming allocation, a normalization factor is applied. The normalization factor is based on the method historically used to calculate \'Normalized Units\' (Cray X-MP-equivalent SUs), which derives from a resource\'s performance on the HPL benchmark.<br/>Specifically, 1 Phase-1 DTF SU = 21.576 NUs, and the XD SU conversion factor for a resource is calculated by taking its NU conversion factor and dividing it by 21.576. The standard formula for calculating a resource\'s NU conversion factor is: (Rmax * 1000 / 191) / P where Rmax is the resource\'s Rmax result on the HPL benchmark in Gflops and P is the number of processors used in the benchmark. In the absence of an HPL benchmark run, a conversion factor can be agreed upon, based on that of an architecturally similar platform and scaled according to processor performance differences.<br/>Conversion to Roaming SUs is handled by the XSEDE central accounting system, and RPs are only required to report usage in local SUs for all allocations.<br/>Defining an SU charge for specialized compute resources (such as visualization hardware) or non-compute resources (such as storage) is possible, but there is no XSEDE-wide policy for doing so.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'total_su' AS NAME, 
		'XD SUs Charged: Total' AS display, 
		'total_su' AS alias, 
		'XD SU' AS unit, 
		'1'+0 AS decimals, 
		'coalesce(sum(jf.local_charge),0)' AS formula, 
		'The total amount of XD SUs charged by jobs.<br/> <i>XD SU: </i>1 XSEDE SU is defined as one CPU-hour on a Phase-1 DTF cluster.<br/> <i>SU - Service Units: </i>Computational resources on the XSEDE are allocated and charged in service units (SUs). SUs are defined locally on each system, with conversion factors among systems based on HPL benchmark results (see the XSEDE SU Conversion Calculator: https://www.xsede.org/su-converter).<br/> Current TeraGrid supercomputers have complex multi-core and memory hierarchies. Each resource has a specific configuration that determines the number (N) of cores that can be dedicated to a job without slowing the code (and other user and system codes). Each resource defines for its system the minimum number of SUs charged for a job running in the default batch queue, calculated as wallclock runtime multiplied by N. Minimum charges may apply.<br/> Note: The actual charge will depend on the specific requirements of the job (e.g., the mapping of the cores across the machine, or the priority you wish to obtain). Consult each system\'s user guide for details. If you have questions, contact help@teragrid.org .<br/> Note 2: The SUs show here have been normalized against the XSEDE Roaming service. Therefore they are comparable across resources.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu' AS NAME, 
		'Avg CPU Performance' AS display, 
		'cpu' AS alias, 
		'Avg CPU Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpu)' AS formula, 
		'The performance of CPU. Calculated as the average of: <ul> <li>Graph500 Walltime,</li> <li>HPCC - DGEMM: Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance,</li> <li>HPCC - FFTW: The performance of FFTW HPCC,</li> <li>HPCC - LINPACK: The performance of LINPACK HPCC,</li> <li> NPB - BT: Block Tridiagonal (BT) Floating-Point Performance NPB,</li> <li>NPB - CG: Conjugate Gradient (CG) Floating-Point Performance,</li><li>NPB - FT: Fast Fourier Transform (FT) Floating-Point Performance,</li><li>NPB - LU: LU Solver (LU) Floating-Point Performance,</li><li>NPB - MG: Multi Grid (MG) Floating-Point Performance,</li><li>NPB - SP: Scalar Pentadiagonal (SP) Floating-Point Performance,</li><li>NWCHEM Walltime,</li><li>OSJitter - Inv Mean Noise: The performance of OSJitter Mean Noise (All Cores in Use)</li></ul>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'fpu_fftw_hpcc' AS NAME, 
		'CPUNET:HPCC - FFTW' AS display, 
		'fpu_fftw_hpcc' AS alias, 
		'CPUNET:HPCC - FFTW' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.fpu_fftw_hpcc)' AS formula, 
		'The performance of FFTW HPCC, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'fpu_dgemm_hpcc' AS NAME, 
		'CPUNET:HPCC - DGEMM' AS display, 
		'fpu_dgemm_hpcc' AS alias, 
		'CPUNET:HPCC - DGEMM' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.fpu_dgemm_hpcc)' AS formula, 
		'Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_3d_cra' AS NAME, 
		'IONET:MPI-Tile-IO - 3D Col Read' AS display, 
		'io_3d_cra' AS alias, 
		'IONET:MPI-Tile-IO - 3D Col Read' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_3d_cra)' AS formula, 
		'The performance of MPI-Tile-IO - 3D Array Collective Read Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_3d_cwa' AS NAME, 
		'IONET:MPI-Tile-IO - 3D Col Write' AS display, 
		'io_3d_cwa' AS alias, 
		'IONET:MPI-Tile-IO - 3D Col Write' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_3d_cwa)' AS formula, 
		'The performance of MPI-Tile-IO - 3D Array Collective Write Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io' AS NAME, 
		'Avg IO Performance' AS display, 
		'io' AS alias, 
		'Avg IO Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io)' AS formula, 
		'The performance of IO. Calculated as the average of: <ul> <li>IOR - MPIIO Collective N-to-1 Read Aggregate Throughput,</li> <li>IOR - MPIIO Collective N-to-1 Write Aggregate Throughput,</li> <li>IOR - MPIIO Independent N-to-1 Read Aggregate Throughput,</li> <li>IOR - MPIIO Independent N-to-1 Write Aggregate Throughput,</li> <li>MPI-Tile-IO - 3D Array Collective Read Aggregate Throughput,</li> <li>MPI-Tile-IO - 3D Array Collective Write Aggregate Throughput,</li> <li>NWCHEM Walltime</li> </ul>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu_inv_mean_noise' AS NAME, 
		'CPU:OSJitter - Inv Mean Noise' AS display, 
		'cpu_inv_mean_noise' AS alias, 
		'CPU:OSJitter - Inv Mean Noise' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpu_inv_mean_noise)' AS formula, 
		'The performance of OSJitter Mean Noise (All Cores in Use), normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'fpu_linpack_hpcc' AS NAME, 
		'CPUNET:HPCC - LINPACK' AS display, 
		'fpu_linpack_hpcc' AS alias, 
		'CPUNET:HPCC - LINPACK' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.fpu_linpack_hpcc)' AS formula, 
		'The performance of LINPACK HPCC, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_mpi_io_cra' AS NAME, 
		'IO:IOR - MPIIO Col Read' AS display, 
		'io_mpi_io_cra' AS alias, 
		'IO:IOR - MPIIO Col Read' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_mpi_io_cra)' AS formula, 
		'The performance of IOR - MPIIO Collective N-to-1 Read Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_mpi_io_cwa' AS NAME, 
		'IO:IOR - MPIIO Col Write' AS display, 
		'io_mpi_io_cwa' AS alias, 
		'IO:IOR - MPIIO Col Write' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_mpi_io_cwa)' AS formula, 
		'The performance of IOR - MPIIO Collective N-to-1 Write Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_mpi_io_ira' AS NAME, 
		'IO:IOR - MPIIO Ind Read' AS display, 
		'io_mpi_io_ira' AS alias, 
		'IO:IOR - MPIIO Ind Read' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_mpi_io_ira)' AS formula, 
		'The performance of IOR - MPIIO Independent N-to-1 Read Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'io_mpi_io_iwa' AS NAME, 
		'IO:IOR - MPIIO Ind Write' AS display, 
		'io_mpi_io_iwa' AS alias, 
		'IO:IOR - MPIIO Ind Write' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.io_mpi_io_iwa)' AS formula, 
		'The performance of IOR - MPIIO Independent N-to-1 Write Aggregate Throughput, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'net_mpi_ra_hpcc' AS NAME, 
		'Net:HPCC - MPI Random Access' AS display, 
		'net_mpi_ra_hpcc' AS alias, 
		'Net:HPCC - MPI Random Access' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.net_mpi_ra_hpcc)' AS formula, 
		'The performance of MPI Random Access HPCC, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'mem_bandwidth_hpcc' AS NAME, 
		'Mem:HPCC - Bandwidth' AS display, 
		'mem_bandwidth_hpcc' AS alias, 
		'Mem:HPCC - Bandwidth' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.mem_bandwidth_hpcc)' AS formula, 
		'Average memory bandwidth of HPCC, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'memory' AS NAME, 
		'Avg Memory Performance' AS display, 
		'memory' AS alias, 
		'Avg Memory Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.memory)' AS formula, 
		'The performance of Memory. Calculated as the average of: <ul> <li>Memory bandwidth of HPCC</li></ul>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_bt' AS NAME, 
		'CPUNET:NPB - BT' AS display, 
		'cpunet_bt' AS alias, 
		'CPUNET:NPB - BT' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_bt)' AS formula, 
		'Block Tridiagonal (BT) Floating-Point Performance NPB, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_cg' AS NAME, 
		'CPUNET:NPB - CG' AS display, 
		'cpunet_cg' AS alias, 
		'CPUNET:NPB - CG' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_cg)' AS formula, 
		'Conjugate Gradient (CG) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_ft' AS NAME, 
		'CPUNET:NPB - FT' AS display, 
		'cpunet_ft' AS alias, 
		'CPUNET:NPB - FT' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_ft)' AS formula, 
		'Fast Fourier Transform (FT) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_lu' AS NAME, 
		'CPUNET:NPB - LU' AS display, 
		'cpunet_lu' AS alias, 
		'CPUNET:NPB - LU' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_lu)' AS formula, 
		'LU Solver (LU) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_mg' AS NAME, 
		'CPUNET:NPB - MG' AS display, 
		'cpunet_mg' AS alias, 
		'CPUNET:NPB - MG' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_mg)' AS formula, 
		'Multi Grid (MG) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpunet_sp' AS NAME, 
		'CPUNET:NPB - SP' AS display, 
		'cpunet_sp' AS alias, 
		'CPUNET:NPB - SP' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpunet_sp)' AS formula, 
		'Scalar Pentadiagonal (SP) Floating-Point Performance, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'network' AS NAME, 
		'Avg Network Performance' AS display, 
		'network' AS alias, 
		'Avg Network Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.network)' AS formula, 
		'The performance of Network. Calculated as the average of: <ul> <li> NPB - BT: Block Tridiagonal (BT) Floating-Point Performance NPB,</li> <li>NPB - CG: Conjugate Gradient (CG) Floating-Point Performance,</li><li>NPB - FT: Fast Fourier Transform (FT) Floating-Point Performance,</li><li>NPB - LU: LU Solver (LU) Floating-Point Performance,</li><li>NPB - MG: Multi Grid (MG) Floating-Point Performance,</li><li>NPB - SP: Scalar Pentadiagonal (SP) Floating-Point Performance,</li><li>MPI-Tile-IO - 3D Array Collective Read Aggregate Throughput,</li> <li>MPI-Tile-IO - 3D Array Collective Write Aggregate Throughput,</li><li>HPCC - PTRANS: The performance of parallell matrix transpose HPCC,</li><li>HPCC - MPI Random Access: The performance of MPI Random Access HPCC,</li> <li>HPCC - LINPACK: The performance of LINPACK HPCC,</li><li>HPCC - FFTW: The performance of FFTW HPCC,</li><li>HPCC - DGEMM: Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance,</li><li>Graph500 - TEPS: The Network performance of Graph500 (Traversed Edges Per Second)</li></ul>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'overall' AS NAME, 
		'Avg System Performance' AS display, 
		'overall' AS alias, 
		'Avg System Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.overall)' AS formula, 
		'Avg System Performance. Calculated as the average of: <ul> <li>CPU,</li> <li>IO,</li> <li>Network,</li> <li>Memory</li> </ul>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'net_ptrans_hpcc' AS NAME, 
		'Net:HPCC - PTRANS' AS display, 
		'net_ptrans_hpcc' AS alias, 
		'Net:HPCC - PTRANS' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.net_ptrans_hpcc)' AS formula, 
		'The performance of parallell matrix transpose HPCC, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'teps_graph500' AS NAME, 
		'Net:Graph500 - TEPS' AS display, 
		'teps_graph500' AS alias, 
		'Net:Graph500 - TEPS' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.teps_graph500)' AS formula, 
		'The Network performance of Graph500 (Traversed Edges Per Second), normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu_walltime_graph500' AS NAME, 
		'CPU:Graph500 - Performance' AS display, 
		'cpu_walltime_graph500' AS alias, 
		'CPU:Graph500 - Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpu_walltime_graph500)' AS formula, 
		'The performance of Graph500, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpuio_walltime_mwchem' AS NAME, 
		'CPUIO:NWCHEM - Performance' AS display, 
		'cpuio_walltime_mwchem' AS alias, 
		'CPUIO:NWCHEM - Performance' AS unit, 
		'4'+0 AS decimals, 
		'avg(jf.cpuio_walltime_mwchem)' AS formula, 
		'The performance of NWCHEM, normalized relative to the performance of edge.ccr.buffalo.edu between 2013-01-01 and 2013-06-30.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'weight' AS NAME, 
		'Number of Data Points' AS display, 
		'weight' AS alias, 
		'Number of Data Points' AS unit, 
		'0'+0 AS decimals, 
		'1' AS formula, 
		'The number of data points.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'project_count' AS NAME, 
		'Number of Projects' AS display, 
		'project_count' AS alias, 
		'Number of Projects' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(count(distinct(jf.proposal_num)),0)' AS formula, 
		'The total number of projects within the selected duration.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Proposals')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'proposal_count' AS NAME, 
		'Number of Proposals' AS display, 
		'proposal_count' AS alias, 
		'Number of Proposals' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(count(distinct(jf.proposal_id)),0)' AS formula, 
		'The total number of proposals within the selected duration.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Proposals')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'project_count' AS NAME, 
		'Number of Projects' AS display, 
		'project_count' AS alias, 
		'Number of Projects' AS unit, 
		'0'+0 AS decimals, 
		'COALESCE(COUNT(DISTINCT jf.request_num), 0)' AS formula, 
		'The total number of projects within the selected" . " duration.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Requests')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'request_count' AS NAME, 
		'Number of Proposals' AS display, 
		'request_count' AS alias, 
		'Number of Proposals' AS unit, 
		'0'+0 AS decimals, 
		'COALESCE(COUNT(DISTINCT jf.action_id), 0)' AS formula, 
		'The total number of requests within the selected" . " duration.<br/>' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Requests')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'available_nu' AS NAME, 
		'NUs Available' AS display, 
		'available_nu' AS alias, 
		'NU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.available * $this->xd_su_cf_sql) * $this->xd_su_to_nu_string, 0)' AS formula, 
		'The total available amount in NUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'available_native_su' AS NAME, 
		'CPU Core Hours Available' AS display, 
		'available_native_su' AS alias, 
		'CPU Core Hours' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.available), 0)' AS formula, 
		'The total available amount in native SUs (CPU core hours).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'available_xd_su' AS NAME, 
		'XD SUs Available' AS display, 
		'available_xd_su' AS alias, 
		'XD SU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.available * $this->xd_su_cf_sql), 0)' AS formula, 
		'The total available amount in XD SUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'awarded_nu' AS NAME, 
		'NUs Awarded' AS display, 
		'awarded_nu' AS alias, 
		'NU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.awarded * $this->xd_su_cf_sql) * $this->xd_su_to_nu_string, 0)' AS formula, 
		'The total awarded amount in NUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'awarded_native_su' AS NAME, 
		'CPU Core Hours Awarded' AS display, 
		'awarded_native_su' AS alias, 
		'CPU Core Hours' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.awarded), 0)' AS formula, 
		'The total awarded amount in native SUs (CPU core hours).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'awarded_xd_su' AS NAME, 
		'XD SUs Awarded' AS display, 
		'awarded_xd_su' AS alias, 
		'XD SU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.awarded * $this->xd_su_cf_sql), 0)' AS formula, 
		'The total awarded amount in XD SUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'recommended_nu' AS NAME, 
		'NUs Recommended' AS display, 
		'recommended_nu' AS alias, 
		'NU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.recommended * $this->xd_su_cf_sql) * $this->xd_su_to_nu_string, 0)' AS formula, 
		'The total recommended amount in NUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'recommended_native_su' AS NAME, 
		'CPU Core Hours Recommended' AS display, 
		'recommended_native_su' AS alias, 
		'CPU Core Hours' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.recommended), 0)' AS formula, 
		'The total recommended amount in native SUs (CPU core hours).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'recommended_xd_su' AS NAME, 
		'XD SUs Recommended' AS display, 
		'recommended_xd_su' AS alias, 
		'XD SU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.recommended * $this->xd_su_cf_sql), 0)' AS formula, 
		'The total recommended amount in XD SUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'requested_nu' AS NAME, 
		'NUs Requested' AS display, 
		'requested_nu' AS alias, 
		'NU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.requested * $this->xd_su_cf_sql) * $this->xd_su_to_nu_string, 0)' AS formula, 
		'The total requested amount in NUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'requested_native_su' AS NAME, 
		'CPU Core Hours Requested' AS display, 
		'requested_native_su' AS alias, 
		'CPU Core Hours' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.requested), 0)' AS formula, 
		'The total requested amount in native SUs (CPU core hours).' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'requested_xd_su' AS NAME, 
		'XD SUs Requested' AS display, 
		'requested_xd_su' AS alias, 
		'XD SU' AS unit, 
		'0'+0 AS decimals, 
		'coalesce(sum(jf.requested * $this->xd_su_cf_sql), 0)' AS formula, 
		'The total requested amount in XD SUs.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'weight' AS NAME, 
		'Weight' AS display, 
		'weight' AS alias, 
		'Weight' AS unit, 
		'0'+0 AS decimals, 
		'1' AS formula, 
		'The weight of a resource allocations realm data point.' AS description 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_block_sda_rd_bytes' AS NAME, 
		'Avg: block sda read rate: Per Node weighted by node-hour' AS display, 
		'avg_block_sda_rd_bytes' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.block_sda_rd_bytes / jf.wall_time / jf.nodecount_id * jf.block_sda_rd_bytes_weight)/sum(jf.block_sda_rd_bytes_weight)\')' AS formula, 
		'Average number of bytes read per second per node from the local hard disk device sda.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_block_sda_rd_ios' AS NAME, 
		'Avg: block sda read ops rate: Per Node weighted by node-hour' AS display, 
		'avg_block_sda_rd_ios' AS alias, 
		'ops/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(block_sda_rd_ios / jf.wall_time / jf.nodecount_id * jf.block_sda_rd_ios_weight)/sum(jf.block_sda_rd_ios_weight)\')' AS formula, 
		'Average number of read operations per second per node for the local hard disk device sda.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_block_sda_wr_bytes' AS NAME, 
		'Avg: block sda write rate: Per Node weighted by node-hour' AS display, 
		'avg_block_sda_wr_bytes' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(block_sda_wr_bytes / jf.wall_time / jf.nodecount_id * jf.block_sda_wr_bytes_weight)/sum(jf.block_sda_wr_bytes_weight)\')' AS formula, 
		'Average number of bytes written per second per node to the local hard disk device sda.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_block_sda_wr_ios' AS NAME, 
		'Avg: block sda write ops rate: Per Node weighted by node-hour' AS display, 
		'avg_block_sda_wr_ios' AS alias, 
		'ops/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(block_sda_wr_ios / jf.wall_time / jf.nodecount_id * jf.block_sda_wr_ios_weight)/sum(jf.block_sda_wr_ios_weight)\')' AS formula, 
		'Average number of write operations per second per node for the local hard disk device sda.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_cpiref_per_core' AS NAME, 
		'Avg: CPI: Per Core weighted by core-hour' AS display, 
		'avg_cpiref_per_core' AS alias, 
		'CPI' AS unit, 
		'2'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpiref_weighted_by_coreseconds / jf.wall_time / jf.cores * jf.cpiref_weight)/sum(jf.cpiref_weight)\')' AS formula, 
		'The average ratio of clock ticks to instructions per core weighted by core-hour. The CPI is calculated using the reference processor clock.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_cpldref_per_core' AS NAME, 
		'Avg: CPLD: Per Core weighted by core-hour' AS display, 
		'avg_cpldref_per_core' AS alias, 
		'CPLD' AS unit, 
		'4'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpldref_weighted_by_coreseconds / jf.wall_time / jf.cores * jf.cpldref_weight)/sum(jf.cpldref_weight)\')' AS formula, 
		'The average ratio of clock ticks to L1D cache loads per core weighted by core-hour. The CPLD is calculated using the reference processor clock.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_cpuusercv_per_core' AS NAME, 
		'Avg: CPU User CV: weighted by core-hour' AS display, 
		'avg_cpuusercv_per_core' AS alias, 
		'CV' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpu_user_cv_weighted_core_seconds / jf.wall_time / jf.cores * jf.cpu_usage_weight)/sum(jf.cpu_usage_weight)\')' AS formula, 
		'The average CPU user coefficient of variation weighted by core-hour. The coefficient of variation is defined as the ratio of the standard deviation to the mean' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_cpuuserimb_per_core' AS NAME, 
		'Avg: CPU User Imbalance: weighted by core-hour' AS display, 
		'avg_cpuuserimb_per_core' AS alias, 
		'%' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpu_user_imbalance_weighted_core_seconds / jf.wall_time / jf.cores * jf.cpu_usage_weight)/sum(jf.cpu_usage_weight)\')' AS formula, 
		'The average normalized CPU user imbalance weighted by core-hour. Imbalance is defined as 100*(max-min)/max, where max is value of the CPU user for the CPU with the largest CPU user.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_flops_per_core' AS NAME, 
		'Avg: FLOPS: Per Core weighted by core-hour' AS display, 
		'avg_flops_per_core' AS alias, 
		'ops/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.flop / jf.wall_time * jf.flop_weight)/sum(jf.flop_weight)\')' AS formula, 
		'The average number of floating point operations per second per core over all jobs that ran in the selected time period.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_ib_rx_bytes' AS NAME, 
		'Avg: InfiniBand rate: Per Node weighted by node-hour' AS display, 
		'avg_ib_rx_bytes' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(ib_rx_bytes / jf.wall_time / jf.nodecount_id * jf.ib_rx_bytes_weight)/sum(jf.ib_rx_bytes_weight)\')' AS formula, 
		'Average number of bytes received per second per node over the data interconnect. This value only includes the inter-node data transfers and does not count any other data over the interconnect (for example parallel filesystem data).' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_mem_bw_per_core' AS NAME, 
		'Avg: Memory Bandwidth: Per Core weighted by core-hour' AS display, 
		'avg_mem_bw_per_core' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.mem_transferred / jf.wall_time / jf.cores * jf.mem_transferred_weight)/sum(jf.mem_transferred_weight)\')' AS formula, 
		'The average main-memory transfer rate per core weighted by core-hour.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_memory_per_core' AS NAME, 
		'Avg: Memory: Per Core weighted by core-hour' AS display, 
		'avg_memory_per_core' AS alias, 
		'bytes' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.mem_used_weighted_by_duration / jf.wall_time / jf.cores * jf.mem_usage_weight)/sum(jf.mem_usage_weight)\')' AS formula, 
		'The average memory used per core for all selected jobs that ran in the selected time period' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_eth0_rx' AS NAME, 
		'Avg: eth0 receive rate: Per Node weighted by node-hour' AS display, 
		'avg_net_eth0_rx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_eth0_rx / jf.wall_time / jf.nodecount_id * jf.net_eth0_rx_weight)/sum(jf.net_eth0_rx_weight)\')' AS formula, 
		'Average number of bytes received per second per node for network device eth0' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_eth0_tx' AS NAME, 
		'Avg: eth0 transmit rate: Per Node weighted by node-hour' AS display, 
		'avg_net_eth0_tx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_eth0_tx / jf.wall_time / jf.nodecount_id * jf.net_eth0_tx_weight)/sum(jf.net_eth0_tx_weight)\')' AS formula, 
		'Average number of bytes transmitted per second per node for network device eth0.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_ib0_rx' AS NAME, 
		'Avg: ib0 receive rate: Per Node weighted by node-hour' AS display, 
		'avg_net_ib0_rx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_ib0_rx / jf.wall_time / jf.nodecount_id * jf.net_ib0_rx_weight)/sum(jf.net_ib0_rx_weight)\')' AS formula, 
		'Average number of bytes received per second per node for network device ib0' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_ib0_tx' AS NAME, 
		'Avg: ib0 transmit rate: Per Node weighted by node-hour' AS display, 
		'avg_net_ib0_tx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_ib0_tx / jf.wall_time / jf.nodecount_id * jf.net_ib0_tx_weight)/sum(jf.net_ib0_tx_weight)\')' AS formula, 
		'Average number of bytes transmitted per second per node for network device ib0.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_mic0_rx' AS NAME, 
		'Avg: mic0 receive rate: Per Node weighted by node-hour' AS display, 
		'avg_net_mic0_rx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_mic0_rx / jf.wall_time / jf.nodecount_id * jf.net_mic0_rx_weight)/sum(jf.net_mic0_rx_weight)\')' AS formula, 
		'Average number of bytes received per second per node for network device mic0' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_mic0_tx' AS NAME, 
		'Avg: mic0 transmit rate: Per Node weighted by node-hour' AS display, 
		'avg_net_mic0_tx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_mic0_tx / jf.wall_time / jf.nodecount_id * jf.net_mic0_tx_weight)/sum(jf.net_mic0_tx_weight)\')' AS formula, 
		'Average number of bytes transmitted per second per node for network device mic0.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_mic1_rx' AS NAME, 
		'Avg: mic1 receive rate: Per Node weighted by node-hour' AS display, 
		'avg_net_mic1_rx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_mic1_rx / jf.wall_time / jf.nodecount_id * jf.net_mic1_rx_weight)/sum(jf.net_mic1_rx_weight)\')' AS formula, 
		'Average number of bytes received per second per node for network device mic1' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_net_mic1_tx' AS NAME, 
		'Avg: mic1 transmit rate: Per Node weighted by node-hour' AS display, 
		'avg_net_mic1_tx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.net_mic1_tx / jf.wall_time / jf.nodecount_id * jf.net_mic1_tx_weight)/sum(jf.net_mic1_tx_weight)\')' AS formula, 
		'Average number of bytes transmitted per second per node for network device mic1.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_netdir_home_write' AS NAME, 
		'Avg: /home write rate: Per Node weighted by node-hour' AS display, 
		'avg_netdir_home_write' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.netdir_home_write / jf.wall_time / jf.nodecount_id * jf.netdir_home_write_weight)/sum(jf.netdir_home_write_weight)\')' AS formula, 
		'Average number of bytes written per second per node for the filesystem mounted on mount point /home' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_netdir_scratch_write' AS NAME, 
		'Avg: /scratch write rate: Per Node weighted by node-hour' AS display, 
		'avg_netdir_scratch_write' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.netdir_scratch_write / jf.wall_time / jf.nodecount_id * jf.netdir_scratch_write_weight)/sum(jf.netdir_scratch_write_weight)\')' AS formula, 
		'Average number of bytes written per second per node for the filesystem mounted on mount point /scratch' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_netdir_work_write' AS NAME, 
		'Avg: /work write rate: Per Node weighted by node-hour' AS display, 
		'avg_netdir_work_write' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.netdir_work_write / jf.wall_time / jf.nodecount_id * jf.netdir_work_write_weight)/sum(jf.netdir_work_write_weight)\')' AS formula, 
		'Average number of bytes written per second per node for the filesystem mounted on mount point /work' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_netdrv_lustre_rx' AS NAME, 
		'Avg: lustre receive rate: Per Node weighted by node-hour' AS display, 
		'avg_netdrv_lustre_rx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.netdrv_lustre_rx / jf.wall_time / jf.nodecount_id * jf.netdrv_lustre_rx_weight)/sum(jf.netdrv_lustre_rx_weight)\')' AS formula, 
		'Average number of bytes received per second per node from the lustre filesystem.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_netdrv_lustre_tx' AS NAME, 
		'Avg: lustre transmit rate: Per Node weighted by node-hour' AS display, 
		'avg_netdrv_lustre_tx' AS alias, 
		'bytes/s' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.netdrv_lustre_tx / jf.wall_time / jf.nodecount_id * jf.netdrv_lustre_tx_weight)/sum(jf.netdrv_lustre_tx_weight)\')' AS formula, 
		'Average number of bytes transmitted per second per node to the lustre filesystem.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_percent_cpu_idle' AS NAME, 
		'Avg CPU %: Idle: weighted by core-hour' AS display, 
		'avg_percent_cpu_idle' AS alias, 
		'CPU %' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(100.0 * jf.cpu_time_idle / jf.cpu_time * jf.cpu_usage_weight)/sum(jf.cpu_usage_weight)\')' AS formula, 
		'The average CPU idle % weighted by core hours, over all jobs that were executing.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_percent_cpu_system' AS NAME, 
		'Avg CPU %: System: weighted by core-hour' AS display, 
		'avg_percent_cpu_system' AS alias, 
		'CPU %' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(100.0 * jf.cpu_time_system / jf.cpu_time * jf.cpu_usage_weight)/sum(jf.cpu_usage_weight)\')' AS formula, 
		'The average CPU system % weighted by core hours, over all jobs that were executing.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_percent_cpu_user' AS NAME, 
		'Avg CPU %: User: weighted by core-hour' AS display, 
		'avg_percent_cpu_user' AS alias, 
		'CPU %' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(100.0 * jf.cpu_time_user / jf.cpu_time * jf.cpu_usage_weight)/sum(jf.cpu_usage_weight)\')' AS formula, 
		'The average CPU user % weighted by core hours, over all jobs that were executing.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'avg_total_memory_per_core' AS NAME, 
		'Avg: Total Memory: Per Core weighted by core-hour' AS display, 
		'avg_total_memory_per_core' AS alias, 
		'bytes' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.mem_used_including_os_caches_weighted_by_duration / jf.wall_time / jf.cores * jf.mem_usage_weight)/sum(jf.mem_usage_weight)\')' AS formula, 
		'The average total memory used (including kernel and disk cache) per core for all selected jobs that ran in the selected time period' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu_time_idle' AS NAME, 
		'CPU Hours: Idle: Total' AS display, 
		'cpu_time_idle' AS alias, 
		'CPU Hour' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpu_time_idle/3600.0)\')' AS formula, 
		'The idle CPU hours for all jobs that were executing during the time period.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu_time_system' AS NAME, 
		'CPU Hours: System: Total' AS display, 
		'cpu_time_system' AS alias, 
		'CPU Hour' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpu_time_system/3600.0)\')' AS formula, 
		'The system CPU hours for all jobs that were executing during the time period.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'cpu_time_user' AS NAME, 
		'CPU Hours: User: Total' AS display, 
		'cpu_time_user' AS alias, 
		'CPU Hour' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'sum(jf.cpu_time_user/3600.0)\')' AS formula, 
		'The user CPU hours for all jobs that were executing during the time period.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'job_count' AS NAME, 
		'Number of Jobs Ended' AS display, 
		'job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.job_count),0)\')' AS formula, 
		'The total number of jobs that ended within the selected duration.<br/><i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'requested_wall_time' AS NAME, 
		'Wall Hours: Requested: Total' AS display, 
		'requested_wall_time' AS alias, 
		'Hour' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.requested_wall_time/3600.0),0)\')' AS formula, 
		'The total time, in hours, jobs requested for execution.<br/><i>Requested Wall Time:</i> Requsted wall time is defined as the user requested linear time between start and end time for execution of a particular job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'requested_wall_time_per_job' AS NAME, 
		'Wall Hours: Requested: Per Job' AS display, 
		'requested_wall_time_per_job' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.requested_wall_time/3600.0)/sum(case when :timeseries then jf.running_job_count else jf.job_count end),0)\')' AS formula, 
		'The average time, in hours, a job requested for execution.<br/><i>Requested Wall Time:</i> Requsted wall time is defined as the user requested linear time between start and end time for execution of a particular job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'running_job_count' AS NAME, 
		'Number of Jobs Running' AS display, 
		'running_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.running_job_count),0)\')' AS formula, 
		'The total number of running jobs.<br/><i>Job: </i>A scheduled process for a computer resource in a batch processing environment' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'started_job_count' AS NAME, 
		'Number of Jobs Started' AS display, 
		'started_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.started_job_count),0)\')' AS formula, 
		'The total number of jobs that started executing within the selected duration.<br/><i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'submitted_job_count' AS NAME, 
		'Number of Jobs Submitted' AS display, 
		'submitted_job_count' AS alias, 
		'Number of Jobs' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.submitted_job_count),0)\')' AS formula, 
		'The total number of jobs that were submitted/queued within the selected duration.<br/><i>Job: </i>A scheduled process for a computer resource in a batch processing environment.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'wait_time' AS NAME, 
		'Wait Hours: Total' AS display, 
		'wait_time' AS alias, 
		'Hour' AS unit, 
		'1'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.wait_time/3600.0),0)\')' AS formula, 
		'The total time, in hours, jobs waited before execution on their designated resource.<br/><i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'wait_time_per_job' AS NAME, 
		'Wait Hours: Per Job' AS display, 
		'wait_time_per_job' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.wait_time/3600.0)/sum(jf.started_job_count),0)\')' AS formula, 
		'The average time, in hours, a job waits before execution on the designated resource.<br/><i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'wall_time' AS NAME, 
		'CPU Hours: Total' AS display, 
		'wall_time' AS alias, 
		'CPU Hour' AS unit, 
		'0'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.wall_time*jf.cores/3600.0),0)\')' AS formula, 
		'The total core time, in hours.<br/><i>Core Time:</i> defined as the time between start and end time of execution for a particular job times the number of allocated cores.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

INSERT INTO statistics (module_id, realm_id, name, display, alias, unit, decimals, formula, description) 
SELECT inc.* FROM ( 
	SELECT 
		m.module_id AS module_id, 
		r.realm_id AS realm_id, 
		'wall_time_per_job' AS NAME, 
		'Wall Hours: Per Job' AS display, 
		'wall_time_per_job' AS alias, 
		'Hour' AS unit, 
		'2'+0 AS decimals, 
		'str_replace(\':timeseries\', $query_instance->getQueryType() == \'timeseries\' ? 1 : 0, \'coalesce(sum(jf.wall_time/3600.0)/sum(case when :timeseries then jf.running_job_count else jf.job_count end),0)\')' AS formula, 
		'The average time, in hours, a job takes to execute.<br/><i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.' AS description 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN statistics cur 
	ON cur.module_id = inc.module_id 
	AND cur.name = inc.name 
	AND cur.display = inc.display 
	AND cur.alias = inc.alias 
	AND cur.unit = inc.unit 
	AND cur.decimals = inc.decimals 
	AND replace(replace(cur.formula, ' ', ''), '\n', '') LIKE replace(replace(inc.formula, ' ', ''), '\n', '') 
	AND replace(replace(cur.description, ' ', ''), '\n', '') LIKE replace(replace(inc.description, ' ', ''), '\n', '') 
WHERE cur.statistic_id IS NULL;

