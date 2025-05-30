<?php

use Models\Services\Acls;
use Models\Services\Realms;

require_once __DIR__ . '/../common_params.php';

$returnData = array();

try {
   $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    if (isset($_REQUEST['node']) && $_REQUEST['node'] == 'realms') {
        $query_group_name = 'tg_usage';

        if (isset($_REQUEST['query_group'])) {
            $query_group_name = $_REQUEST['query_group'];
        }

        $realms = Realms::getRealmsForUser($user);

        foreach ($realms as $realm) {
            $returnData[] = array(
                'text'        => $realm,
                'id'          => 'realm_' . $realm,
                'realm'       => $realm,
                'query_group' => $query_group_name,
                'node_type'   => 'realm',
                'iconCls'     => 'realm',
                'description' => $realm,
                'leaf'        => false,
            );
        }
    } elseif (
        isset($_REQUEST['node'])
        && \xd_utilities\string_begins_with($_REQUEST['node'], 'category_')
    ) {
        $query_group_name = 'tg_usage';

        if (isset($_REQUEST['query_group'])) {
            $query_group_name = $_REQUEST['query_group'];
        }

        // Get the categories ( realms ) that XDMoD knows about.
        $categories = DataWarehouse::getCategories();

        // Retrieve the realms that the user has access to
        $realms = Realms::getRealmIdsForUser($user);

        // Filter the categories by those that the user has access to.
        $categories = array_map(function ($category) use ($realms) {
            return array_filter($category, function ($realm) use ($realms) {
                return in_array($realm, $realms);
            });
        }, $categories);
        $categories = array_filter($categories);

        // Ensure the categories are sorted as the realms were.
        $categoryRealmIndices = array();
        foreach ($categories as $categoryName => $category) {
            foreach ($category as $realm) {
                $realmIndex = array_search($realm, $realms);
                if (
                    !isset($categoryRealmIndices[$categoryName])
                    || $categoryRealmIndices[$categoryName] > $realmIndex
                ) {
                    $categoryRealmIndices[$categoryName] = $realmIndex;
                }
            }
        }
        array_multisort($categoryRealmIndices, $categories);

        // If the user requested certain categories, ensure those categories
        // are valid.
        if (isset($_REQUEST['category'])) {
            $requestedCategories = explode(',', $_REQUEST['category']);
            $missingCategories = array_diff($requestedCategories, array_keys($categories));
            if (!empty($missingCategories)) {
                throw new Exception("Invalid categories: " . implode(', ', $missingCategories));
            }
            $categories = array_map(function ($categoryName) use ($categories) {
                return $categories[$categoryName];
            }, $requestedCategories);
        }

        foreach ($categories as $categoryName => $category) {
            $hasItems = false;
            $categoryReturnData = array();
            foreach ($category as $realm_name) {

                // retrieve the query descripters this user is authorized to view for this realm.
                $query_descripter_groups = Acls::getQueryDescripters(
                    $user,
                    $realm_name
                );
                foreach($query_descripter_groups as $groupBy => $queryDescriptorData) {
                    $queryDescriptor = $queryDescriptorData['all'];

                    if ($queryDescriptor->getShowMenu() !== true) {
                        continue;
                    }

                    $nodeId = (
                        'node=group_by'
                        . '&realm='
                        . $categoryName
                        . '&group_by='
                        . $queryDescriptor->getGroupByName()
                    );

                    // Make sure that the nodeText, derived from the query descripters menu
                    // label, has each  instance of $realm_name replaced with $categoryName.
                    $nodeText = preg_replace(
                        '/' . preg_quote($realm_name, '/') . '/',
                        $categoryName,
                        $queryDescriptor->getMenuLabel()
                    );

                    // If this $nodeId has been seen before but for a different realm. Update
                    // the list of realms associated with this $nodeId
                    $nodeRealms = (
                    isset($categoryReturnData[$nodeId])
                        ? $categoryReturnData[$nodeId]['realm'] . ",${realm_name}"
                        : $realm_name
                    );

                    $categoryReturnData[$nodeId] = array(
                        'text' => $nodeText,
                        'id' => $nodeId,
                        'group_by' => $queryDescriptor->getGroupByName(),
                        'query_group' => $query_group_name,
                        'category' => $categoryName,
                        'realm' => $nodeRealms,
                        'defaultChartSettings' => $queryDescriptor->getChartSettings(true),
                        'chartSettings' => $queryDescriptor->getChartSettings(true),
                        'node_type' => 'group_by',
                        'iconCls' => 'menu',
                        'description' => $queryDescriptor->getGroupByLabel(),
                        'leaf' => false
                    );

                    $hasItems = true;
                }
            }

            if ($hasItems) {
                $returnData = array_merge(
                    $returnData,
                    array_values($categoryReturnData)
                );

                $returnData[] = array(
                    'text'      => '',
                    'id'        => '-111',
                    'node_type' => 'separator',
                    'iconCls'   => 'blank',
                    'leaf'      => true,
                    'disabled'  => true
                );
            }
        }
    } elseif (
        isset($_REQUEST['node'])
        && substr($_REQUEST['node'], 0, 13) == 'node=group_by'
    ) {
        if (isset($_REQUEST['category'])) {
            $categoryName = $_REQUEST['category'];

            if (isset($_REQUEST['group_by'])) {
                $query_group_name = 'tg_usage';

                if (isset($_REQUEST['query_group'])) {
                    $query_group_name = $_REQUEST['query_group'];
                }

                // Get the categories. If the requested one does not exist,
                // throw an exception.
                $categories = DataWarehouse::getCategories();
                if (!isset($categories[$categoryName])) {
                    throw new Exception("Category not found.");
                }

                $group_by_name = $_REQUEST['group_by'];

                foreach ($categories[$categoryName] as $realm_name) {
                    $query_descripter = Acls::getQueryDescripters($user, $realm_name, $group_by_name);
                    if (empty($query_descripter)) {
                        continue;
                    }

                    $group_by = $query_descripter->getGroupByInstance();

                    foreach ($query_descripter->getPermittedStatistics() as $realm_group_by_statistic) {
                        $statistic_object = $query_descripter->getStatistic($realm_group_by_statistic);

                        if ( ! $statistic_object->showInMetricCatalog() || in_array($group_by_name, $statistic_object->getHiddenGroupBys())) {
                            continue;
                        }

                        $statName = $statistic_object->getId();
                        $chartSettings = $query_descripter->getChartSettings();
                        if(!$statistic_object->usesTimePeriodTablesForAggregate()){
                            $chartSettingsArray = json_decode($chartSettings, true);
                            $chartSettingsArray['dataset_type'] = 'timeseries';
                            $chartSettingsArray['display_type'] = 'line';
                            $chartSettingsArray['swap_xy'] = false;
                            $chartSettings = json_encode($chartSettingsArray);
                        }
                        $returnData[] = array(
                            'text'                 => $statistic_object->getName(false),
                            'id'                   => 'node=statistic'
                            . '&realm='
                            . $realm_name
                            . '&group_by='
                            . $group_by_name
                            . '&statistic='
                            . $statName,
                            'statistic'            => $statName,
                            'group_by'             => $group_by_name,
                            'group_by_label'       => $group_by->getName(),
                            'query_group'          => $query_group_name,
                            'category'             => $categoryName,
                            'realm'                => $realm_name,
                            'defaultChartSettings' => $chartSettings,
                            'chartSettings'        => $chartSettings,
                            'node_type'            => 'statistic',
                            'iconCls'              => 'chart',
                            'description'          => $statName,
                            'leaf'                 => true,
                            'supportsAggregate'    => $statistic_object->usesTimePeriodTablesForAggregate()
                        );
                    }
                }

                if (empty($returnData)) {
                    throw new Exception("Category not found.");
                }

                $texts = array();
                foreach($returnData as $key => $row) {
                    $texts[$key] = $row['text'];
                }
                array_multisort($texts, SORT_ASC, $returnData);
            }
        }
    }
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $ex) {
    $returnData = array(
        'totalCount' => 0,
        'message'    => $ex->getMessage(),
        'data'       => array($ex->getTraceAsString()),
        'success'    => false,
    );
}

xd_controller\returnJSON($returnData);
