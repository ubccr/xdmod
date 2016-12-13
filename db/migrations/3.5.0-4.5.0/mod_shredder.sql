CREATE TABLE IF NOT EXISTS `schema_version_history` (
    `database_name` char(64) NOT NULL,
    `schema_version` char(64) NOT NULL,
    `action_datetime` datetime NOT NULL,
    `action_type` enum('created','upgraded') NOT NULL,
    `script_name` varchar(255) NOT NULL,
    PRIMARY KEY (`database_name`,`schema_version`,`action_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `schema_version_history` VALUES ('mod_shredder', '4.5.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `shredded_job`
    MODIFY COLUMN `source_format` enum('pbs','sge','slurm','lsf') NOT NULL,
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    MODIFY COLUMN `group_name` varchar(255) NOT NULL DEFAULT 'Unknown',
    ADD COLUMN `pi_name` varchar(255) NOT NULL DEFAULT 'Unknown' AFTER `project_name`,
    CHANGE COLUMN `wallt` `wall_time` bigint(20) unsigned NOT NULL,
    DROP COLUMN `cput`,
    DROP COLUMN `mem`,
    DROP COLUMN `vmem`,
    CHANGE COLUMN `wait` `wait_time` bigint(20) unsigned NOT NULL,
    DROP COLUMN `exect`,
    CHANGE COLUMN `nodes` `node_count` int(10) unsigned NOT NULL,
    CHANGE COLUMN `cpus` `cpu_count` int(10) unsigned NOT NULL,
    ADD KEY `date_key` (`date_key`,`resource_name`),
    ADD KEY `end_time` (`end_time`,`resource_name`),
    ADD KEY `resource_name` (`resource_name`),
    ADD KEY `pi_name` (`pi_name`,`resource_name`),
    ADD KEY `user_name` (`user_name`,`resource_name`,`pi_name`);

-- Use a stored procedure to update "small" groups of rows in order to
-- prevent ERROR 1206 (The total number of locks exceeds the lock table
-- size).
DROP PROCEDURE IF EXISTS migrate_shredded_job;
delimiter //
CREATE PROCEDURE migrate_shredded_job()
BEGIN
    SET @interval = 10000;
    SET @max = (SELECT MAX(`shredded_job_id`) FROM `shredded_job`);
    SET @i = 0;
    WHILE @i < @max DO
        UPDATE `shredded_job` SET `pi_name` = `group_name`
            WHERE `shredded_job_id` BETWEEN @i + 1 AND @i + @interval;
        SET @i = @i + @interval;
    END WHILE;
END//
delimiter ;
CALL migrate_shredded_job();
DROP PROCEDURE migrate_shredded_job;

ALTER TABLE `shredded_job_slurm`
    ADD COLUMN `job_array_index` int(10) NOT NULL DEFAULT '-1' AFTER `job_id`,
    DROP KEY `job`,
    ADD UNIQUE KEY `job` (`cluster_name`(20),`job_id`,`job_array_index`,`submit_time`,`end_time`);

CREATE TABLE `shredded_job_lsf` (
    `shredded_job_lsf_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `job_id` int(10) unsigned NOT NULL,
    `idx` int(10) unsigned NOT NULL,
    `job_name` varchar(255) NOT NULL DEFAULT '',
    `resource_name` varchar(255) NOT NULL,
    `queue` varchar(255) NOT NULL,
    `user_name` varchar(255) NOT NULL,
    `project_name` varchar(255) NOT NULL DEFAULT '',
    `submit_time` int(10) unsigned NOT NULL,
    `start_time` int(10) unsigned NOT NULL,
    `event_time` int(10) unsigned NOT NULL,
    `num_processors` int(10) unsigned NOT NULL,
    `num_ex_hosts` int(10) unsigned NOT NULL,
    PRIMARY KEY (`shredded_job_lsf_id`),
    UNIQUE KEY `job` (`resource_name`(20),`job_id`,`idx`,`submit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `staging_cluster`
    RENAME TO `staging_resource`,
    CHANGE COLUMN `cluster_id` `resource_id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    DROP KEY `cluster_name`,
    ADD UNIQUE KEY `resource_name` (`resource_name`);

ALTER TABLE `staging_group`
    RENAME TO `staging_pi`,
    CHANGE COLUMN `group_id` `pi_id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `group_name` `pi_name` varchar(255) NOT NULL,
    DROP KEY `group_name`,
    ADD UNIQUE KEY `pi_name` (`pi_name`);

ALTER TABLE `staging_group_cluster`
    RENAME TO `staging_pi_resource`,
    CHANGE COLUMN `group_cluster_id` `pi_resource_id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `group_name` `pi_name` varchar(255) NOT NULL,
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    DROP KEY `group_cluster_name`,
    DROP KEY `group_name`,
    DROP KEY `cluster_name`,
    ADD UNIQUE KEY `pi_resource_name` (`pi_name`,`resource_name`),
    ADD KEY `pi_name` (`pi_name`),
    ADD KEY `resource_name` (`resource_name`);

ALTER TABLE `staging_job`
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    ADD COLUMN `pi_name` varchar(255) NOT NULL AFTER `project_name`,
    CHANGE COLUMN `wallt` `wall_time` bigint(20) unsigned NOT NULL,
    DROP COLUMN `cput`,
    DROP COLUMN `mem`,
    DROP COLUMN `vmem`,
    CHANGE COLUMN `wait` `wait_time` bigint(20) unsigned NOT NULL,
    DROP COLUMN `exect`,
    CHANGE COLUMN `nodes` `node_count` int(10) unsigned NOT NULL,
    CHANGE COLUMN `cpus` `cpu_count` int(10) unsigned NOT NULL;

ALTER TABLE `staging_union_user_group`
    RENAME TO `staging_union_user_pi`,
    CHANGE COLUMN `union_user_group_id` `union_user_pi_id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `union_user_group_name` `union_user_pi_name` varchar(255) NOT NULL,
    DROP KEY `union_user_group_name`,
    ADD UNIQUE KEY `union_user_pi_name` (`union_user_pi_name`);

ALTER TABLE `staging_union_user_group_cluster`
    RENAME TO `staging_union_user_pi_resource`,
    CHANGE COLUMN `union_user_group_cluster_id` `union_user_pi_resource_id` int(11) NOT NULL AUTO_INCREMENT,
    CHANGE COLUMN `union_user_group_name` `union_user_pi_name` varchar(255) NOT NULL,
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    DROP KEY `union_user_group_cluster_name`,
    DROP KEY `union_user_group_name`,
    DROP KEY `cluster_name`,
    ADD UNIQUE KEY `union_user_pi_resource_name` (`union_user_pi_name`,`resource_name`),
    ADD KEY `union_user_pi_name` (`union_user_pi_name`),
    ADD KEY `resource_name` (`resource_name`);

ALTER TABLE `staging_user_group_cluster`
    RENAME TO `staging_user_pi_resource`,
    CHANGE COLUMN `user_group_cluster_id` `user_pi_resource_id` int(11) NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN `user_name` varchar(255) NOT NULL,
    CHANGE COLUMN `group_name` `pi_name` varchar(255) NOT NULL,
    CHANGE COLUMN `cluster_name` `resource_name` varchar(255) NOT NULL,
    DROP KEY `user_group_cluster`,
    DROP KEY `group_name`,
    DROP KEY `cluster_name`,
    ADD UNIQUE KEY `user_pi_resource` (`user_name`,`pi_name`,`resource_name`),
    ADD KEY `pi_name` (`pi_name`),
    ADD KEY `resource_name` (`resource_name`);

