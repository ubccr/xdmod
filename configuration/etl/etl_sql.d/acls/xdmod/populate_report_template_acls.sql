-- =============================================================================
-- NAME:      populate_report_template_acls.sql
-- EXECUTION: once on installation / update
-- PURPOSE:   Ensure that the moddb.report_template_acls is populated. Make sure
--            that records are only included if we're able to translate their
--            role value to an acl value.
-- =============================================================================
INSERT INTO moddb.report_template_acls(report_template_id, acl_id)
SELECT inc.*
FROM (
  SELECT
    rta.template_id,
    a.acl_id
  FROM moddb.ReportTemplateACL rta
    JOIN moddb.Roles r ON r.role_id = rta.role_id
    LEFT JOIN moddb.acls a ON a.name = r.abbrev
  WHERE a.acl_id IS NOT NULL
) inc
  LEFT JOIN moddb.report_template_acls cur
  ON     cur.report_template_id = inc.template_id
     AND cur.acl_id = inc.acl_id
WHERE cur.report_template_acl_id IS NULL;