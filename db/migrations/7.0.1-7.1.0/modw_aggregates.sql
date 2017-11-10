USE modw_aggregates;
-- Keep everything intact just changing the names, in the future these should be changed to be utf8mb4
ALTER TABLE `jobfact_by_day`
    CHANGE `resource_id` `task_resource_id` INT(5)  NOT NULL  COMMENT 'DIMENSION: The resource on which the jobs ran',
    CHANGE `job_count` `ended_job_count` INT(11)  NULL  DEFAULT NULL  COMMENT 'FACT: The number of jobs that ended during this period.',
    CHANGE `processors` `processor_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of processors each of the jobs used.',
    CHANGE `jobtime_id` `job_time_bucket_id` INT(3)  NOT NULL  COMMENT 'DIMENSION: Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.',
    CHANGE `nodecount_id` `node_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of nodes each of the jobs used.',
    CHANGE `queue_id` `queue` CHAR(50)  CHARACTER SET latin1  COLLATE latin1_swedish_ci  NOT NULL  DEFAULT ''  COMMENT 'DIMENSION: The queue of the resource on which the jobs ran.',
    CHANGE `organization_id` `resource_organization_id` INT(11)  NOT NULL  COMMENT 'DIMENSION: The organization of the resource that the jobs ran on.';


ALTER TABLE `jobfact_by_month`
    CHANGE `resource_id` `task_resource_id` INT(5)  NOT NULL  COMMENT 'DIMENSION: The resource on which the jobs ran',
    CHANGE `job_count` `ended_job_count` INT(11)  NULL  DEFAULT NULL  COMMENT 'FACT: The number of jobs that ended during this period.',
    CHANGE `processors` `processor_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of processors each of the jobs used.',
    CHANGE `jobtime_id` `job_time_bucket_id` INT(3)  NOT NULL  COMMENT 'DIMENSION: Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.',
    CHANGE `nodecount_id` `node_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of nodes each of the jobs used.',
    CHANGE `queue_id` `queue` CHAR(50)  CHARACTER SET latin1  COLLATE latin1_swedish_ci  NOT NULL  DEFAULT ''  COMMENT 'DIMENSION: The queue of the resource on which the jobs ran.',
    CHANGE `organization_id` `resource_organization_id` INT(11)  NOT NULL  COMMENT 'DIMENSION: The organization of the resource that the jobs ran on.';

ALTER TABLE `jobfact_by_quarter`
    CHANGE `resource_id` `task_resource_id` INT(5)  NOT NULL  COMMENT 'DIMENSION: The resource on which the jobs ran',
    CHANGE `job_count` `ended_job_count` INT(11)  NULL  DEFAULT NULL  COMMENT 'FACT: The number of jobs that ended during this period.',
    CHANGE `processors` `processor_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of processors each of the jobs used.',
    CHANGE `jobtime_id` `job_time_bucket_id` INT(3)  NOT NULL  COMMENT 'DIMENSION: Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.',
    CHANGE `nodecount_id` `node_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of nodes each of the jobs used.',
    CHANGE `queue_id` `queue` CHAR(50)  CHARACTER SET latin1  COLLATE latin1_swedish_ci  NOT NULL  DEFAULT ''  COMMENT 'DIMENSION: The queue of the resource on which the jobs ran.',
    CHANGE `organization_id` `resource_organization_id` INT(11)  NOT NULL  COMMENT 'DIMENSION: The organization of the resource that the jobs ran on.';

ALTER TABLE `jobfact_by_year`
    CHANGE `resource_id` `task_resource_id` INT(5)  NOT NULL  COMMENT 'DIMENSION: The resource on which the jobs ran',
    CHANGE `job_count` `ended_job_count` INT(11)  NULL  DEFAULT NULL  COMMENT 'FACT: The number of jobs that ended during this period.',
    CHANGE `processors` `processor_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of processors each of the jobs used.',
    CHANGE `jobtime_id` `job_time_bucket_id` INT(3)  NOT NULL  COMMENT 'DIMENSION: Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.',
    CHANGE `nodecount_id` `node_count` INT(8)  NOT NULL  COMMENT 'DIMENSION: Number of nodes each of the jobs used.',
    CHANGE `queue_id` `queue` CHAR(50)  CHARACTER SET latin1  COLLATE latin1_swedish_ci  NOT NULL  DEFAULT ''  COMMENT 'DIMENSION: The queue of the resource on which the jobs ran.',
    CHANGE `organization_id` `resource_organization_id` INT(11)  NOT NULL  COMMENT 'DIMENSION: The organization of the resource that the jobs ran on.';
