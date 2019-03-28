<?php

namespace Models\Services;

use CCR\DB;
use Configuration\XdmodConfiguration;

class Tabs
{
    const DEFAULT_ACL_HIERARCHY = 'acl_hierarchy';

    private function __construct()
    {
    }

    public static function getTabs(\XDUser $user)
    {
        $userId = $user->getUserID();

        if (!isset($userId)) {
            throw new \Exception('User must be saved first.');
        }
        $results = array();

        $db = DB::factory('database');

        $query = <<<SQL
SELECT t.name AS tab ,a.name AS acl FROM acl_tabs at
  JOIN (
         SELECT ua.acl_id FROM user_acls ua
           JOIN acl_hierarchies ah
             ON ah.acl_id = ua.acl_id
           JOIN hierarchies h
             ON ah.hierarchy_id = h.hierarchy_id
         WHERE ua.user_id = :user_id AND
               h.name = :acl_hierarchy_name
         ORDER BY ah.level DESC LIMIT 1
       ) max
    ON at.acl_id = max.acl_id
  JOIN acls a ON a.acl_id = at.acl_id
  JOIN tabs t ON t.tab_id = at.tab_id
UNION
SELECT nh.tab, nh.acl FROM (
  SELECT DISTINCT t.name as tab, a.name AS acl
  FROM acl_tabs at
    JOIN user_acls ua ON at.acl_id = ua.acl_id
    JOIN acls a ON a.acl_id = at.acl_id
    JOIN tabs t ON t.tab_id = at.tab_id
    LEFT JOIN acl_hierarchies ah
      ON ah.acl_id = at.acl_id
  WHERE
    ua.user_id = :user_id
    AND ah.acl_hierarchy_id IS NULL
) nh;
SQL;
        $rows = $db->query($query, array(
            ':user_id' => $userId,
            ':acl_hierarchy_name' => self::DEFAULT_ACL_HIERARCHY
        ));

        $aclConfig = XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR);
        $acls = $aclConfig['roles'];

        if (isset($acls['default'])) {
            unset($acls['default']);
        }

        foreach ($rows as $row) {
            $tab = $row['tab'];
            $acl = $row['acl'];
            if (array_key_exists($acl, $acls) && isset($acls[$acl]['permitted_modules'])) {
                $tabs = array_reduce(
                    $acls[$acl]['permitted_modules'],
                    function ($carry, $item) use ($tab) {
                        if ($tab === $item['name']) {
                            $carry[] = $item;
                        }
                        return $carry;
                    },
                    array()
                );
                if (count($tabs) > 0) {
                    $results = array_merge($results, $tabs);
                }
            }
        }

        return $results;
    }
}
