SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'//
INSERT IGNORE INTO ${DESTINATION_SCHEMA}.`staging_resource_type` (resource_type_id, resource_type_description, resource_type_abbrev)
VALUES ('0', 'Unknown Resource Type', 'UNK')//
