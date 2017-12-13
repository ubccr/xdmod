<?php namespace Models\Services;

use Exception;
use PDO;

use CCR\DB;
use CCR\DB\iDatabase;
use Models\Acl;
use Models\GroupBy;
use Models\Realm;
use Models\Statistic;
use XDUser;

/**
 * Class Centers
 * @package User
 *
 * It is the intended purpose of this class to provide a host of functions to
 * ease working with and within the Acl framework. It provides basic CRUD
 * functionality in addition to a number of other functions related to Acls and
 * their associated pieces of data.
 *
 */
class Centers
{

    public static function getCenters()
    {
        $db = DB::factory('database');

        return $db->query("SELECT o.* FROM modw.organization");
    }
}