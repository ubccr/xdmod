
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'default'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;

-- default

-- usr
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'usr'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;
-- usr

-- cd
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cd'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;
-- cd

-- pi
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pi'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;
-- pi

-- cs
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'cs'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;
-- cs

-- mgr
INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'mgr'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;
-- mgr

INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'none'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'fieldofscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'jobsize'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'jobwalltime'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'nodecount'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'nsfdirectorate'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'parentscience'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'pi'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'queue'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'resource'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'resource_type'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              False hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'person'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.acl_group_bys (acl_id, realm_id, group_by_id, visible)
    SELECT inc.*
    FROM (SELECT
              a.acl_id,
              r.realm_id,
              gb.group_by_id,
              True hide
          FROM ${DESTINATION_SCHEMA}.acls a, ${DESTINATION_SCHEMA}.realms r, ${DESTINATION_SCHEMA}.group_bys gb
          WHERE
              a.name = 'pub'
              AND gb.name = 'username'
              AND r.name = LOWER('Jobs')
              AND a.acl_id IS NOT NULL
              AND gb.group_by_id IS NOT NULL
              AND r.realm_id IS NOT NULL) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acl_group_bys cur
            ON cur.acl_id = inc.acl_id
               AND cur.realm_id = inc.realm_id
               AND cur.group_by_id = inc.group_by_id
               AND cur.visible = inc.hide
    WHERE cur.acl_group_by_id IS NULL;

