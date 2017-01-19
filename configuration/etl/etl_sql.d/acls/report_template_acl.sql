INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 1 template_id,
                 a.acl_id
             FROM acls a
                 JOIN Roles r
                     ON a.name = r.abbrev
             WHERE r.role_id = 1) inc
        LEFT JOIN ReportTemplateACL cur
            ON cur.template_id = inc.template_id
               AND cur.acl_id = inc.acl_id
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 1 template_id,
                 a.acl_id
             FROM acls a
                 JOIN Roles r
                     ON a.name = r.abbrev
             WHERE r.role_id = 5) inc
        LEFT JOIN ReportTemplateACL cur
            ON cur.template_id = inc.template_id
               AND cur.acl_id = inc.acl_id
    WHERE cur.acl_id IS NULL;


INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 3 template_id,
                 a.acl_id
             FROM acls a
                 JOIN Roles r
                     ON a.name = r.abbrev
             WHERE r.role_id = 2) inc
        LEFT JOIN ReportTemplateACL cur
            ON cur.template_id = inc.template_id
               AND cur.acl_id = inc.acl_id
    WHERE cur.acl_id IS NULL;
