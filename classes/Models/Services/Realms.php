<?php namespace Models\Services;

use CCR\DB;
use Models\Realm;

class Realms
{
    public static function getRealms()
    {
        $db = DB::factory('database');

        $results = $db->query("SELECT r.* FROM realms r");
        return array_reduce($results, function ($carry, $item) {
            $carry[] = new Realm($item);
            return $carry;
        }, array());
    }
}
