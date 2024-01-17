/* Backfill a username value into log entries where we're able to find a valid SessionManager.token['user_id'] -> Users.id relationship. */
UPDATE mod_logger.log_table as lt JOIN (SELECT
                                            l2.id,
                                            l2.message,
                                            REPLACE(JSON_EXTRACT(l2.message, '$.data.token'), '"', '') as token
                                        FROM mod_logger.log_table as l2
                                        WHERE
                                              l2.ident IN ('rest.logger.db', 'controller.log')
                                          AND l2.priority = 6) AS data JOIN moddb.SessionManager sm ON sm.session_token = data.token JOIN moddb.Users u ON u.id = sm.user_id
SET lt.message = JSON_REPLACE(lt.message, '$.data.username', u.username)
WHERE
      lt.ident IN ('rest.logger.db', 'controller.log')
  AND lt.priority = 6;
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
