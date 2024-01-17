/* Backfill a username value into log entries where we're able to find a valid SessionManager.token['user_id'] -> Users.id relationship. */
UPDATE mod_logger.log_table as lt JOIN (SELECT
                                            id,
                                            message,
                                            REPLACE(JSON_EXTRACT(message, '$.data.token'), '"', '') as token
                                        FROM mod_logger.log_table
                                        WHERE
                                              ident IN ('rest.logger.db', 'controller.log')
                                          AND priority = 6) AS data JOIN moddb.SessionManager sm ON sm.session_token = data.token JOIN moddb.Users u ON u.id = sm.user_id
SET
    message = JSON_REPLACE(message, '$.data.username', u.username)
WHERE
      ident IN ('rest.logger.db', 'controller.log')
  AND priority = 6;
//

/* backfill for removing extra escaping from log_table, only for rest.logger.db and controller.log. */
UPDATE mod_logger.log_table lt
SET
    message = TRIM(TRAILING '"}' FROM
                   REPLACE(REPLACE(REPLACE(SUBSTR(message, 13), '\\"', '"'), '\\\\\\/', '/'), '\\/', '/'))
WHERE
      ident IN ('rest.logger.db', 'controller.log')
  AND priority = 6;

//
/* backfill for removing the extra escaping for the other ident types */
UPDATE mod_logger.log_table lt
SET message = REPLACE(REPLACE(REPLACE(REPLACE(message, '\\"', '"'), '\\\\\\/', '/'), '\\/', '/'), '\\\\', '\\')
WHERE
      ident NOT IN ('rest.logger.db', 'controller.log')
  AND priority = 6;
