UPDATE
  modw_cloud.account as a
JOIN
  modw.resourcefact as rf ON a.resource_id = rf.id
LEFT JOIN
  modw_cloud.staging_pi_to_project as pi ON a.display = pi.project_name AND pi.resource_name = rf.code
LEFT JOIN
  modw.account as acc ON pi.pi_name = acc.charge_number
LEFT JOIN
  modw.principalinvestigator as p ON acc.id = p.request_id
SET
  a.principalinvestigator_person_id = COALESCE(p.person_id, -1)
//
