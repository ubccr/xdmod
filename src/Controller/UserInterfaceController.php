<?php

declare(strict_types=1);

namespace CCR\Controller;

use DataWarehouse;
use DataWarehouse\Access\Usage;
use Exception;
use Models\Services\Acls;
use Models\Services\Realms;
use Models\Services\Tabs;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use function xd_response\buildError;

/**
 *
 */
class UserInterfaceController extends BaseController
{

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route("/controllers/user_interface.php", name: "legacy_user_interface")]
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation', true);
        switch ($operation) {
            case 'get_charts':
                return $this->getCharts($request);
            case 'get_data':
                return $this->getData($request);
            case 'get_menus':
                return $this->getMenus($request);
            case 'get_param_descriptions':
                return $this->getParamDescriptions($request);
            case 'get_tabs':
                return $this->getTabs($request);
        }

        throw new NotFoundHttpException();
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/interfaces/user/tabs', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getTabs(Request $request): Response
    {
        $user = $this->getXDUser($request->getSession());

        $tabs = Tabs::getTabs($user);

        $results = [];
        foreach ($tabs as $tab) {
            $results[] = [
                'tab' => $tab['name'],
                'isDefault' => isset($tab['default']) ? $tab['default'] : false,
                'title' => $tab['title'],
                'pos' => $tab['position'],
                'permitted_modules' => isset($tab['permitted_modules']) ? $tab['permitted_modules'] : null,
                'javascriptClass' => $tab['javascriptClass'],
                'javascriptReference' => $tab['javascriptReference'],
                'tooltip' => isset($tab['tooltip']) ? $tab['tooltip'] : '',
                'userManualSectionName' => $tab['userManualSectionName'],
            ];
        }
        // Sort tabs
        usort(
            $results,
            function ($a, $b) {
                return ($a['pos'] < $b['pos']) ? -1 : 1;
            }
        );

        return $this->json([
            'success' => true,
            'totalCount' => 1,
            'message' => '',
            'data' => [
                ['tabs' => json_encode(array_values($results))]
            ]
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/interfaces/user/charts', requirements: ['prefix' => '.*'],  methods: ['POST'])]
    public function getCharts(Request $request): Response
    {
        $this->logger->debug('Calling Get Charts');
        try {
            $user = $this->tokenHelper->authenticate($request, false);

            // If token authentication failed then fallback to the standard session based authentication method.
            if ($user === null) {
                $user = $this->getXDUser($request->getSession());
            }
        } catch (Exception $e) {
            return $this->json(
                buildError(new Exception('Session Expired', 2)),
                401
            );
        }

        $allowPublicUser = $request->get('public_user', false);
        if ($user->isPublicUser() && !$allowPublicUser) {
            return $this->json(buildError(new Exception('Session Expired', 2)), 401);
        }

        // Send the request and user to the Usage-to-Metric Explorer adapter.
        $this->logger->debug('Instantiating Usage Object');
        $usageAdapter = new Usage($request->request->all());

        $this->logger->debug('Calling Usage->getCharts');

        try {
            $chartResponse = $usageAdapter->getCharts($user);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $statusCode = 400;
            if (str_starts_with($message, 'Your user account does not have permission to view the requested data.')) {
                $statusCode = 403;
            } elseif ($message === 'One or more realms must be specified.') {
                $statusCode = 500;
            }
            return $this->json(buildError($e), $statusCode);
        }

        $newHeaders = [];
        foreach ($chartResponse['headers'] as $headerName => $headerValue) {
            $newHeaders [] = sprintf('%s: %s', $headerName, $headerValue);
        }

        $format = $this->getStringParam($request, 'format');
        $this->logger->debug(sprintf('Requested Format %s', var_export($format, true)));
        if (isset($format)) {
            switch ($format) {
                case 'pdf':
                    $newHeaders['Content-Type'] = 'application/pdf';
                    break;
                case 'png':
                    $newHeaders['Content-Type'] = 'image/png';
                    break;
                case 'csv':
                    $newHeaders['Content-Type'] = 'application/xls';
                    break;
                case 'svg':
                    $newHeaders['Content-Type'] = 'image/svg+xml';
                    break;
                case 'xml':
                    $newHeaders['Content-Type'] = 'text/xml;charset=UTF-8';
                    break;
            }
        }
        $this->logger->debug(sprintf('Adding Headers: %s', var_export($newHeaders, true)));

        return new Response($chartResponse['results'], 200, $newHeaders);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/interfaces/user/data', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getData(Request $request): Response
    {
        $this->logger->debug('GetData Called');
        return $this->getCharts($request);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/interfaces/user/menus', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getMenus(Request $request): Response
    {
        $returnData = [];

        $user = $this->getXDUser($request->getSession());

        $node = $this->getStringParam($request, 'node');
        $this->logger->debug('Getting Menus for ', [$node]);
        if (isset($node) && $node === 'realms') {
            $this->logger->debug('Getting Menus for realms');
            $queryGroupName = $this->getStringParam($request, 'query_group', false, 'tg_usage');

            $realms = Realms::getRealmsForUser($user);

            foreach ($realms as $realm) {
                $returnData[] = [
                    'text' => $realm,
                    'id' => 'realm_' . $realm,
                    'realm' => $realm,
                    'query_group' => $queryGroupName,
                    'node_type' => 'realm',
                    'iconCls' => 'realm',
                    'description' => $realm,
                    'leaf' => false,
                ];
            }
        } elseif (isset($node) && \xd_utilities\string_begins_with($node, 'category_')) {
            $this->logger->debug('Getting Menus for category_');
            $queryGroupName = $this->getStringParam($request, 'query_group', false, 'tg_usage');

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
            $categoryRealmIndices = [];
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
            $category = $this->getStringParam($request, 'category');
            if (isset($category)) {
                $requestedCategories = explode(',', $category);
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
                $categoryReturnData = [];
                foreach ($category as $realm_name) {

                    // retrieve the query descripters this user is authorized to view for this realm.
                    $queryDescriptorGroups = Acls::getQueryDescripters(
                        $user,
                        $realm_name
                    );
                    foreach ($queryDescriptorGroups as $groupByName => $queryDescriptorData) {
                        $queryDescriptor = $queryDescriptorData['all'];

                        if ($queryDescriptor->getShowMenu() !== true) {
                            continue;
                        }

                        $nodeId = (
                            'node=group_by&realm='
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
                            ? $categoryReturnData[$nodeId]['realm'] . ",{$realm_name}"
                            : $realm_name
                        );

                        $categoryReturnData[$nodeId] = [
                            'text' => $nodeText,
                            'id' => $nodeId,
                            'group_by' => $queryDescriptor->getGroupByName(),
                            'query_group' => $queryGroupName,
                            'category' => $categoryName,
                            'realm' => $nodeRealms,
                            'defaultChartSettings' => $queryDescriptor->getChartSettings(true),
                            'chartSettings' => $queryDescriptor->getChartSettings(true),
                            'node_type' => 'group_by',
                            'iconCls' => 'menu',
                            'description' => $queryDescriptor->getGroupByLabel(),
                            'leaf' => false
                        ];

                        $hasItems = true;
                    }
                }

                if ($hasItems) {
                    $returnData = array_merge(
                        $returnData,
                        array_values($categoryReturnData)
                    );

                    $returnData[] = [
                        'text' => '',
                        'id' => '-111',
                        'node_type' => 'separator',
                        'iconCls' => 'blank',
                        'leaf' => true,
                        'disabled' => true
                    ];
                }
            }
        } elseif (
            isset($_REQUEST['node'])
            && substr($_REQUEST['node'], 0, 13) == 'node=group_by'
        ) {
            $this->logger->debug('Getting Menus for group_by');
            $category = $this->getStringParam($request, 'category');
            if ($category) {
                $categoryName = $category;
                $groupByName = $this->getStringParam($request, 'group_by');
                if (isset($groupByName)) {
                    $queryGroupName = $this->getStringParam($request, 'query_group', false, 'tg_usage');

                    // Get the categories. If the requested one does not exist,
                    // throw an exception.
                    $categories = DataWarehouse::getCategories();
                    if (!isset($categories[$categoryName])) {
                        throw new Exception('Category not found.');
                    }

                    foreach ($categories[$categoryName] as $realm_name) {
                        $queryDescriptor = Acls::getQueryDescripters($user, $realm_name, $groupByName);
                        if (empty($queryDescriptor)) {
                            continue;
                        }

                        $group_by = $queryDescriptor->getGroupByInstance();

                        foreach ($queryDescriptor->getPermittedStatistics() as $realm_group_by_statistic) {
                            $statistic = $queryDescriptor->getStatistic($realm_group_by_statistic);

                            if (!$statistic->showInMetricCatalog()) {
                                continue;
                            }

                            $statName = $statistic->getId();
                            $chartSettings = $queryDescriptor->getChartSettings();
                            if (!$statistic->usesTimePeriodTablesForAggregate()) {
                                $chartSettingsArray = json_decode($chartSettings, true);
                                $chartSettingsArray['dataset_type'] = 'timeseries';
                                $chartSettingsArray['display_type'] = 'line';
                                $chartSettingsArray['swap_xy'] = false;
                                $chartSettings = json_encode($chartSettingsArray);
                            }
                            $returnData[] = [
                                'text' => $statistic->getName(false),
                                'id' => 'node=statistic&realm='
                                    . $realm_name
                                    . '&group_by='
                                    . $groupByName
                                    . '&statistic='
                                    . $statName,
                                'statistic' => $statName,
                                'group_by' => $groupByName,
                                'group_by_label' => $group_by->getName(),
                                'query_group' => $queryGroupName,
                                'category' => $categoryName,
                                'realm' => $realm_name,
                                'defaultChartSettings' => $chartSettings,
                                'chartSettings' => $chartSettings,
                                'node_type' => 'statistic',
                                'iconCls' => 'chart',
                                'description' => $statName,
                                'leaf' => true,
                                'supportsAggregate' => $statistic->usesTimePeriodTablesForAggregate()
                            ];
                        }
                    }

                    if (empty($returnData)) {
                        throw new Exception('Category not found.');
                    }

                    $texts = [];
                    foreach ($returnData as $key => $row) {
                        $texts[$key] = $row['text'];
                    }
                    array_multisort($texts, SORT_ASC, $returnData);
                }
            }
        }

        return $this->json($returnData);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/interfaces/userparameters/descriptions', requirements: ['prefix' => '.*'],  methods: ['POST'])]
    public function getParamDescriptions(Request $request): Response
    {
        $user = $this->getXDUser($request->getSession());

        $queryBuilder = DataWarehouse\QueryBuilder::getInstance();
        $requestParams = $request->request->all();
        $parameterDescriptions = $queryBuilder->pullQueryParameterDescriptionsFromRequest($requestParams, $user);

        $keyValueParamDescriptions = [];
        foreach ($parameterDescriptions as $param_desc) {
            $kv = explode('=', $param_desc);
            $keyValueParamDescriptions[] = ['key' => trim($kv[0], ' '), 'value' => trim($kv[1], ' ')];
        }

        return $this->json([
            'totalCount' => count($keyValueParamDescriptions),
            'success' => true,
            'message' => 'success',
            'data' => $keyValueParamDescriptions
        ]);
    }


}
