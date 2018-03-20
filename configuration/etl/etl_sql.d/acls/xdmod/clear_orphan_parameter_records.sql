-- Delete records from user_acl_group_by_parameters that do not have a corresponding
-- record in user_acls
DELETE uagbp FROM user_acl_group_by_parameters uagbp
  LEFT JOIN user_acls ua ON ua.acl_id = uagbp.acl_id AND
                            ua.user_id = uagbp.user_id
WHERE ua.user_acl_id IS NULL;

-- Delete records from UserRoleParameters that do not have a corresponding
-- record in UserRoles
DELETE urp FROM UserRoleParameters urp
  LEFT JOIN UserRoles ur ON ur.role_id = urp.role_id AND
                            ur.user_id = urp.user_id
WHERE ur.user_id IS NULL;
