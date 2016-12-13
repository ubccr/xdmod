
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `hpcdb_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_allocation_breakdown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_allocation_breakdown` (
  `allocation_breakdown_id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `allocation_id` int(11) NOT NULL,
  `percentage` decimal(10,0) DEFAULT NULL,
  PRIMARY KEY (`allocation_breakdown_id`),
  UNIQUE KEY `uk_allocation_breakdown` (`allocation_id`,`person_id`),
  UNIQUE KEY `allocation_breakdown_pk` (`allocation_breakdown_id`),
  KEY `alloc_alloc_breakdown_fk` (`allocation_id`),
  KEY `user_accts_alloc_breakdown_fk` (`person_id`),
  CONSTRAINT `fk_allocati_alloc_all_allocati` FOREIGN KEY (`allocation_id`) REFERENCES `hpcdb_allocations` (`allocation_id`),
  CONSTRAINT `fk_allocati_user_hpcdb_people` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_allocations` (
  `allocation_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`allocation_id`),
  UNIQUE KEY `allocations_pk` (`allocation_id`),
  KEY `allocations_resources_fk` (`resource_id`),
  KEY `allocations_ac_rs_idx` (`account_id`,`resource_id`),
  KEY `accounts_allocations_fk` (`account_id`),
  CONSTRAINT `fk_allocati_accounts__accounts` FOREIGN KEY (`account_id`) REFERENCES `hpcdb_accounts` (`account_id`),
  CONSTRAINT `fk_allocati_allocatio_resource` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_allocations_on_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_allocations_on_resources` (
  `allocation_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  PRIMARY KEY (`allocation_id`,`resource_id`),
  KEY `IDX_83494B7789329D25` (`resource_id`),
  KEY `IDX_83494B779C83F4B2` (`allocation_id`),
  CONSTRAINT `ar_alloc_fk` FOREIGN KEY (`allocation_id`) REFERENCES `hpcdb_allocations` (`allocation_id`),
  CONSTRAINT `ar_res_fk` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_email_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_email_addresses` (
  `email_address_id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `email_address` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`email_address_id`),
  UNIQUE KEY `email_addresses_uk` (`person_id`),
  UNIQUE KEY `email_addresses_pk` (`email_address_id`),
  CONSTRAINT `fk_email_ad_people_em_people` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_fields_of_science`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_fields_of_science` (
  `field_of_science_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `abbrev` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`field_of_science_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_fields_of_science_hierarchy`;
/*!50001 DROP VIEW IF EXISTS `hpcdb_fields_of_science_hierarchy`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `hpcdb_fields_of_science_hierarchy` (
  `field_of_science_id` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `parent_id` tinyint NOT NULL,
  `parent_description` tinyint NOT NULL,
  `directorate_id` tinyint NOT NULL,
  `directorate_description` tinyint NOT NULL,
  `directorate_abbrev` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `hpcdb_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_jobs` (
  `job_id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `allocation_breakdown_id` int(11) DEFAULT NULL,
  `allocation_id` int(11) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `local_jobid` int(11) NOT NULL,
  `local_job_array_index` int(11) NOT NULL,
  `local_job_id_raw` int(11) DEFAULT NULL,
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `submit_time` int(10) unsigned NOT NULL,
  `eligible_time` int(10) unsigned DEFAULT NULL,
  `wallduration` int(11) NOT NULL,
  `exit_code` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `exit_state` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `jobname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nodecount` int(11) NOT NULL,
  `processors` int(11) DEFAULT NULL,
  `cpu_req` int(10) unsigned DEFAULT NULL,
  `mem_req` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timelimit` int(10) unsigned DEFAULT NULL,
  `node_list` mediumtext COLLATE utf8_unicode_ci,
  `queue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid_number` int(10) unsigned DEFAULT NULL,
  `groupname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gid_number` int(10) unsigned DEFAULT NULL,
  `ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`job_id`),
  UNIQUE KEY `uk_jobs` (`resource_id`,`local_jobid`,`local_job_array_index`,`submit_time`),
  KEY `jobs_account_idx` (`account_id`),
  KEY `jobs_allocation_idx` (`allocation_id`),
  KEY `jobs_hpcdb_res_time_idx` (`account_id`,`resource_id`,`submit_time`,`end_time`),
  KEY `jobs_person_idx` (`person_id`),
  KEY `IDX_2EBE1C0289329D25` (`resource_id`),
  KEY `IDX_2EBE1C021E990699` (`allocation_breakdown_id`),
  KEY `jobs_username_person_resource_idx` (`username`,`person_id`,`resource_id`),
  KEY `jobs_end_time_idx` (`end_time`),
  CONSTRAINT `fk_jobs_fk1` FOREIGN KEY (`account_id`) REFERENCES `hpcdb_accounts` (`account_id`),
  CONSTRAINT `fk_jobs_fk2` FOREIGN KEY (`allocation_id`) REFERENCES `hpcdb_allocations` (`allocation_id`),
  CONSTRAINT `fk_jobs_fk3` FOREIGN KEY (`allocation_breakdown_id`) REFERENCES `hpcdb_allocation_breakdown` (`allocation_breakdown_id`),
  CONSTRAINT `fk_jobs_fk5` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`),
  CONSTRAINT `fk_jobs_fk6` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_organizations` (
  `organization_id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_abbrev` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`organization_id`),
  UNIQUE KEY `org_name_uk` (`organization_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_people` (
  `person_id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `prefix` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `middle_name` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `department` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ts` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `people_pk` (`person_id`),
  KEY `people_last_name` (`last_name`),
  KEY `people_orgs_fk` (`organization_id`),
  CONSTRAINT `fk_people_people_or_organiza` FOREIGN KEY (`organization_id`) REFERENCES `hpcdb_organizations` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_people_on_accounts_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_people_on_accounts_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C3714113217BBB47` (`person_id`),
  KEY `IDX_C371411389329D25` (`resource_id`),
  KEY `IDX_C37141139B6B5FBA` (`account_id`),
  CONSTRAINT `poah_fk1` FOREIGN KEY (`account_id`) REFERENCES `hpcdb_accounts` (`account_id`),
  CONSTRAINT `poah_fk2` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`),
  CONSTRAINT `poah_fk3` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_principal_investigators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_principal_investigators` (
  `person_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  PRIMARY KEY (`person_id`,`request_id`),
  UNIQUE KEY `principal_investigators_pk` (`person_id`,`request_id`),
  KEY `principal_investigators2_fk` (`request_id`),
  KEY `principal_investigators_fk` (`person_id`),
  CONSTRAINT `fk_principa_principal_people` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`),
  CONSTRAINT `fk_principa_principal_requests` FOREIGN KEY (`request_id`) REFERENCES `hpcdb_requests` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `primary_fos_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `requests_pk` (`request_id`),
  KEY `requests_div_of_science_fk` (`primary_fos_id`),
  KEY `accounts_requests_fk` (`account_id`),
  CONSTRAINT `fk_requests_accounts__accounts` FOREIGN KEY (`account_id`) REFERENCES `hpcdb_accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_resource_allocated`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_resource_allocated` (
  `resource_id` int(11) NOT NULL,
  `start_date_ts` int(11) NOT NULL,
  `end_date_ts` int(11) DEFAULT NULL,
  `percent` int(11) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_resource_specs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_resource_specs` (
  `resource_spec_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `start_date_ts` int(11) NOT NULL DEFAULT '0',
  `end_date_ts` int(11) DEFAULT NULL,
  `node_count` int(11) NOT NULL,
  `cpu_count` int(11) NOT NULL,
  `cpu_count_per_node` int(11) NOT NULL,
  `comments` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`resource_spec_id`),
  UNIQUE KEY `resource_spec_start_date` (`resource_id`,`start_date_ts`),
  CONSTRAINT `fk_resource` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_resource_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_resource_types` (
  `type_id` int(11) NOT NULL,
  `type_abbr` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type_desc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `rt_uk` (`type_abbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `resource_name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resource_code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `resource_description` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resource_shared_jobs` int(1) NOT NULL DEFAULT '0',
  `resource_timezone` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`resource_id`),
  UNIQUE KEY `resources_pk` (`resource_id`),
  UNIQUE KEY `ak_identifier_2_resource` (`resource_name`),
  KEY `org_resources_fk` (`organization_id`),
  KEY `IDX_3A9CF2E298EC6B7B` (`resource_type_id`),
  CONSTRAINT `fk_resource_org_resou_organiza` FOREIGN KEY (`organization_id`) REFERENCES `hpcdb_organizations` (`organization_id`),
  CONSTRAINT `fk_resource_res_type` FOREIGN KEY (`resource_type_id`) REFERENCES `hpcdb_resource_types` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hpcdb_system_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hpcdb_system_accounts` (
  `system_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `username` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `ts` int(10) unsigned NOT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`system_account_id`),
  UNIQUE KEY `system_accounts_pk` (`system_account_id`),
  KEY `resources_usernames_fk` (`resource_id`),
  KEY `people_user_accounts_fk` (`person_id`),
  CONSTRAINT `fk_system_a_people_us_people` FOREIGN KEY (`person_id`) REFERENCES `hpcdb_people` (`person_id`),
  CONSTRAINT `fk_system_a_resources_resource` FOREIGN KEY (`resource_id`) REFERENCES `hpcdb_resources` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schema_version_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_version_history` (
  `database_name` char(64) NOT NULL,
  `schema_version` char(64) NOT NULL,
  `action_datetime` datetime NOT NULL,
  `action_type` enum('created','upgraded') NOT NULL,
  `script_name` varchar(255) NOT NULL,
  PRIMARY KEY (`database_name`,`schema_version`,`action_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

