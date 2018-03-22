LOCK TABLES `error_descriptions` WRITE;
/*!40000 ALTER TABLE `error_descriptions` DISABLE KEYS */;
INSERT INTO `error_descriptions` VALUES (0,'Metric OK'),
(1,'Metric Missing: Collection Failed'),
(2,'Metric Missing: Not Available On This Host'),
(3,'Metric Disappeared During Job'),
(4,'Metric Missing: Unknown Reason'),
(5,'Metric Summarization Error'),
(6,'Metric Out Of Bounds'),
(7,'Metric Mapping Not Found'),
(8,'Mapping Function Error'),
(9,'Metric Ambiguous'),
(10,'Derived Field Query Failed'),
(11,'Metric Value Doesn\'t Fit Schema Type'),
(12,'Metric Counter Rolled Over'),
(13,'Metric Dropped Data Due to Jitter');
/*!40000 ALTER TABLE `error_descriptions` ENABLE KEYS */;
UNLOCK TABLES;

INSERT INTO `schema_version_history` VALUES ('modw', '8.0.0', NOW(), 'created', 'N/A');

INSERT INTO `person` (id, organization_id, nsfstatuscode_id, first_name, last_name, long_name, short_name, order_id) VALUES
  (-1, -1, -1, 'Unknown', 'Unknown', 'Unknown', 'Unknown', -1), (-2, -2, -2, 'unassociated', 'unassociated', 'unassociated', 'unassociated', -2);
