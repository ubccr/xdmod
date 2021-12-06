-- These tables cannot be locked for writing while converting to InnoDB as it
-- will cause a deadlock and the migration process will time out.

ALTER TABLE `mod_logger`.`log_id_seq` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`log_level` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`log_table` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`schema_version_history` ENGINE=InnoDB;
