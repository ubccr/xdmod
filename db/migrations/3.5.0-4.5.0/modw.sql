CREATE TABLE IF NOT EXISTS `schema_version_history` (
    `database_name` char(64) NOT NULL,
    `schema_version` char(64) NOT NULL,
    `action_datetime` datetime NOT NULL,
    `action_type` enum('created','upgraded') NOT NULL,
    `script_name` varchar(255) NOT NULL,
    PRIMARY KEY (`database_name`,`schema_version`,`action_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `schema_version_history` VALUES ('modw', '4.5.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `jobfact`
    ADD COLUMN `local_job_array_index` int(11) NOT NULL AFTER `local_jobid`,
    DROP KEY `index_jobfact_local_jobid`,
    ADD KEY `index_jobfact_local_jobid` (`local_jobid`,`local_job_array_index`,`resource_id`);

CREATE TABLE `resourcespecs` (
    `resource_id` int(11) NOT NULL,
    `start_date_ts` int(11) NOT NULL,
    `end_date_ts` int(11) DEFAULT NULL,
    `processors` int(11) DEFAULT NULL,
    `q_nodes` int(11) DEFAULT NULL,
    `q_ppn` int(11) DEFAULT NULL,
    `comments` varchar(500) DEFAULT NULL,
    `name` varchar(200) DEFAULT NULL,
    PRIMARY KEY (`resource_id`,`start_date_ts`),
    KEY `unq` (`name`,`start_date_ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `resourcespecs`
    SELECT
        `id`,
        0,
        NULL,
        `processors`,
        `q_nodes`,
        `q_ppn`,
        `comments`,
        `name`
    FROM `resourcefact`;

ALTER TABLE `resourcefact`
    MODIFY COLUMN `start_date_ts` int(14) DEFAULT '0',
    DROP COLUMN `nodes`,
    DROP COLUMN `ppn`,
    DROP COLUMN `processors`,
    DROP COLUMN `q_nodes`,
    DROP COLUMN `q_ppn`,
    DROP COLUMN `rmax`,
    DROP COLUMN `rpeak`,
    DROP COLUMN `rcores`,
    DROP COLUMN `comments`,
    DROP KEY `name_UNIQUE`,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`,`start_date_ts`);

