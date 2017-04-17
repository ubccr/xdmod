<?php

use CCR\DB;
use CCR\DB\iDatabase;
use User\Acl;

/**
 * Class Tabs
 *
 * The intention of the class is to provide a central location for all functionality
 * having to do with the concept of 'tabs'. This concept / data  was previously
 * encoded as the 'permitted_modules' section of the the 'roles.json' file.
 *
 * These tabs correspond on a one-to-one basis with the visual tabs that one
 * encounters in the XDMoD application. They can also be restricted on an acl by
 * acl basis. So a function has been provided to retrieve a list of tabs for a user
 * based on those allowed by said users acls.
 * This is intended to replace aRole->getPermittedModules().
 *
 * The infrastructure (columns) is also in place to support a tab having a set
 * of child tabs, which can be retrieved via the getChildTabs function.
 *
 */
class Tabs
{

    /**
     * @return Tab[]
     */
    public static function getTabs()
    {
        return self::_getTabs(DB::factory('database'));
    }

    /**
     * @param $tabId
     * @return null|Tab
     * @throws Exception
     */
    public static function getTab($tabId)
    {
        if (null === $tabId) {
            throw new Exception('A valid tab id must be provided. (missing)');
        }

        if (!is_numeric($tabId)) {
            throw new Exception('A valid tab id must be provided. (non numeric)');
        }

        return self::_getTab(
            DB::factory('database'),
            $tabId
        );
    }

    /**
     * @param XDUser $user
     * @return Tab[]
     * @throws Exception
     */
    public static function getTabsForUser(XDUser $user)
    {
        if (null === $user->getUserID()) {
            throw new Exception('User must have a valid id.');
        }

        return self::_getTabsForUser(
            DB::factory('database'),
            $user
        );
    }

    public static function getChildTabs(Tab $tab, Acl $acl = null)
    {
        if (null === $tab) {
            throw new Exception('Must provide a valid tab');
        }

        if (null === $tab->getTabId()) {
            throw new Exception('Tab must have a valid id.');
        }
        if (null !== $acl && null === $acl->getAclId()) {
            throw new Exception('Acl must have a valid id.');
        }

        return self::_getChildTabs(
            DB::factory('database'),
            $tab,
            $acl
        );
    }

    /**
     * @param iDatabase $db
     * @return Tab[]
     */
    private static function _getTabs(iDatabase $db)
    {
        $rows = $db->query("SELECT t.* FROM tabs t");
        $results = array_reduce($rows, function ($carry, $item) {
            $carry [] = new Tab($item);
            return $carry;
        }, array());
        return $results;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return Tab[]
     */
    private static function _getTabsForUser(iDatabase $db, XDUser $user)
    {
        $userId = $user->getUserID();

        $query = <<<SQL
SELECT DISTINCT t.*
FROM acl_tabs at
  JOIN user_acls ua
    ON at.acl_id = ua.acl_id
  JOIN acls a
    ON ua.acl_id = a.acl_id
  JOIN tabs t
    ON t.tab_id = at.tab_id
WHERE a.enabled = TRUE
      AND t.parent_tab_id IS NULL
      AND ua.user_id = :user_id
ORDER BY t.position
SQL;

        $results = array();

        $rows = $db->query($query, array(
            ':user_id' => $userId
        ));

        if ( count($rows) > 0 ) {
            $results = array_reduce($rows, function ($carry, $item) {
                $carry [$item['name']] = new Tab($item);
                return $carry;
            }, array());
        }

        return $results;
    }

    /**
     * @param iDatabase $db
     * @param integer $tabId
     * @return Tab|null
     */
    private static function _getTab(iDatabase $db, $tabId)
    {
        $row = $db->query("SELECT t.* FROM tabs t WHERE t.tab_id = :tab_id", array(
            ':tab_id' => $tabId
        ));
        if ( count($row) > 0 ) {
            return new Tab($row[0]);
        }
        return null;
    }

    /**
     * @param iDatabase $db
     * @param Tab $tab
     * @param Acl|null $acl
     * @return Tab[]|null
     */
    private static function _getChildTabs(iDatabase $db, Tab $tab, Acl $acl = null)
    {
        $defaultQuery = <<<SQL
SELECT t.* 
FROM tabs t 
WHERE t.parent_tab_id = :parent_tab_id
SQL;
        $aclQuery = <<<SQL
SELECT
  t.tab_id                              AS tab_id,
  t.name                                AS name,
  t.display                             AS display,
  coalesce(at.position, t.position)     AS position,
  coalesce(at.is_default, t.is_default) AS is_default,
  t.javascript_class                    AS javascript_class,
  t.javascript_reference                AS javascript_reference,
  t.tooltip                             AS tooltip,
  t.user_manual_section_name            AS user_manual_section_name
FROM acl_tabs at
  JOIN acl_tabs pt
    ON at.parent_acl_tab_id = pt.acl_tab_id
  JOIN tabs t
    ON t.tab_id = at.tab_id
WHERE
  pt.acl_id = :parent_acl_id
  AND pt.tab_id = :parent_tab_id
SQL;
        if ($acl !== null) {
            $rows = $db->query($aclQuery, array(
                ':parent_acl_id' => $acl->getAclId(),
                ':parent_tab_id' => $tab->getTabId()
            ));
        } else {
            $rows = $db->query($defaultQuery, array(
                ':parent_tab_id' => $tab->getTabId()
            ));
        }
        if (count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry []= new Tab($item);
                return $carry;
            }, array());
        }
        return null;
    }
}
