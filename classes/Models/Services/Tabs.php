<?php

namespace Models\Services;

use CCR\DB;
use User\Roles;

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
SQL;
        $rows = $db->query($query, array(
            ':user_id' => $userId,
            ':acl_hierarchy_name' => self::DEFAULT_ACL_HIERARCHY
        ));

        $sections = array('display', 'type', 'permitted_modules', 'query_descripters', 'summary_charts');
        $acls = array();

        $roleNames = Roles::getRoleNames(array('default'));
        foreach ($roleNames as $roleName) {
            foreach ($sections as $section) {
                $acls[$roleName][$section] = Roles::getConfig($roleName, $section);
            }
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
