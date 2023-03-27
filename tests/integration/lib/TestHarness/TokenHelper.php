<?php

namespace TestHarness;

use CCR\DB;

/**
 *
 */
abstract class TokenHelper
{
    /**
     * A helper function that will allow us to test the expiration of a token.
     *
     * Note: We need to directly access the database as we do not have an endpoint for expiring
     * a token.
     *
     * @param string $userId the userId whose token should be expired.
     *
     * @return bool true if the token was successfully expired.
     *
     * @throws Exception if there is a problem parsing the the provided $rawToken.
     * @throws Exception if there is a problem connecting to or executing the update statement against the database.
     */
    public static function expireToken($userId)
    {
        $db = DB::factory('database');
        $query = 'UPDATE moddb.user_tokens SET expires_on = NOW() WHERE user_id = :user_id';
        $params = array(':user_id' => $userId);
        return $db->execute($query, $params) === 1;
    }
}
