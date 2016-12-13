INSERT INTO `schema_version_history` VALUES ('modw', '5.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `resourcefact`
    ADD COLUMN `shared_jobs` INT(1) NOT NULL DEFAULT 0 AFTER `end_date_ts`,
    ADD COLUMN `timezone` VARCHAR(30) NOT NULL DEFAULT 'UTC' AFTER `shared_jobs`;

CREATE TABLE IF NOT EXISTS `resource_allocated` (
  `resource_id` int(11) NOT NULL,
  `start_date_ts` int(11) NOT NULL,
  `end_date_ts` int(11) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `percent` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`resource_id`,`start_date_ts`),
  KEY `unq` (`name`,`start_date_ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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

DELIMITER ;;

CREATE TRIGGER `jobfact_AINS` AFTER INSERT ON `jobfact` FOR EACH ROW
BEGIN
  INSERT INTO jobfactstatus (job_id, aggregated_day, aggregated_month, aggregated_quarter, aggregated_year)
  VALUES (NEW.job_id, 0,0, 0, 0)
  ON DUPLICATE KEY UPDATE aggregated_day = 0, aggregated_month = 0, aggregated_quarter = 0, aggregated_year = 0;
END ;;

CREATE TRIGGER `jobfact_AUPD` AFTER UPDATE ON `jobfact` FOR EACH ROW
BEGIN
  INSERT INTO jobfactstatus (job_id, aggregated_day, aggregated_month, aggregated_quarter, aggregated_year)
  VALUES (NEW.job_id, 0,0, 0, 0)
  ON DUPLICATE KEY UPDATE aggregated_day = 0, aggregated_month = 0, aggregated_quarter = 0, aggregated_year = 0;
END ;;

CREATE TRIGGER `jobfact_BDEL` BEFORE DELETE ON `jobfact` FOR EACH ROW
BEGIN
  DELETE FROM jobfactstatus WHERE job_id = OLD.job_id;
END ;;

DELIMITER ;

