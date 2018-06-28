CREATE OR REPLACE VIEW `hpcdb_fields_of_science_hierarchy` AS SELECT
    `fos`.`field_of_science_id` AS `field_of_science_id`,
    `fos`.`description` AS `description`,
    IF(
        ISNULL(`gp`.`field_of_science_id`),
        `fos`.`field_of_science_id`,
        COALESCE(`p`.`field_of_science_id`, `fos`.`field_of_science_id`)
    ) AS `parent_id`,
    IF(
        ISNULL(`gp`.`field_of_science_id`),
        `fos`.`description`,
        COALESCE(`p`.`description`, `fos`.`description`)
    ) AS `parent_description`,
    COALESCE(
        `gp`.`field_of_science_id`,
        `p`.`field_of_science_id`,
        `fos`.`field_of_science_id`
    ) AS `directorate_id`,
    COALESCE(
        `gp`.`description`,
        `p`.`description`,
        `fos`.`description`
    ) AS `directorate_description`,
    COALESCE(
        `gp`.`abbrev`,
        `p`.`abbrev`,
        `fos`.`abbrev`
    ) AS `directorate_abbrev`
FROM `hpcdb_fields_of_science` `fos`
LEFT JOIN `hpcdb_fields_of_science` `p`
    ON `fos`.`parent_id` = `p`.`field_of_science_id`
LEFT JOIN `hpcdb_fields_of_science` `gp`
    ON `p`.`parent_id` = `gp`.`field_of_science_id`//
