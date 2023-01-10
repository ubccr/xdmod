<?php

declare(strict_types=1);

namespace Access\Controller\InternalDashboard;

use Access\Controller\BaseController;
use CCR\DB;
use Exception;
use OpenXdmod\Assets;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/internal_dashboard")
 */
class InternalDashboardController extends BaseController
{

    /**
     * @Route("")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $user = $this->getXDUser($request->getSession());

        $hasAppKernels = false;
        $instanceId = null;
        if (\xd_utilities\getConfiguration('features', 'appkernels') == 'on') {
            $op = $request->get('op');
            if ($op === 'ak_instance') {
                $hasAppKernels = true;
                $instanceId = $request->get('instance_id');
            }
        }

        $parameters = [
            'user' => $user,
            'has_app_kernels' => $hasAppKernels,
            'ak_instance_id' => $instanceId,
            'extjs_path' => 'gui/lib',
            'extjs_version' => 'extjs',
            'rest_token' => $user->getToken(),
            'rest_url' => sprintf(
                '%s%s',
                \xd_utilities\getConfiguration('rest', 'base'),
                \xd_utilities\getConfiguration('rest', 'version')
            ),
            'xdmod_features' => json_encode($this->getFeatures()),
            'is_logged_in' => !$user->isPublicUser(),
            'is_public_user' => $user->isPublicUser(),
            'asset_paths' => Assets::generateAssetTags('internal_dashboard'),
        ];

        if ($user->isPublicUser()) {
            return $this->render('internal_dashboard_login.html.twig', $parameters);
        } else {
            return $this->render('internal_dashboard.html.twig', $parameters);
        }
    }

    /**
     * @Route("/menus", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getMenus(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $config = \Configuration\XdmodConfiguration::assocArrayFactory(
            'internal_dashboard.json',
            CONFIG_DIR
        );

        return $this->json([
            'success' => true,
            'response' => $config['menu'],
            'count' => count($config['menu'])
        ]);
    }

    /**
     * @Route("/users/summary")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getUserSummary(Request $request): Response
    {
        $pdo = DB::factory('database');

        $sql = 'SELECT COUNT(*) AS count FROM moddb.Users';
        list($userCountRow) = $pdo->query($sql);

        // TODO: Refactor these queries.
        $sql = '
        SELECT COUNT(DISTINCT user_id) AS count
        FROM moddb.SessionManager
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(init_time)) < 7
    ';
        list($last7DaysRow) = $pdo->query($sql);

        $sql = '
        SELECT COUNT(DISTINCT user_id) AS count
        FROM moddb.SessionManager
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(init_time)) < 30
    ';
        list($last30DaysRow) = $pdo->query($sql);

        $returnData = [
            'success' => true,
            'response' => [
                [
                    'user_count'             => $userCountRow['count'],
                    'logged_in_last_7_days'  => $last7DaysRow['count'],
                    'logged_in_last_30_days' => $last30DaysRow['count'],
                ]
            ],
            'count' => 1,
        ];
        return $this->json($returnData);
    }

}