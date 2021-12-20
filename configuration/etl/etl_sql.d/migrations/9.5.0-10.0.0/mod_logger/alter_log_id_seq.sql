-- This is to account for the table schema change in db/schema/mod_logger.sql.
ALTER TABLE `mod_logger`.`log_id_seq` MODIFY sequence int(11) NOT NULL;
