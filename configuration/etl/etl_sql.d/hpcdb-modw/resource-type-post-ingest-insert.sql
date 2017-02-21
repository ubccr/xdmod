INSERT INTO `${DESTINATION_SCHEMA}`.`resourcetype` (
    id,
    abbrev,
    description
) VALUES (
    0,
    'UNK',
    'Unknown'
) ON DUPLICATE KEY
UPDATE abbrev = 'UNK', description = 'Unknown'
