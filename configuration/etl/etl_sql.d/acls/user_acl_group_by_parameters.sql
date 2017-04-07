INSERT INTO user_acl_group_by_parameters(user_id, acl_id, group_by_id, value)
  SELECT
    urp.user_id,
    a.acl_id,
    gb.group_by_id,
    urp.param_value
  FROM UserRoleParameters urp
    JOIN Roles r
      ON r.role_id = urp.role_id
    JOIN acls a
      ON a.name = r.abbrev
    JOIN group_bys gb
      ON gb.name LIKE CONCAT('%', urp.param_name, '%')
    JOIN modules m
      ON m.module_id = gb.module_id
  ORDER BY urp.user_id, urp.role_id, a.acl_id, gb.group_by_id;