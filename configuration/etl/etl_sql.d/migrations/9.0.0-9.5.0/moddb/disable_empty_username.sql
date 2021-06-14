/*
 * Description: The purpose of this script is to disable any Users who have an empty username.
 */
UPDATE moddb.Users u
SET u.account_is_active = FALSE
WHERE u.username = '';
