CREATE TABLE IF NOT EXISTS `resourceallocationfact_by_quarter` (
  `quarter_id` int(10) unsigned NOT NULL COMMENT 'DIMENSION: The id related to modw.quarters.',
  `year` smallint(5) unsigned NOT NULL COMMENT 'DIMENSION: The year of the quarter',
  `quarter` smallint(5) unsigned NOT NULL COMMENT 'DIMENSION: The quarter of the year.',
  `resource_id` int(11) NOT NULL COMMENT 'DIMENSION: The id of the resource that the allocation was made on.',
  `organization_id` int(11) NOT NULL COMMENT 'DIMENSION: The organization of the resource that the jobs ran on.',
  `alloc_date` date NOT NULL COMMENT 'The date associated with a resource allocation.',
  `xd_su_conversion_factor` double NOT NULL COMMENT 'The multiplier used to convert native SUs into XD SUs for a resource.',
  `available` int(11) NULL COMMENT 'The amount of native SUs available on a resource on an allocation date.',
  `requested` int(11) NULL COMMENT 'The amount of native SUs requested on a resource on an allocation date.',
  `recommended` char(50) NULL COMMENT 'The amount of native SUs awarded on a resource on an allocation date.',
  `awarded` int(11) NULL COMMENT 'The amount of native SUs recommended for allocation on a resource on an allocation date.',
  INDEX `index_period_value` (`quarter`),
  INDEX `index_period_id` (`quarter_id`),
  INDEX `index_year` (`year`),
  INDEX `index_organization_id` (`organization_id`),
  INDEX `index_resource_id` (`resource_id`)
) ENGINE = MyISAM COMMENT = 'Resource Allocation facts aggregated by quarter.';
