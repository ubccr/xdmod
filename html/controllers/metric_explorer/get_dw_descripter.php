<?php

use Models\Services\Acls;
use Models\Services\Realms;
use Models\Services\Tokens;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

@require_once('common.php');

// Attempt authentication by API token.
try {
    $user = Tokens::authenticateController();
} catch (UnauthorizedHttpException $e) {
    // If token authentication failed then fall back to the standard
    // session-based authentication method.
    $user = \xd_security\getLoggedInUser();
}

$roles = $user->getAllRoles(true);

$roleDescriptors = array();
foreach ($roles as $activeRole) {
    $shortRole = $activeRole;
    $us_pos = strpos($shortRole, '_');
    if ($us_pos > 0)
    {
        $shortRole = substr($shortRole, 0, $us_pos);
    }

    if (array_key_exists($shortRole, $roleDescriptors)) {
        continue;
    }

    // If enabled, try to lookup answer in cache first.
    $cache_enabled = xd_utilities\getConfiguration('internal', 'dw_desc_cache') === 'on';
    $cache_data_found = false;
    if ($cache_enabled)
    {
        $db = \CCR\DB::factory('database');
        $db->execute('create table if not exists dw_desc_cache (role char(5), response mediumtext, index (role) ) ');
        $cachedResults = $db->query('select response from dw_desc_cache where role=:role', array('role' => $shortRole));
        if(count($cachedResults) > 0)
        {
            $roleDescriptors[$shortRole] = unserialize($cachedResults[0]['response']);
            $cache_data_found = true;
        }
    }

    // If the cache was not used or was not useful, get descriptors from code.
    if (!$cache_data_found)
    {
        $realms = array();
        $groupByObjects = array();

        $realmObjects = Realms::getRealmObjectsForUser($user);
        $query_descripter_realms = Acls::getQueryDescripters($user);

        foreach($query_descripter_realms as $query_descripter_realm => $query_descripter_groups)
        {
            $category = DataWarehouse::getCategoryForRealm($query_descripter_realm);
            if ($category === null) {
                continue;
            }
            $seenstats = array();

            $realmObject = $realmObjects[$query_descripter_realm];
            $realmDisplay = $realmObject->getDisplay();
            $realms[$query_descripter_realm] = array(
                'text' => $query_descripter_realm,
                'category' => $realmDisplay,
                'dimensions' => array(),
                'metrics' => array(),
            );
            foreach($query_descripter_groups as $query_descripter_group) {
                foreach ($query_descripter_group as $query_descripter) {
                    if ($query_descripter->getDisableMenu()) {
                        continue;
                    }

                    $groupByName = $query_descripter->getGroupByName();
                    $group_by_object = $query_descripter->getGroupByInstance();
                    $permittedStatistics = $group_by_object->getRealm()->getStatisticIds();

                    $groupByObjects[$query_descripter_realm . '_' . $groupByName] = array(
                        'object' => $group_by_object,
                        'permittedStats' => $permittedStatistics);
                    $realms[$query_descripter_realm]['dimensions'][$groupByName] = array(
                        'text' => $groupByName == 'none' ? 'None' : $group_by_object->getName(),
                        'info' => $group_by_object->getHtmlDescription()
                    );

                    $stats = array_diff($permittedStatistics, $seenstats);
                    if (empty($stats)) {
                        continue;
                    }

                    $statsObjects = $query_descripter->getStatisticsClasses($stats);
                    foreach ($statsObjects as $realm_group_by_statistic => $statistic_object) {

                        if ( ! $statistic_object->showInMetricCatalog() ) {
                            continue;
                        }

                        $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
                            $realm_group_by_statistic
                        );
                        $realms[$query_descripter_realm]['metrics'][$realm_group_by_statistic] =
                            array(
                                'text' => $statistic_object->getName(),
                                'info' => $statistic_object->getHtmlDescription(),
                                'std_err' => in_array($semStatId, $permittedStatistics),
                                'hidden_groupbys' => $statistic_object->getHiddenGroupBys()
                            );
                        $seenstats[] = $realm_group_by_statistic;
                    }
                }
            }
            $texts = array();
            foreach($realms[$query_descripter_realm]['metrics'] as $key => $row)
            {
                $texts[$key] = $row['text'];
            }
            array_multisort($texts, SORT_ASC, $realms[$query_descripter_realm]['metrics']);
        }
        $texts = array();
        foreach($realms as $key => $row)
        {
            $texts[$key] = $row['text'];
        }
        array_multisort($texts, SORT_ASC, $realms);

        $roleDescriptors[$shortRole] = array('totalCount'=> 1, 'data' => array(array( 'realms' => $realms)));

        // Cache the results if the cache is enabled.
        if ($cache_enabled)
        {
            $db->execute('insert into dw_desc_cache (role, response) values (:role, :response)', array('role' =>$shortRole, 'response' => serialize($roleDescriptors[$shortRole])));
        }
    }
}

$combinedRealmDescriptors = array();
foreach ($roleDescriptors as $roleDescriptor) {
    foreach ($roleDescriptor['data'][0]['realms'] as $realm => $realmDescriptor) {
        if (!isset($combinedRealmDescriptors[$realm])) {
            $combinedRealmDescriptors[$realm] = array(
                'metrics' => array(),
                'dimensions' => array(),
                'text' => $realmDescriptor['text'],
                'category' => $realmDescriptor['category'],
            );
        }

        $combinedRealmDescriptors[$realm]['metrics'] += $realmDescriptor['metrics'];
        $combinedRealmDescriptors[$realm]['dimensions'] += $realmDescriptor['dimensions'];
    }
}

xd_controller\returnJSON(array(
    'totalCount' => 1,
    'data' => array(
        array(
            'realms' => $combinedRealmDescriptors,
        ),
    ),
));
