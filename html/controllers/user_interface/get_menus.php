<?php

require_once __DIR__ . '/../common_params.php';

$returnData = array();

try {
    $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    $activeRole = $user->getMostPrivilegedRole();

    if (isset($_REQUEST['node']) && $_REQUEST['node'] == 'realms') {
        $query_group_name = 'tg_usage';

        if (isset($_REQUEST['query_group'])) {
            $query_group_name = $_REQUEST['query_group'];
        }

        $realms = array_keys(
            $activeRole->getAllQueryRealms($query_group_name)
        );

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
    } elseif (isset($_REQUEST['node'])
        && substr($_REQUEST['node'], 0, 6) == 'realm_'
    ) {
        $query_group_name = 'tg_usage';

        if (isset($_REQUEST['query_group'])) {
            $query_group_name = $_REQUEST['query_group'];
        }

        if (isset($_REQUEST['realm'])) {
            $realms = explode(',', $_REQUEST['realm']);
        } else {
            $realms = array_keys(
                $activeRole->getAllQueryRealms($query_group_name)
            );
        }

        foreach ($realms as $realm_name) {
            $query_descripter_groups = $activeRole->getQueryDescripters(
                $query_group_name,
                $realm_name
            );

            $hasItems = false;

            foreach ($query_descripter_groups as $query_descripter_group) {
                foreach ($query_descripter_group as $query_descripter) {
                    if ($query_descripter->getShowMenu() !== true) {
                        continue;
                    }

                    $returnData[] = array(
                        'text'                 => $query_descripter->getMenuLabel(),
                        'id'                   => 'group_by_'
                                                . $realm_name
                                                . '_'
                                                . $query_descripter->getGroupByName(),
                        'group_by'             => $query_descripter->getGroupByName(),
                        'query_group'          => $query_group_name,
                        'realm'                => $realm_name,
                        'defaultChartSettings' => $query_descripter->getChartSettings(true),
                        'chartSettings'        => $query_descripter->getChartSettings(true),
                        'node_type'            => 'group_by',
                        'iconCls'              => 'menu',
                        'description'          => $query_descripter->getGroupByLabel(),
                        'leaf'                 => false,
                    );

                    $hasItems = true;
                }
            }

            if ($hasItems) {
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
    } elseif (isset($_REQUEST['node'])
        && substr($_REQUEST['node'], 0, 9) == 'group_by_'
    ) {
        if (isset($_REQUEST['realm'])) {
            $realm_name = $_REQUEST['realm'];

            if (isset($_REQUEST['group_by'])) {
                $query_group_name = 'tg_usage';

                if (isset($_REQUEST['query_group'])) {
                    $query_group_name = $_REQUEST['query_group'];
                }

                $group_by_name = $_REQUEST['group_by'];
                $query_descripter = $activeRole->getQueryDescripters($query_group_name, $realm_name, $group_by_name);

                $group_by = $query_descripter->getGroupByInstance();

                foreach ($query_descripter->getPermittedStatistics() as $realm_group_by_statistic) {
                    $statistic_object = $query_descripter->getStatistic($realm_group_by_statistic);
                    if ($statistic_object->isVisible()) {
                        $returnData[] = array(
                            'text'                 => $statistic_object->getLabel(false),
                            'id'                   => 'statistic_'
                                                    . $realm_name
                                                    . '_'
                                                    . $group_by_name
                                                    . '_'
                                                    . $statistic_object->getAlias()->getName(),
                            'statistic'            => $statistic_object->getAlias()->getName(),
                            'group_by'             => $group_by_name,
                            'group_by_label'       => $group_by->getLabel(),
                            'query_group'          => $query_group_name,
                            'realm'                => $realm_name,
                            'defaultChartSettings' => $query_descripter->getChartSettings(),
                            'chartSettings'        => $query_descripter->getChartSettings(),
                            'node_type'            => 'statistic',
                            'iconCls'              => 'chart',
                            'description'          => $statistic_object->getAlias()->getName(),
                            'leaf'                 => true,
                        );
                    }
                }

                $texts = array();
                foreach ($returnData as $key => $row) {
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
