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
