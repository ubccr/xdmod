DELETE
  rs
FROM
  modw.resourcespecs as rs
JOIN
  mod_hpcdb.hpcdb_resource_specs as hrs on hrs.resource_id = rs.resource_id;
//

DELETE
  ra
FROM
  modw.resource_allocated as ra
JOIN
  mod_hpcdb.hpcdb_resource_allocated as hra on hra.resource_id = ra.resource_id;
