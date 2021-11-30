-- If you lock these tables for writing while converting to InnoDB the migration process times out.

ALTER TABLE `mod_logger`.`log_id_seq` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`log_level` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`log_table` ENGINE=InnoDB;
ALTER TABLE `mod_logger`.`schema_version_history` ENGINE=InnoDB;
