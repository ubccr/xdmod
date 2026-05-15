-- Drop any rows that have duplicate report_ids
START TRANSACTION//
CREATE TEMPORARY TABLE `tmp_uniquereports` (
  `report_id` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(1000) DEFAULT 'TAS Report',
  `derived_from` varchar(1000) DEFAULT NULL,
  `title` varchar(1000) DEFAULT 'TAS Report',
  `header` varchar(1000) DEFAULT NULL,
  `footer` varchar(1000) DEFAULT NULL,
  `format` enum('Pdf','Pptx','Doc','Xls','Html') NOT NULL DEFAULT 'Pdf',
  `schedule` enum('Once','Daily','Weekly','Monthly','Quarterly','Semi-annually','Annually') NOT NULL DEFAULT 'Once',
  `delivery` enum('Download','E-mail') NOT NULL DEFAULT 'E-mail',
  `selected` tinyint(1) NOT NULL DEFAULT 0,
  `charts_per_page` int(1) DEFAULT NULL,
  `active_role` varchar(30) DEFAULT NULL,
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE INDEX idx_report (report_id)
)//
INSERT IGNORE INTO `tmp_uniquereports` SELECT * FROM `moddb`.`Reports`//
DELETE FROM `moddb`.`Reports`//
INSERT INTO `moddb`.`Reports` SELECT * FROM `tmp_uniquereports`//
COMMIT//

-- Then rename any reports that have duplicate names
START TRANSACTION//
CREATE TEMPORARY TABLE `tmp_duplicatenames`
SELECT rr.user_id,
       rr.report_id,
       rr.name,
       ROW_NUMBER() OVER (PARTITION BY rr.user_id, rr.name ORDER BY rr.report_id) as duplicate_index
FROM `moddb`.`Reports` rr//
UPDATE `moddb`.`Reports` r, `tmp_duplicatenames` dup SET r.name = CONCAT(r.name, ' [duplicate # ', dup.duplicate_index, ']') WHERE dup.duplicate_index > 1 AND dup.report_id = r.report_id//

COMMIT//
