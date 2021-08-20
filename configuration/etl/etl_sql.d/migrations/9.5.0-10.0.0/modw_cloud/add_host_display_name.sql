UPDATE
  modw_cloud.host as h
JOIN
  modw.resourcefact as rf on h.resource_id = rf.id
SET
  h.display = CONCAT(h.hostname, ' (', rf.name, ')')
