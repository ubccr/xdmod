INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    0
  FROM statistics s
  WHERE s.name = 'job_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    1
  FROM statistics s
  WHERE s.name = 'gateway_job_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    2
  FROM statistics s
  WHERE s.name = 'running_job_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    3
  FROM statistics s
  WHERE s.name = 'started_job_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    4
  FROM statistics s
  WHERE s.name = 'submitted_job_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    5
  FROM statistics s
  WHERE s.name = 'active_person_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    6
  FROM statistics s
  WHERE s.name = 'active_pi_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    7
  FROM statistics s
  WHERE s.name = 'active_allocation_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    8
  FROM statistics s
  WHERE s.name = 'active_institution_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    9
  FROM statistics s
  WHERE s.name = 'total_su';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    10
  FROM statistics s
  WHERE s.name = 'total_nu';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    11
  FROM statistics s
  WHERE s.name = 'total_cpu_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    12
  FROM statistics s
  WHERE s.name = 'total_node_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    13
  FROM statistics s
  WHERE s.name = 'total_waitduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    14
  FROM statistics s
  WHERE s.name = 'total_wallduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    15
  FROM statistics s
  WHERE s.name = 'avg_su';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    16
  FROM statistics s
  WHERE s.name = 'sem_avg_su';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    17
  FROM statistics s
  WHERE s.name = 'avg_nu';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    18
  FROM statistics s
  WHERE s.name = 'sem_avg_nu';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    19
  FROM statistics s
  WHERE s.name = 'avg_cpu_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    20
  FROM statistics s
  WHERE s.name = 'sem_avg_cpu_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    21
  FROM statistics s
  WHERE s.name = 'avg_node_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    22
  FROM statistics s
  WHERE s.name = 'sem_avg_node_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    23
  FROM statistics s
  WHERE s.name = 'avg_waitduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    24
  FROM statistics s
  WHERE s.name = 'sem_avg_waitduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    25
  FROM statistics s
  WHERE s.name = 'avg_wallduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    26
  FROM statistics s
  WHERE s.name = 'sem_avg_wallduration_hours';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    27
  FROM statistics s
  WHERE s.name = 'avg_processors';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    28
  FROM statistics s
  WHERE s.name = 'sem_avg_processors';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    29
  FROM statistics s
  WHERE s.name = 'min_processors';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    30
  FROM statistics s
  WHERE s.name = 'max_processors';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    31
  FROM statistics s
  WHERE s.name = 'utilization';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    32
  FROM statistics s
  WHERE s.name = 'expansion_factor';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    33
  FROM statistics s
  WHERE s.name = 'normalized_avg_processors';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    34
  FROM statistics s
  WHERE s.name = 'avg_job_size_weighted_by_xd_su';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    35
  FROM statistics s
  WHERE s.name = 'active_resource_count';

INSERT INTO statistics_hierarchy(statistic_id, hierarchy_id, value)
  SELECT
    s.statistic_id as statistic_id,
    2,
    36
  FROM statistics s
  WHERE s.name = 'rate_of_usage';