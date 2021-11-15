-- The `modw_cloud` aggregate tables cannot be changed to the InnoDB table engine
-- using an action that uses the ManageTable class. The ManageTable class does not
-- support the `table_prefix` option, and this prevents the aggregate tables from
-- being changed to InnoDB. Because of this, we will convert the aggregate tables
-- manually.

LOCK TABLES `modw_cloud`.`cloudfact_by_day` WRITE;
ALTER TABLE `modw_cloud`.`cloudfact_by_day` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_cloud`.`cloudfact_by_month` WRITE;
ALTER TABLE `modw_cloud`.`cloudfact_by_month` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_cloud`.`cloudfact_by_quarter` WRITE;
ALTER TABLE `modw_cloud`.`cloudfact_by_quarter` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_cloud`.`cloudfact_by_year` WRITE;
ALTER TABLE `modw_cloud`.`cloudfact_by_year` ENGINE=InnoDB;
UNLOCK TABLES;
