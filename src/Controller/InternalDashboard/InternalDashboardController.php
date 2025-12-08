<?php

declare(strict_types=1);

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use CCR\DB;
use Exception;
use Models\Services\Users;
use OpenXdmod\Assets;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;


/**
 *
 */
class InternalDashboardController extends BaseController
{

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard')]
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
            return $this->render('twig/internal_dashboard_login.html.twig', $parameters);
        } else {
            if (!$user->hasAcl('mgr')) {
                return $this->redirect($this->generateUrl('xdmod_home'));
            }
            return $this->render('twig/internal_dashboard.html.twig', $parameters);
        }
    }

    /**
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/dashboard.php', methods: ['POST'])]
    public function dashboardIndex(Request $request): Response
    {
        $operation = $request->get('operation');
        switch ($operation) {
            case 'get_menu':
                return $this->getMenus($request);
            default:
                throw new BadRequestHttpException();
        }
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard/menus', methods: ['POST'])]
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
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard/controllers/user.php', methods: ['POST'])]
    public function userController(Request $request): Response
    {
        $operation = $request->get('operation');
        switch ($operation) {
            case 'get_summary':
                return $this->getUserSummary($request);
            default:
                throw new BadRequestHttpException();
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard/users/summary')]
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
                    'user_count' => $userCountRow['count'],
                    'logged_in_last_7_days' => $last7DaysRow['count'],
                    'logged_in_last_30_days' => $last30DaysRow['count'],
                ]
            ],
            'count' => 1,
        ];
        return $this->json($returnData);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route("/internal_dashboard/controllers/controller.php", name: "legacy_internal_dashboard_controllers", methods: ['POST', 'GET'])]
    public function controllers(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $operation = $this->getStringParam($request, 'operation', true);
        switch ($operation) {
            case 'enum_account_requests':
                return $this->enumAccountRequests($request);
            case 'update_request':
                return $this->updateRequest($request);
            case 'delete_request':
                return $this->deleteRequest($request);
            case 'enum_existing_users':
                return $this->enumExistingUsers($request);
            case 'enum_user_types_and_roles':
                return $this->enumUserTypesAndRoles($request);
            case 'enum_user_visits':
            case 'enum_user_visits_export':
                return $this->enumUserVisits($request, $operation);
            case 'ak_rr':
                return $this->akrr($request);
            case 'logout':
                return $this->redirectToRoute('xdmod_logout');
        }

        return $this->json([
            'success' => false,
            'message' => 'operation not recognized'
        ]);
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * Enumerates the current Requests for an XDMoD Account.
     *
     * @param Request $request
     * @return Response
     * @throws Exception if unable to retrieve a connection to the database.
     */
    private function enumAccountRequests(Request $request): Response
    {
        $md5Only = $this->getBooleanParam($request, 'md5only');

        $pdo = DB::factory('database');
        $sql = <<<SQL
        SELECT
            id,
            first_name,
            last_name,
            organization,
            title, email_address,
            field_of_science,
            additional_information,
            time_submitted,
            status,
            comments
        FROM AccountRequests
SQL;

        $results = $pdo->query($sql);

        $data = [
            'success' => true,
            'count' => count($results),
            'response' => $results
        ];

        if (isset($md5Only) && $md5Only) {
            unset($data['count']);
            unset($data['response']);
        }

        return $this->json($data);
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function updateRequest(Request $request): Response
    {
        $id = $this->getStringParam($request, 'id', true);
        $comments = $this->getStringParam($request, 'comments', true);

        $pdo = DB::factory('database');

        $data = ['success' => false, 'message' => 'invalid id specified'];

        $results = $pdo->query('SELECT id FROM AccountRequests WHERE id=:id', ['id' => $id]);
        if (count($results) == 1) {
            $pdo->execute('UPDATE AccountRequests SET comments=:comments WHERE id=:id', [
                'comments' => $comments,
                'id' => $id
            ]);
            $data = ['success' => true];
        }

        return $this->json($data);
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function deleteRequest(Request $request): Response
    {
        $idParam = $this->getStringParam($request, 'id', true, null, '/^\d+(,\d+)*$/');

        $pdo = DB::factory('database');

        $ids = array_map('intval', explode(',', $idParam));
        $idPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
        $pdo->execute("DELETE FROM AccountRequests WHERE id IN ($idPlaceholders)", $ids);

        return $this->json(['success' => true]);
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * NOTE: there is a duplicate function UserAdminController::enumExistingUsers, this one can be removed when we are
     * able to discontinue the old API layout.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function enumExistingUsers(Request $request): Response
    {
        $groupFilter = $this->getStringParam($request, 'group_filter');
        $roleFilter = $this->getStringParam($request, 'role_filter');
        $contextFilter = $this->getStringParam($request, 'context_filter', false, '');

        $results = Users::getUsers($groupFilter, $roleFilter, $contextFilter);
        $filtered = [];
        foreach ($results as $user) {
            if ($user['username'] !== 'Public User') {
                $filtered[] = $user;
            }
        }
        $data = [
            'success' => true,
            'count' => count($filtered),
            'response' => $filtered
        ];
        return new Response(json_encode($data));
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function enumUserTypesAndRoles(Request $request): Response
    {
        $data = ['success' => true];
        $pdo = DB::factory('database');

        $query = 'SELECT id, type, color FROM moddb.UserTypes';
        $userTypes = $pdo->query($query);
        $data['user_types'] = $userTypes;

        $query = "SELECT display AS description, acl_id AS role_id FROM moddb.acls WHERE name != 'pub' ORDER BY description";
        $userRoles = $pdo->query($query);
        $data['user_roles'] = $userRoles;
        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        return $response;
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * @param Request $request
     * @param string $operation
     * @return Response
     * @throws Exception
     */
    private function enumUserVisits(Request $request, string $operation): Response
    {
        $timeframe = strtolower($this->getStringParam($request, 'timeframe'));
        $userTypes = explode(',', $this->getStringParam($request, 'user_types'));
        $logger = $this->logger;
        if (!in_array($timeframe, ['year', 'month'])) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'invalid value specified for the timeframe'
            ]));
        }

        $data = [
            'success' => true,
            'stats' => \XDStatistics::getUserVisitStats($timeframe, $userTypes)
        ];

        if ($operation === 'enum_user_visits_export') {
            $response = new StreamedResponse(function () use ($data, $logger) {
                $outputStream = fopen('php://output', 'wb');

                $content = array_map(
                    function ($item) {
                        return implode(',', $item);
                    },
                    $data['stats']
                );

                // Add the header row.
                array_unshift($content, implode(',', UserVisitController::$columns));

                $written = fwrite(
                    $outputStream,
                    sprintf("%s\n", implode("\n", $content))
                );
                if ($written === false) {
                    $logger->error('Unable to write bytes to output stream');
                    exit(1);
                }

                $flushed = fflush($outputStream);
                if ($flushed === false) {
                    $logger->error('Unable to flush output stream');
                    exit(1);
                }

                $closed = fclose($outputStream);
                if ($closed === false) {
                    $logger->error('Unable to close output stream');
                    exit(1);
                }
            });

            $response->headers->set('Content-Type', 'application/xls');
            $response->headers->set(
                'Content-Disposition',
                HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    "xdmod_visitation_stats_by_$timeframe.csv"
                )
            );

            return $response;
        }

        return new Response(json_encode($data));
    }

    /**
     * Code Ported from `html/internal_dashboard/controllers/controller.php`
     *
     * TODO: Probable end up removing this function as it doesn't look like it's used.
     *
     * @param Request $request
     * @return Response
     */
    private function akrr(Request $request): Response
    {
        $data = ['success' => true];

        $startDate = $this->getStringParam($request, 'start_date');
        $endDate = $this->getStringParam($request, 'end_date');

        $testData = [['x' => [1, 2, 3], 'y' => [5, 2, 1]]];

        $data['response'] = $testData;
        $data['count'] = count($testData);

        return $this->json($data);
    }

}
