CREATE TABLE IF NOT EXISTS `schema_version_history` (
    `database_name` char(64) NOT NULL,
    `schema_version` char(64) NOT NULL,
    `action_datetime` datetime NOT NULL,
    `action_type` enum('created','upgraded') NOT NULL,
    `script_name` varchar(255) NOT NULL,
    PRIMARY KEY (`database_name`,`schema_version`,`action_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `schema_version_history` VALUES ('mod_hpcdb', '4.5.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `hpcdb_jobs`
    DROP COLUMN `memory`,
    ADD COLUMN `local_job_array_index` int(11) NOT NULL AFTER `local_jobid`,
    MODIFY COLUMN `ts` int(10) unsigned NOT NULL AFTER `username`,
    DROP KEY `uk_jobs`,
    ADD UNIQUE KEY `uk_jobs` (`resource_id`,`local_jobid`,`local_job_array_index`,`submit_time`),
    ADD KEY `jobs_end_time_idx` (`end_time`);

CREATE TABLE `hpcdb_resource_specs` (
    `resource_spec_id` int(11) NOT NULL AUTO_INCREMENT,
    `resource_id` int(11) NOT NULL,
    `start_date_ts` int(11) NOT NULL DEFAULT 0,
    `end_date_ts` int(11) DEFAULT NULL,
    `node_count` int(11) NOT NULL,
    `cpu_count` int(11) NOT NULL,
    `cpu_count_per_node` int(11) NOT NULL,
    `comments` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`resource_spec_id`),
    UNIQUE KEY `resource_spec_start_date` (`resource_id`,`start_date_ts`),
    CONSTRAINT `fk_resource` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!50001 DROP TABLE IF EXISTS `hpcdb_fields_of_science_hierarchy`*/;
/*!50001 DROP VIEW IF EXISTS `hpcdb_fields_of_science_hierarchy`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 */
/*!50001 VIEW `hpcdb_fields_of_science_hierarchy` AS select `fos`.`field_of_science_id` AS `field_of_science_id`,`fos`.`description` AS `description`,if(isnull(`gp`.`field_of_science_id`),`fos`.`field_of_science_id`,coalesce(`p`.`field_of_science_id`,`fos`.`field_of_science_id`)) AS `parent_id`,if(isnull(`gp`.`field_of_science_id`),`fos`.`description`,coalesce(`p`.`description`,`fos`.`description`)) AS `parent_description`,coalesce(`gp`.`field_of_science_id`,`p`.`field_of_science_id`,`fos`.`field_of_science_id`) AS `directorate_id`,coalesce(`gp`.`description`,`p`.`description`,`fos`.`description`) AS `directorate_description`,coalesce(`gp`.`abbrev`,`p`.`abbrev`,`fos`.`abbrev`) AS `directorate_abbrev` from ((`hpcdb_fields_of_science` `fos` left join `hpcdb_fields_of_science` `p` on((`fos`.`parent_id` = `p`.`field_of_science_id`))) left join `hpcdb_fields_of_science` `gp` on((`p`.`parent_id` = `gp`.`field_of_science_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

