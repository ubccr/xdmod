<?php namespace Models\Services;

use CCR\DB;
use User\Roles;
use Xdmod\Config;

class Tabs
{

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
SELECT DISTINCT t.name as tab, a.name as acl FROM acl_tabs at
  JOIN user_acls ua
    ON at.acl_id = ua.acl_id
  JOIN tabs t
    ON at.tab_id = t.tab_id
  JOIN acls a
    ON a.acl_id = at.acl_id
WHERE ua.user_id = :user_id
SQL;
        $rows = $db->query($query, array(
            ':user_id' => $userId
        ));

        $sections = array('display', 'type', 'permitted_modules', 'query_descripters', 'summary_charts');
        $acls = array();

        $roleNames = Roles::getRoleNames(array('default'));
        foreach( $roleNames as $roleName) {
            foreach($sections as $section) {
                $acls[$roleName][$section] = Roles::getConfig($roleName, $section);
            }
        }

        foreach($rows as $row) {
            $tab = $row['tab'];
            $acl = $row['acl'];
            if (array_key_exists($acl, $acls)) {
                $tabs = array_reduce(
                    $acls[$acl]['permitted_modules'],
                    function($carry, $item) use($tab) {
                        if ($tab === $item['name']) {
                            $carry[] = $item;
                        }
                        return $carry;
                    },
                    array());
                if (count($tabs) > 0) {
                    $results = array_merge($results, $tabs);
                }
            }
        }

        return $results;
    }
}