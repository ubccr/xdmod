
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
DROP TABLE IF EXISTS `account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account` (
  `id` int(11) NOT NULL COMMENT 'The id of the account record.',
  `parent_id` int(11) DEFAULT NULL COMMENT 'The id of the parent account record, if any.',
  `charge_number` varchar(200) NOT NULL COMMENT 'The charge number associated with the allocation.',
  `creator_organization_id` int(11) DEFAULT NULL COMMENT 'The id of the organization who created this account.',
  `granttype_id` int(11) NOT NULL,
  `long_name` varchar(500) DEFAULT NULL,
  `short_name` varchar(500) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_charge` (`charge_number`,`id`),
  KEY `fk_account_account1_idx` (`parent_id`),
  KEY `fk_account_organization1_idx` (`creator_organization_id`),
  KEY `fk_account_granttype1_idx` (`granttype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='This table has records for all the TeraGrid accounts.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocation` (
  `id` int(11) NOT NULL COMMENT 'The id of the allocation record.',
  `resource_id` int(11) NOT NULL COMMENT 'This is the resource that the allocation sus where assigned in relativity to. It doesnt mean that the allocation can be used to run on this resource. To use this allocation, there must be resources assigned to this allocation in the allocationonresource t',
  `account_id` int(11) NOT NULL COMMENT 'This is the id of the accoun that owns this allocation, usually belongs to the PI of the project.',
  `request_id` int(11) NOT NULL COMMENT 'The id of the request that resulted in this allocation.',
  `principalinvestigator_person_id` int(11) NOT NULL COMMENT 'The person id of the pi who owns this allocation.',
  `fos_id` int(11) NOT NULL,
  `boardtype_id` int(11) NOT NULL,
  `initial_allocation` decimal(15,2) NOT NULL COMMENT 'The initial amount of the allocation in SUs.',
  `initial_start_date` date NOT NULL COMMENT 'The initial start date of the allocation.',
  `initial_start_date_ts` int(14) NOT NULL,
  `initial_end_date` date NOT NULL COMMENT 'The initial assumed end of the allocation.',
  `base_allocation` decimal(15,2) NOT NULL COMMENT 'The current amount of the allocation, which is the initial modified by any amounts since it was initiated. Allocations can be modified by transfers or not, supplements awarded.',
  `remaining_allocation` decimal(18,4) NOT NULL COMMENT 'This is the remaning amount of the allocation. Negative values mean they’ve consumed more SUs than they’ve been allocated. This may happen for a number of reasons, the most common being the project submits final job(s) when they are still “in the black” b',
  `end_date` date NOT NULL COMMENT 'This is the actual date the allocation was actually ended. ',
  `end_date_ts` int(14) NOT NULL,
  `allocation_type_id` int(11) DEFAULT NULL COMMENT 'The type of the allocation (extension, supplement, transfer, new, renewal, advance, adjustment)',
  `charge_number` varchar(200) DEFAULT NULL,
  `conversion_factor` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `xd_su_per_hour` decimal(15,2) NOT NULL DEFAULT '0.00',
  `long_name` varchar(500) DEFAULT NULL,
  `short_name` varchar(500) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `allocation_charge_number` (`charge_number`),
  KEY `aggregation_index` (`account_id`,`id`,`initial_start_date_ts`,`end_date_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Holds allocation records.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocationadjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocationadjustment` (
  `allocation_resource_id` int(11) NOT NULL COMMENT 'The id of the resource to adjust sus to (ie. TeraGrid roaming)',
  `site_resource_id` int(11) NOT NULL COMMENT 'The id of the resource to adjust su from (ie. The resouce the job ran on and the sus recorded for that job)',
  `conversion_factor` double NOT NULL COMMENT 'The multiplier to apply to the sus of the job we are trying to adjust/normlize.',
  `start_date` date NOT NULL COMMENT 'The date this adjustment factor goes in effect.',
  `end_date` date DEFAULT NULL COMMENT 'The date  this adjustment factor ends, if null,  it is still active. ',
  PRIMARY KEY (`start_date`,`site_resource_id`,`allocation_resource_id`),
  KEY `fk_allocationadjustment_resourcefact1_idx` (`allocation_resource_id`),
  KEY `fk_allocationadjustment_resourcefact2_idx` (`site_resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='SU adjustment to TeraGrid Roaming.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocationbreakdown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocationbreakdown` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `person_id` int(11) NOT NULL COMMENT 'The id of the person who gets a part of the allocation.',
  `allocation_id` int(11) NOT NULL COMMENT 'The id of the allocation the person can use.',
  `percentage` decimal(5,2) DEFAULT NULL COMMENT 'The percentage [0-100] of the allocation that the person can use. ',
  `alloc_limit` decimal(18,4) DEFAULT NULL COMMENT 'Usually set to the base_allocation of the allocation.',
  `used_allocation` decimal(18,4) DEFAULT NULL COMMENT 'How much the user has used in Sus.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `alloc_pid` (`allocation_id`,`person_id`),
  KEY `fk_allocationbreakdown_allocation1_idx` (`allocation_id`),
  KEY `fk_allocationbreakdown_person1_idx` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Assigns people to a part of an allocation.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocationonresource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocationonresource` (
  `allocation_id` int(11) NOT NULL COMMENT 'The id of the allocation record.',
  `resource_id` int(11) NOT NULL COMMENT 'The id of the resource that is allowed to use the allocation. In other words the allocation listed can be used by running jobs on this resource, depending on the allocation_state_id.',
  `allocation_state_id` int(11) NOT NULL COMMENT 'The state of the allocation.',
  PRIMARY KEY (`resource_id`,`allocation_id`),
  KEY `fk_allocation_on_resource_allocation1_idx` (`allocation_id`),
  KEY `fk_allocation_on_resource_resourcefact1_idx` (`resource_id`),
  KEY `fk_allocation_on_resource_allocation_state1_idx` (`allocation_state_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='state of alloc wrt resources.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocationstate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocationstate` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `name` varchar(32) DEFAULT NULL COMMENT 'The description of the state.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `NAME_UNIQUE` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='state of an allocation';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `boardtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boardtype` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `description` varchar(100) DEFAULT NULL COMMENT 'The description of the board type.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `desc_UNIQUE` (`description`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The various board types related to each requests';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compliance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance` (
  `timeframe` varchar(100) DEFAULT NULL,
  `start_date` char(10) DEFAULT NULL,
  `end_date` char(10) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `job_records` int(11) DEFAULT NULL,
  `processor_count_supplied` int(11) DEFAULT NULL,
  `local_sus_supplied` int(11) DEFAULT NULL,
  `job_name_supplied` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compliance_compute_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_compute_resources` (
  `timestamp` varchar(10) DEFAULT NULL,
  `rdr_timestamp` varchar(100) DEFAULT NULL,
  `resource_id` varchar(100) DEFAULT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `OperatingSystem` varchar(100) DEFAULT NULL,
  `PeakTeraflops` varchar(100) DEFAULT NULL,
  `MemoryPerCPUGB` varchar(100) DEFAULT NULL,
  `CPUType` varchar(100) DEFAULT NULL,
  `CPUSpeedGhz` varchar(100) DEFAULT NULL,
  `CPUCountPerNode` varchar(100) DEFAULT NULL,
  `NodeCount` varchar(100) DEFAULT NULL,
  `Interconnect` varchar(100) DEFAULT NULL,
  `DiskSizeTB` varchar(100) DEFAULT NULL,
  `tg_conversion_factor` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compliance_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_resources` (
  `timestamp` varchar(10) DEFAULT NULL,
  `rdr_timestamp` varchar(50) DEFAULT NULL,
  `resource_name` varchar(100) DEFAULT NULL,
  `resource_id` varchar(20) DEFAULT NULL,
  `site_id` varchar(100) DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `country` (
  `id` int(11) NOT NULL COMMENT 'The id of the record',
  `name` varchar(400) DEFAULT NULL COMMENT 'The name of the country',
  `nsf_code` varchar(200) DEFAULT NULL COMMENT 'The NSF code for the country',
  `is_reconciled` tinyint(1) DEFAULT '0' COMMENT 'whether this record is reconciled or not, whatever that means.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nsf_code_UNIQUE` (`nsf_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Countries of Earth';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `days` (
  `id` int(10) unsigned NOT NULL COMMENT 'The id of the day record.',
  `year` smallint(5) unsigned NOT NULL COMMENT 'The year.',
  `day` smallint(5) unsigned NOT NULL COMMENT 'The day of year starting at 1.',
  `day_start` datetime NOT NULL COMMENT 'The datetime of the start of this day down to the second.',
  `day_end` datetime NOT NULL COMMENT 'the end datetime of this day down to the second.',
  `hours` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The number of hours in this day. Could be less than 24 in case the last job record fell in the middle of this day.',
  `seconds` INT(10) UNSIGNED NOT NULL COMMENT 'number of seconds n the day',
  `day_start_ts` int(10) unsigned NOT NULL COMMENT 'The start in epochs.',
  `day_end_ts` int(10) unsigned NOT NULL COMMENT 'The end in epochs.',
  `day_middle_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `days_pk2` (`day_start`,`day_end`,`day`,`year`),
  UNIQUE KEY `days_yd` (`year`,`day`),
  KEY `days_index` (`id`,`seconds`,`day_start_ts`,`day_end_ts`),
  KEY `days_index2` (`id`,`day_start_ts`,`day_middle_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='autogen - one rec for each day of TG.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fieldofscience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fieldofscience` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `parent_id` int(11) DEFAULT NULL COMMENT 'The parent of this field of science, if NULL this is an NSF Directorate.',
  `description` varchar(200) DEFAULT NULL COMMENT 'The description of this field of science.',
  `fos_nsf_id` int(11) DEFAULT NULL COMMENT 'The nsf id for this field of science.',
  `fos_nsf_abbrev` varchar(10) DEFAULT NULL COMMENT 'The nsf abbreviation.',
  `directorate_fos_id` int(11) DEFAULT NULL COMMENT 'The id of the NSF directorate of this field of science.',
  PRIMARY KEY (`id`),
  KEY `fk_science_science1_idx` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The various fields of science.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fieldofscience_hierarchy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fieldofscience_hierarchy` (
  `id` int(11) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `description2` varchar(200) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `parent_description` varchar(200) DEFAULT NULL,
  `directorate_id` int(11) DEFAULT NULL,
  `directorate_description` varchar(200) DEFAULT NULL,
  `directorate_abbrev` varchar(100) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `division_description` varchar(200) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fos_h_directorate_id` (`directorate_id`),
  KEY `fos_h_parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gatewayperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gatewayperson` (
  `person_id` int(11) NOT NULL,
  `long_name` varchar(400) DEFAULT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `granttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `granttype` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gridresource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gridresource` (
  `grid_resource_id` int(11) NOT NULL COMMENT 'The id of the grid resource.',
  `resource_id` int(11) NOT NULL COMMENT 'The id of a resource that is in the grid referred to by grid_resource_id.',
  `start_date` date NOT NULL COMMENT 'The date the resource became a part of the grid.',
  `end_date` date DEFAULT NULL COMMENT 'The date the resource stopped being a part of the grid.',
  PRIMARY KEY (`grid_resource_id`,`resource_id`),
  KEY `fk_gridresource_resource2_idx` (`resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Defines the child resources of grid resources.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_id` (`resource_id`,`hostname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_times` (
  `id` int(4) NOT NULL,
  `min_duration` int(11) DEFAULT NULL,
  `max_duration` int(11) DEFAULT NULL,
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `times` (`min_duration`,`max_duration`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobfact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobfact` (
  `job_id` int(11) NOT NULL COMMENT 'The id of the job record.',
  `local_jobid` int(11) NOT NULL,
  `local_job_array_index` int(11) NOT NULL,
  `local_job_id_raw` int(11) DEFAULT NULL COMMENT 'The local raw id of the job.',
  `person_id` int(11) NOT NULL COMMENT 'The id of the person who ran the job.',
  `person_organization_id` int(11) NOT NULL COMMENT 'The person''s organization.',
  `person_nsfstatuscode_id` int(11) NOT NULL COMMENT 'The person''s NSF status.',
  `organization_id` int(11) NOT NULL COMMENT 'The organization of the resource the job ran on.',
  `organizationtype_id` int(11) NOT NULL COMMENT 'The type of the organization the job ran on.',
  `state_id` int(11) NOT NULL COMMENT 'The state the resourse is in.',
  `country_id` int(11) NOT NULL COMMENT 'The country the resource is in.',
  `resource_id` int(11) NOT NULL COMMENT 'The id of the resource the job ran on.',
  `resourcetype_id` int(11) NOT NULL COMMENT 'The type of the resource the job ran on.',
  `queue_id` char(50) NOT NULL COMMENT 'The queue on the resource the job ran on.',
  `fos_id` int(11) NOT NULL COMMENT 'The field of science of the project pertaining to the job.',
  `account_id` int(11) NOT NULL COMMENT 'The account id of the allocation the job ran on.',
  `allocation_id` int(11) NOT NULL COMMENT 'The allocation record that funds this job.',
  `principalinvestigator_person_id` int(11) NOT NULL COMMENT 'The PI of the project''s person id.',
  `piperson_organization_id` int(11) NOT NULL,
  `systemaccount_id` int(11) NOT NULL COMMENT 'The id of the system account record related to this job.',
  `start_time` datetime DEFAULT NULL COMMENT 'The start time of the job.',
  `end_time` datetime DEFAULT NULL COMMENT 'The end time of the job.',
  `submit_time` datetime DEFAULT NULL COMMENT 'The time the job was sumitted to the queue.',
  `eligible_time` datetime DEFAULT NULL COMMENT 'The time the job became eligible to run.',
  `wallduration` int(11) NOT NULL COMMENT 'The duration in seconds. If the duration between start time and end time is less than the wallduration then start time minus end time is used, else the wallduration value from the TGcDB is used.',
  `waitduration` int(11) DEFAULT NULL COMMENT 'The wait time in seconds.',
  `name` varchar(200) DEFAULT NULL COMMENT 'The name of the job.',
  `nodecount` int(11) NOT NULL COMMENT 'The job''s nodecount.',
  `processors` int(11) NOT NULL COMMENT 'Number of processors used. If original procesors reported is 0 or null then processors is equal to nodecount*processors per node of the resource. ',
  `local_charge` decimal(18,3) DEFAULT NULL COMMENT 'Normalized to TeraGrid roaming, charge before being adjusted by RP.',
  `adjusted_charge` decimal(18,3) DEFAULT NULL COMMENT 'The normalized charge after being adjusted by RP.',
  `cpu_time` bigint(42) DEFAULT NULL COMMENT 'The CPU time in CPU seconds.',
  `processors_original` int(11) DEFAULT NULL COMMENT 'The number of processors reported before being transformed.',
  `start_time_ts` int(14) DEFAULT NULL COMMENT 'The start time in epochs.',
  `end_time_ts` int(14) DEFAULT NULL COMMENT 'The end time in epochs.',
  `submit_time_ts` int(14) DEFAULT NULL COMMENT 'The submit time in epochs.',
  `eligible_time_ts` int(11) DEFAULT NULL COMMENT 'The eligible time in Unix time.',
  `local_charge_nu` decimal(18,3) DEFAULT NULL COMMENT 'Local charge in NUs.',
  `adjusted_charge_nu` decimal(18,3) DEFAULT NULL COMMENT 'Adjusted charge in NUs',
  `request_id` int(11) NOT NULL COMMENT 'The request id for the allocation of this job.',
  `allocation_resource_id` int(11) DEFAULT NULL COMMENT 'The resouce which the SUs of the allocation were normalized against.',
  `conversion_factor` double DEFAULT NULL COMMENT 'The factor by which to multiply the local_charge and adjusted_charge to normalize against TeraGrid Roaming.',
  `group_name` varchar(255) DEFAULT NULL COMMENT 'The name of the group that ran the job.',
  `gid_number` int(10) unsigned DEFAULT NULL COMMENT 'The GID of the group that ran the job.',
  `uid_number` int(10) unsigned DEFAULT NULL COMMENT 'The UID of the user that ran the job.',
  `exit_code` varchar(32) DEFAULT NULL COMMENT 'The code that the job exited with.',
  `exit_state` varchar(32) DEFAULT NULL COMMENT 'The state of the job when it completed.',
  `cpu_req` int(10) unsigned DEFAULT NULL COMMENT 'The number of CPUs required by the job.',
  `mem_req` varchar(32) DEFAULT NULL COMMENT 'The amount of memory required by the job.',
  `timelimit` int(10) unsigned DEFAULT NULL COMMENT 'The time limit of the job in seconds.',
  PRIMARY KEY (`job_id`),
  KEY `index_jobfact_local_jobid` (`local_jobid`,`local_job_array_index`,`resource_id`),
  KEY `aggregation_index` (`end_time_ts`,`start_time_ts`),
  KEY `index_start_time_resource_id` (`start_time_ts`,`resource_id`),
  KEY `index_supremm_lookup` (`local_job_id_raw`,`resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1 COMMENT='Consolidated details about every job on TG.';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `jobfact_AINS` AFTER INSERT ON `jobfact` FOR EACH ROW
BEGIN
  INSERT INTO jobfactstatus (job_id, aggregated_day, aggregated_month, aggregated_quarter, aggregated_year)
  VALUES (NEW.job_id, 0,0, 0, 0)
  ON DUPLICATE KEY UPDATE aggregated_day = 0, aggregated_month = 0, aggregated_quarter = 0, aggregated_year = 0;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `jobfact_AUPD` AFTER UPDATE ON `jobfact` FOR EACH ROW
BEGIN
  INSERT INTO jobfactstatus (job_id, aggregated_day, aggregated_month, aggregated_quarter, aggregated_year)
  VALUES (NEW.job_id, 0,0, 0, 0)
  ON DUPLICATE KEY UPDATE aggregated_day = 0, aggregated_month = 0, aggregated_quarter = 0, aggregated_year = 0;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `jobfact_BDEL` BEFORE DELETE ON `jobfact` FOR EACH ROW
BEGIN
  DELETE FROM jobfactstatus WHERE job_id = OLD.job_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `jobfactstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobfactstatus` (
  `job_id` int(11) NOT NULL,
  `aggregated_day` bit(1) NOT NULL DEFAULT b'0',
  `aggregated_month` bit(1) NOT NULL DEFAULT b'0',
  `aggregated_quarter` bit(1) NOT NULL DEFAULT b'0',
  `aggregated_year` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`job_id`),
  KEY `days` (`aggregated_day`),
  KEY `months` (`aggregated_month`),
  KEY `quarters` (`aggregated_quarter`),
  KEY `years` (`aggregated_year`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobhosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobhosts` (
  `job_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`job_id`,`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `minmaxdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `minmaxdate` (
  `min_job_date` datetime DEFAULT NULL,
  `max_job_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `months` (
  `id` int(10) unsigned NOT NULL COMMENT 'The id of the month record.',
  `year` smallint(5) unsigned NOT NULL COMMENT 'The year of the month.',
  `month` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The month of the year. Starts at 1.',
  `month_start` datetime NOT NULL COMMENT 'The datetime start of the month down to the second.',
  `month_end` datetime NOT NULL COMMENT 'The month end datetime down to the second.',
  `hours` smallint(5) unsigned NOT NULL COMMENT 'The number of hours in this month. This is variable based on duration of the month. Also in case the last job record fell in the middle of this month it will be somewhere between 1 and 31.',
  `seconds` int(10) unsigned NOT NULL COMMENT 'The number of seconds in this month. The last month might be partial.',
  `month_start_ts` int(10) unsigned NOT NULL COMMENT 'The start timestamp of this month in epochs.',
  `month_end_ts` int(10) unsigned NOT NULL COMMENT 'The end of this month in epochs. May be less than expected if the end of the last job fell during this month. ',
  `month_middle_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `month_ym` (`year`,`month`),
  UNIQUE KEY `months_pk2` (`year`,`month`,`month_start`,`month_end`),
  KEY `month_index` (`id`,`seconds`,`month_start_ts`,`month_end_ts`),
  KEY `month_index2` (`id`,`month_start_ts`,`month_middle_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='autogen - one rec for each month of TG operation.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nodecount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodecount` (
  `id` int(11) NOT NULL,
  `nodes` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nodes_UNIQUE` (`nodes`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nsfstatuscode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nsfstatuscode` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `code` varchar(10) NOT NULL COMMENT 'The short name of the NSF status code.',
  `name` varchar(100) NOT NULL COMMENT 'The description of the code.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The NSF status of a person.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `organizationtype_id` int(11) DEFAULT NULL COMMENT 'The type of the organization.',
  `abbrev` varchar(100) DEFAULT NULL COMMENT 'Abbreviated name.',
  `name` varchar(300) DEFAULT NULL COMMENT 'Long name for this organization.',
  `url` varchar(500) DEFAULT NULL COMMENT 'The internet URL.',
  `phone` varchar(30) DEFAULT NULL COMMENT 'Phone number.',
  `nsf_org_code` varchar(45) DEFAULT NULL COMMENT 'NSF code for this organization.',
  `is_reconciled` tinyint(1) DEFAULT '0' COMMENT 'Whether this record is reconciled.',
  `amie_name` varchar(6) DEFAULT NULL COMMENT 'The amie name.',
  `country_id` int(11) DEFAULT NULL COMMENT 'The country this organization is in.',
  `state_id` int(11) DEFAULT NULL COMMENT 'The state this organization is in.',
  `latitude` decimal(13,10) DEFAULT NULL COMMENT 'The latitude of the organization.',
  `longitude` decimal(13,10) DEFAULT NULL COMMENT 'The longitude of the organization.',
  `short_name` varchar(300) DEFAULT NULL,
  `long_name` varchar(300) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `nsf_org_code_UNIQUE` (`nsf_org_code`),
  UNIQUE KEY `amie_name_UNIQUE` (`amie_name`),
  KEY `fk_organization_country1_idx` (`country_id`),
  KEY `fk_organization_state1_idx` (`state_id`),
  KEY `fk_organization_organizationtype1_idx` (`organizationtype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The various organization pertaining to TG.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `organizationtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizationtype` (
  `id` int(11) NOT NULL,
  `type` varchar(300) DEFAULT NULL,
  `nsf_org_type_code` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_UNIQUE` (`type`),
  UNIQUE KEY `nsf_org_type_code_UNIQUE` (`nsf_org_type_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peopleonaccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peopleonaccount` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `allocationstate_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `start_time_ts` int(11) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `end_time_ts` int(11) DEFAULT NULL,
  `comments` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aggregation_index` (`resource_id`,`account_id`,`person_id`,`start_time_ts`,`end_time_ts`,`allocationstate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peopleunderpi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peopleunderpi` (
  `principalinvestigator_person_id` int(11) NOT NULL,
  `person_id` varchar(45) NOT NULL,
  PRIMARY KEY (`principalinvestigator_person_id`,`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `person` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `nsfstatuscode_id` int(11) NOT NULL,
  `prefix` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `birth_month` int(11) DEFAULT NULL,
  `birth_day` int(11) DEFAULT NULL,
  `department` varchar(300) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `is_reconciled` tinyint(1) DEFAULT '0',
  `citizenship_country_id` int(11) DEFAULT NULL,
  `email_address` varchar(200) DEFAULT NULL,
  `ts` datetime DEFAULT NULL,
  `ts_ts` int(11) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL COMMENT 'links to allocationstate',
  `long_name` varchar(700) DEFAULT NULL,
  `short_name` varchar(101) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_last_name` (`last_name`),
  KEY `aggregation_index` (`status`,`id`,`ts_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `piperson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `piperson` (
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `long_name` varchar(400) DEFAULT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `principalinvestigator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `principalinvestigator` (
  `person_id` int(11) NOT NULL COMMENT 'The id of the person of the PI.',
  `request_id` int(11) NOT NULL COMMENT 'The request id.',
  PRIMARY KEY (`person_id`,`request_id`),
  KEY `fk_princialinvestigator_person1_idx` (`person_id`),
  KEY `fk_princialinvestigator_request1_idx` (`request_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Only PIs are allowed to make requests.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `processor_buckets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `processor_buckets` (
  `id` int(4) NOT NULL,
  `min_processors` int(11) DEFAULT NULL,
  `max_processors` int(11) DEFAULT NULL,
  `description` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `proc` (`min_processors`,`max_processors`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quarters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quarters` (
  `id` int(10) unsigned NOT NULL COMMENT 'The id of the quarter record.',
  `year` smallint(5) unsigned NOT NULL COMMENT 'The year of the record.',
  `quarter` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The quarter of the year [1-4]',
  `quarter_start` datetime NOT NULL COMMENT 'The start datetime of the quarter.',
  `quarter_end` datetime NOT NULL COMMENT 'The end datetime of the quarter. ',
  `hours` smallint(5) unsigned NOT NULL COMMENT 'The number of hours in the quarter.',
  `seconds` int(10) unsigned NOT NULL COMMENT 'The number of seconds in the quarter.',
  `quarter_start_ts` INT(10) UNSIGNED NOT NULL COMMENT 'The start timestamp of the quarter in epochs.',
  `quarter_end_ts` int(10) unsigned NOT NULL COMMENT 'The end timestamp of the quarter in epochs. If the last job fell during this quarter, the end of the quarter will be abrupt. Hence a partial quarter. ',
  `quarter_middle_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quarters_pk2` (`year`,`quarter`,`quarter_start`,`quarter_end`),
  UNIQUE KEY `quarter_yq` (`year`,`quarter`),
  KEY `quarter_index` (`id`,`seconds`,`quarter_start_ts`,`quarter_end_ts`),
  KEY `quarter_index2` (`id`,`quarter_start_ts`,`quarter_middle_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='autogen - one rec for each quarter of TG operation.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queue` (
  `id` char(50) NOT NULL DEFAULT '' COMMENT 'The name of the queue.',
  `resource_id` int(11) NOT NULL COMMENT 'The resource this queue belongs to.',
  PRIMARY KEY (`id`,`resource_id`),
  KEY `fk_Queue_Resource_idx` (`resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The queue names of the different resources.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request` (
  `id` int(11) NOT NULL COMMENT 'The id of the request record.',
  `request_type_id` int(11) NOT NULL COMMENT 'The type of the request. Links to transactiontype table.',
  `primary_fos_id` int(11) NOT NULL COMMENT 'The field of science associated with  the project of this request.',
  `account_id` int(11) NOT NULL COMMENT 'The account pertaining to this request.',
  `proposal_title` varchar(1000) DEFAULT NULL COMMENT 'The title of the proposal for the allocation request.',
  `expedite` tinyint(1) DEFAULT NULL COMMENT 'The date this request expires.',
  `project_title` varchar(300) DEFAULT NULL COMMENT 'The project title related to this request.',
  `primary_reviewer` varchar(100) DEFAULT NULL COMMENT 'The name of the primary reviewer.',
  `proposal_number` varchar(20) DEFAULT NULL COMMENT 'The number of the proposal  of the project.',
  `grant_number` varchar(200) NOT NULL COMMENT 'The grant number.',
  `comments` varchar(2000) DEFAULT NULL COMMENT 'Any comments.',
  `start_date` date NOT NULL COMMENT 'The start date of the request.',
  `end_date` date NOT NULL COMMENT 'The end of the request.',
  `boardtype_id` int(11) DEFAULT NULL COMMENT 'The board type.',
  PRIMARY KEY (`id`),
  KEY `index6` (`grant_number`),
  KEY `fk_request_transactiontype1_idx` (`request_type_id`),
  KEY `fk_request_fieldofscience1_idx` (`primary_fos_id`),
  KEY `fk_request_boardtype1_idx` (`boardtype_id`),
  KEY `fk_request_account1_idx` (`account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Requests by PIs for allocations on TG.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resource_allocated`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource_allocated` (
  `resource_id` int(11) NOT NULL,
  `start_date_ts` int(11) NOT NULL,
  `end_date_ts` int(11) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `percent` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`resource_id`,`start_date_ts`),
  KEY `unq` (`name`,`start_date_ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resourcefact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resourcefact` (
  `id` int(11) NOT NULL COMMENT 'The id of the resource record',
  `resourcetype_id` int(11) DEFAULT NULL COMMENT 'The resource type id.',
  `organization_id` int(11) DEFAULT NULL COMMENT 'The organization of the resource.',
  `name` varchar(200) DEFAULT NULL COMMENT 'The name of the resource.',
  `code` varchar(64) NOT NULL COMMENT 'The short name of the resource.',
  `description` varchar(1000) DEFAULT NULL COMMENT 'The description of the resource.',
  `start_date` datetime DEFAULT NULL COMMENT 'The date the resource was put into commission.',
  `start_date_ts` int(14) NOT NULL DEFAULT '0',
  `end_date` datetime DEFAULT NULL COMMENT 'The end date of the resource.',
  `end_date_ts` int(14) DEFAULT NULL,
  `shared_jobs` int(1) NOT NULL DEFAULT '0',
  `timezone` varchar(30) NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`id`,`start_date_ts`),
  KEY `aggregation_index` (`resourcetype_id`,`id`),
  KEY `fk_resource_resourcetype1_idx` (`resourcetype_id`),
  KEY `fk_Resource_Organization1_idx` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Information about resources.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resourcespecs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resourcetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resourcetype` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `description` char(50) NOT NULL COMMENT 'The description of the resource type.',
  `abbrev` char(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The different types of resources.';
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
DROP TABLE IF EXISTS `serviceprovider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `serviceprovider` (
  `organization_id` int(11) NOT NULL,
  `long_name` varchar(400) DEFAULT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `state` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `abbrev` varchar(4200) DEFAULT NULL COMMENT 'The abbrevated name of the state.',
  `name` varchar(300) DEFAULT NULL COMMENT 'The name of the state.',
  `is_reconciled` tinyint(1) DEFAULT '0' COMMENT 'Whether its reconciled or not.',
  `epscor` tinyint(1) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL COMMENT 'The id of the country that pertains to this state.',
  PRIMARY KEY (`id`),
  KEY `fk_state_country1_idx` (`country_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='The states of the different Countries.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `systemaccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `systemaccount` (
  `id` int(11) NOT NULL COMMENT 'the id of the record',
  `person_id` int(11) NOT NULL COMMENT 'The person to whom this system account belongs',
  `resource_id` int(11) NOT NULL COMMENT 'The resource for which this is an account.',
  `username` varchar(30) NOT NULL COMMENT 'The username to log on to the resource.',
  `ts` timestamp NULL DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `systemaccount_username` (`username`),
  KEY `index_resource_username_id` (`resource_id`,`username`,`id`),
  KEY `fk_systemaccount_person1_idx` (`person_id`),
  KEY `fk_systemaccount_resourcefact1_idx` (`resource_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='User''s accounts on various resources.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactiontype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactiontype` (
  `id` int(11) NOT NULL COMMENT 'The id of the record.',
  `name` varchar(200) NOT NULL COMMENT 'The name of the state.',
  `description` varchar(500) DEFAULT NULL COMMENT 'The description of the state.',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='state of allocation and request.';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `years` (
  `id` int(10) unsigned NOT NULL COMMENT 'The id of the year record.',
  `year` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The year of the record.',
  `year_start` datetime NOT NULL COMMENT 'The start datetime of the year',
  `year_end` datetime NOT NULL COMMENT 'The end datetime of the year',
  `hours` smallint(5) unsigned NOT NULL COMMENT 'The number of hours in the year.',
  `seconds` int(10) unsigned NOT NULL COMMENT 'The number of seconds in the year',
  `year_start_ts` int(10) unsigned NOT NULL COMMENT 'The start timestamp of the year in epochs.',
  `year_end_ts` int(10) unsigned NOT NULL COMMENT 'The end timestamp of the year in epochs. If the last job fell during this year the end of the year will be abrupt. Hence a partial year.',
  `year_middle_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_pk2` (`year`,`year_start`,`year_end`),
  UNIQUE KEY `year_yq` (`year`),
  KEY `year_index` (`id`,`seconds`,`year_start_ts`,`year_end_ts`),
  KEY `year_index2` (`id`,`year_start_ts`,`year_middle_ts`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='autogen - one rec for each year of TG operation.';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

--
-- Table structure for table `error_descriptions`
--

DROP TABLE IF EXISTS `error_descriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_descriptions` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
