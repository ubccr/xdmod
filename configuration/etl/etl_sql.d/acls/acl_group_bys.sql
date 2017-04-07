
INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'default')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'default')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pub')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
False                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pub')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cd')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cd')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'mgr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'mgr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'usr')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'usr')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'pi')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'pi')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('ResourceAllocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('ResourceAllocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('ResourceAllocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('ResourceAllocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('ResourceAllocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('ResourceAllocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'po')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'po')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'gateway'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'gateway');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfstatus'
         AND r.name = LOWER('Jobs')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Jobs')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfstatus');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'allocation_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'allocation_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'board_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'board_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Allocations')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Allocations')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource_type'
         AND r.name = LOWER('Accounts')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Accounts')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('Requests')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('Requests')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'none'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'none');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'appclassmethod_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'appclassmethod_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'application'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'application');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'catastrophe_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'catastrophe_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpucv'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpucv');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'cpuuser'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'cpuuser');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'datasource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'datasource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'exit_status'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'exit_status');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'fieldofscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'fieldofscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'grant_type'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'grant_type');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'granted_pe'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'granted_pe');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'ibrxbyterate_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'ibrxbyterate_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobsize'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobsize');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'jobwalltime'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'jobwalltime');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'max_mem'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'max_mem');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'netdrv_lustre_rx_bucket_id'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'netdrv_lustre_rx_bucket_id');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nodecount'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nodecount');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'nsfdirectorate'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'nsfdirectorate');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'parentscience'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'parentscience');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'person'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'person');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'pi_institution'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'pi_institution');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'provider'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'provider');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'queue'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'queue');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'resource'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'resource');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'shared'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'shared');


INSERT INTO acl_group_bys(acl_id, realm_id, group_by_id, visible, enabled, statistic_id)
SELECT
  (SELECT a.acl_id
   FROM acls a
   WHERE a.name = 'cc')             AS acl_id,
  r.realm_id                         AS realm_id,
  (SELECT gb.group_by_id
   FROM group_bys gb
     JOIN realms r
       ON gb.realm_id = r.realm_id
   WHERE gb.name = 'username'
         AND r.name = LOWER('SUPREMM')) AS group_by_id,
True                               AS visible,
True                               AS enabled,
  s.statistic_id                     AS statistic_id
FROM statistics s
  JOIN realms r
    ON r.realm_id = s.realm_id
WHERE r.name = LOWER('SUPREMM')
      AND EXISTS(SELECT a.acl_id
                 FROM acls a
                 WHERE a.name = 'cc')
      AND EXISTS(SELECT gb.group_by_id
                 FROM group_bys gb
                 WHERE gb.name = 'username');

