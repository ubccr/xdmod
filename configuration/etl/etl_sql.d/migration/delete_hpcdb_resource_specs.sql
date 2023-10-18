DELETE
  hrs
FROM
  mod_hpcdb.hpcdb_resource_specs as hrs
JOIN
  mod_hpcdb.hpcdb_resources as r on r.resource_id = hrs.resource_id
JOIN
  mod_shredder.staging_resource_spec as srs on srs.resource = r.resource_code;
//

DELETE
  hra
FROM
  mod_hpcdb.hpcdb_resource_allocated as hra
JOIN
  mod_hpcdb.hpcdb_resources as r on r.resource_id = hra.resource_id
JOIN
  mod_shredder.staging_resource_spec as srs on srs.resource = r.resource_code;
//
