LOCK TABLES `modw_aggregates`.`jobfact_by_day` WRITE;
ALTER TABLE `modw_aggregates`.`jobfact_by_day` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`jobfact_by_month` WRITE;
ALTER TABLE `modw_aggregates`.`jobfact_by_month` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`jobfact_by_quarter` WRITE;
ALTER TABLE `modw_aggregates`.`jobfact_by_quarter` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`jobfact_by_year` WRITE;
ALTER TABLE `modw_aggregates`.`jobfact_by_year` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`storagefact_by_day` WRITE;
ALTER TABLE `modw_aggregates`.`storagefact_by_day` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`storagefact_by_month` WRITE;
ALTER TABLE `modw_aggregates`.`storagefact_by_month` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`storagefact_by_quarter` WRITE;
ALTER TABLE `modw_aggregates`.`storagefact_by_quarter` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`storagefact_by_year` WRITE;
ALTER TABLE `modw_aggregates`.`storagefact_by_year` ENGINE=InnoDB;
UNLOCK TABLES;

LOCK TABLES `modw_aggregates`.`resourcespecsfact_by_day` WRITE;
ALTER TABLE `modw_aggregates`.`resourcespecsfact_by_day` ENGINE=InnoDB;

LOCK TABLES `modw_aggregates`.`resourcespecsfact_by_month` WRITE;
ALTER TABLE `modw_aggregates`.`resourcespecsfact_by_month` ENGINE=InnoDB;

LOCK TABLES `modw_aggregates`.`resourcespecsfact_by_quarter` WRITE;
ALTER TABLE `modw_aggregates`.`resourcespecsfact_by_quarter` ENGINE=InnoDB;

LOCK TABLES `modw_aggregates`.`resourcespecsfact_by_year` WRITE;
ALTER TABLE `modw_aggregates`.`resourcespecsfact_by_year` ENGINE=InnoDB;
UNLOCK TABLES;
