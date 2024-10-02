/* Backfill a username value into log entries where we're able to find a valid SessionManager.token['user_id'] -> Users.id relationship. */
UPDATE mod_logger.log_table as lt JOIN (SELECT
                                            l2.id,
                                            l2.message,
                                            REPLACE(JSON_EXTRACT(l2.message, '$.data.token'), '"', '') as token
                                        FROM mod_logger.log_table as l2
                                        WHERE
                                              l2.ident IN ('rest.logger.db', 'controller.log')
                                          AND l2.priority = 6) AS data on data.id = lt.id JOIN moddb.SessionManager sm ON sm.session_token = data.token JOIN moddb.Users u ON u.id = sm.user_id
SET lt.message = JSON_REPLACE(lt.message, '$.data.username', u.username)
//
/* backfill for removing the extra escaping for the other ident types */
UPDATE mod_logger.log_table lt
    JOIN (
        WITH message_converted      as (SELECT
                                            id,
                                            ident,
                                            priority,
                                            logtime,
                                            IF(JSON_EXISTS(message, '$.message') AND
                                               JSON_VALID(JSON_UNQUOTE(JSON_EXTRACT(message, '$.message'))),
                                               JSON_REPLACE(message, '$.message', JSON_EXTRACT(
                                                   REPLACE(JSON_UNQUOTE(JSON_EXTRACT(message, '$.message')), '\\/', '/'), '$')),
                                               message) as message
                                        FROM mod_logger.log_table lt WHERE lt.ident IN ('rest.logger.db', 'controller.log') AND lt.priority = 6),
            /* converts post.config from a json_encoded object to a json object */
             post_config_converted  AS (SELECT
                                            id,
                                            priority,
                                            ident,
                                            logtime,
                                            IF(JSON_EXISTS(message, '$.message.post.config') AND
                                               JSON_VALID(JSON_UNQUOTE(JSON_EXTRACT(message, '$.message.post.config'))),
                                               JSON_REPLACE(message, '$.message.post.config', JSON_EXTRACT(
                                                   JSON_UNQUOTE(JSON_EXTRACT(message, '$.message.post.config')), '$')),
                                               message) as message
                                        FROM message_converted),
             remove_escaped_slashes AS (SELECT
                                            id,
                                            ident,
                                            priority,
                                            logtime,
                                            REPLACE(REPLACE(message, '\\/', '/'), '\\"', '"') as message
                                        FROM post_config_converted)
        SELECT * FROM remove_escaped_slashes
    ) as data ON data.id = lt.id
SET lt.message = data.message;
