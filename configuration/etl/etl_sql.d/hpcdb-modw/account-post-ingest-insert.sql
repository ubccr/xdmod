INSERT IGNORE INTO `${DESTINATION_SCHEMA}`.`account` (
    id,
    parent_id,
    charge_number,
    creator_organization_id,
    granttype_id,
    long_name,
    short_name,
    order_id
) VALUES (
    -1,
    NULL,
    'Unknown',
    NULL,
    -1,
    'Unknown Project',
    'Unknown Project',
    -1
);
