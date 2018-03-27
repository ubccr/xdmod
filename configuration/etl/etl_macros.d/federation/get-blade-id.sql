(
    SELECT
        federation_blade_id
    FROM
        ${dest}.federation_blades
    WHERE
        prefix = TRIM('-modw' FROM '${src}')
)
