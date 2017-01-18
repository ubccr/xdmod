INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT
        1 template_id,
        a.acl_id
    FROM ${DESTINATION_SCHEMA}.acls a
        JOIN ${DESTINATION_SCHEMA}.Roles r
            ON a.name = r.abbrev
    WHERE r.role_id = 1;

INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT
        1 template_id,
        a.acl_id
    FROM ${DESTINATION_SCHEMA}.acls a
        JOIN ${DESTINATION_SCHEMA}.Roles r
            ON a.name = r.abbrev
    WHERE r.role_id = 5;

INSERT INTO ${DESTINATION_SCHEMA}.ReportTemplateACL (template_id, acl_id)
    SELECT
        3 template_id,
        a.acl_id
    FROM ${DESTINATION_SCHEMA}.acls a
        JOIN ${DESTINATION_SCHEMA}.Roles r
            ON a.name = r.abbrev
    WHERE r.role_id = 2;
