INSERT INTO ${DESTINATION_SCHEMA}.modules (name, display, enabled)
    SELECT inc.*
    FROM
        (
            SELECT
                'xdmod' name,
                'XDMoD' display,
                TRUE    enabled
        ) inc
        LEFT JOIN modules m
            ON m.name = inc.name
               AND m.display = inc.display
    WHERE
        m.module_id IS NULL;
