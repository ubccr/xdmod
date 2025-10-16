UPDATE Users
SET person_id = -2
WHERE person_id = -1
AND username != 'Public User'
