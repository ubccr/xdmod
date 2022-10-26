<?php

namespace Access\Controller;

use DataWarehouse\Access\MetricExplorer;
use Exception;
use Models\Services\Acls;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use XDUser;

/**
 * @Route("/metrics/explorer")
 */
class MetricExplorerController extends AbstractController
{
    /**
     * The identifier that is used to store 'queries' in the user profile.
     *
     * @var string
     */
    private const QUERIES_STORE = 'queries_store';

    private const DEFAULT_ERROR_MESSAGE = 'An error was encountered while attempting to process the requested authorization procedure.';

    /**
     * @Route("/queries", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getQueries(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $action = 'getQueries';
        $payload = [
            'success' => false,
            'action' => $action
        ];
        $statusCode = 401;

        try {
            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
            if (isset($user) && $user instanceof XDUser) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $data = $queries->get();

                foreach ($data as &$query) {
                    $this->removeRoleFromQuery($user, $query);
                    $query['name'] = htmlspecialchars($query['name'], ENT_COMPAT, 'UTF-8', false);
                }

                $payload['data'] = $data;
                $payload['success'] = true;
                $statusCode = 200;
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestException|HttpException|Exception $exception) {
            $payload['message'] = $exception->getMessage();
            $statusCode = (get_class($exception) === 'Exception') ? 500 : $exception->getStatusCode();
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * @Route("/queries/{queryId}", methods={"GET"}, requirements={"queryId"="\w+"})
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    public function getQueryByid(Request $request, string $queryId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $action = 'getQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;

        try {
            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
            if (isset($user) && $user instanceof XDUser) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);

                $query = $queries->getById($queryId);

                if (isset($query)) {
                    $payload['data'] = $query;
                    $payload['data']['name'] = htmlspecialchars($query['name'], ENT_COMPAT, 'UTF-8', false);
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'Unable to find the query identified by the provided id: ' . $queryId;
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestException|HttpException|Exception $exception) {
            $payload['message'] = $exception->getMessage();
            $statusCode = (get_class($exception) === 'Exception') ? 500 : $exception->getStatusCode();
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * @Route("/queries", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function createQuery(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $action = 'creatQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
        );
        $statusCode = 401;
        try {
            $data = $request->get('data', null);
            if ($data === null) {
                throw new BadRequestHttpException('data is a required parameter.');
            }

            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
            if (isset($user) && $user instanceof XDUser) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $data = json_decode($data, true);
                $success = $queries->insert($data) != null;
                $payload['success'] = $success;
                if ($success) {
                    $payload['success'] = true;
                    $payload['data'] = $data;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'Error creating chart. User is over the chart limit.';
                    $statusCode = 500;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestException|HttpException|Exception $exception) {
            $payload['message'] = $exception->getMessage();
            $statusCode = (get_class($exception) === 'Exception') ? 500 : $exception->getStatusCode();
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * @Route("/queries/{queryId}", methods={"PUT"}, requirements={"queryId"="\w+"})
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    public function updateQueryById(Request $request, string $queryId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $action = 'updateQuery';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
            if (isset($user) && $user instanceof XDUser) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);

                $query = $queries->getById($queryId);
                if (isset($query)) {

                    $data = $request->get('data');
                    if (isset($data)) {
                        $jsonData = json_decode($data, true);
                        $name = isset($jsonData['name']) ? $jsonData['name'] : null;
                        $config = isset($jsonData['config']) ? $jsonData['config'] : null;
                        $ts = isset($jsonData['ts']) ? $jsonData['ts'] : microtime(true);
                    } else {
                        $name = $request->get('name');
                        $config = $request->get('config');
                        $ts = $request->get('ts');
                    }

                    if (isset($name)) {
                        $query['name'] = $name;
                    }
                    if (isset($config)) {
                        $query['config'] = $config;
                    }
                    if (isset($ts)) {
                        $query['ts'] = $ts;
                    }

                    $queries->upsert($queryId, $query);

                    // required for the UI to do it's thing.
                    $total = count($queries->get());

                    // make sure everything is in place for returning to the
                    // front end.
                    $payload['total'] = $total;
                    $payload['success'] = true;
                    $statusCode = 200;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestException|HttpException|Exception $exception) {
            $payload['message'] = $exception->getMessage();
            $statusCode = (get_class($exception) === 'Exception') ? 500 : $exception->getStatusCode();
        }

        return $this->json($payload, $statusCode);
    }

    /**
     * @Route("/queries/{queryId}", methods={"DELETE"}, requirements={"queryId"="\w+"})
     * @param Request $request
     * @param string $queryId
     * @return Response
     */
    public function deleteQueryById(Request $request, string $queryId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $action = 'deleteQueryById';
        $payload = array(
            'success' => false,
            'action' => $action,
            'message' => 'success'
        );
        $statusCode = 401;

        try {
            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
            if (isset($user) and $user instanceof XDUser) {
                $queries = new \UserStorage($user, self::QUERIES_STORE);
                $query = $queries->getById($queryId);

                if (isset($query)) {
                    $before = count($queries->get());
                    $after = $queries->delById($queryId);
                    $success = $before > $after;
                    $payload['success'] = $success;
                    $payload['message'] = $success ? $payload['message'] : 'There was an error removing the query identified by: ' . $queryId;

                    $statusCode = $success ? 200 : 500;
                } else {
                    $payload['message'] = 'There was no query found for the given id';
                    $statusCode = 404;
                }
            } else {
                $payload['message'] = self::DEFAULT_ERROR_MESSAGE;
            }
        } catch (BadRequestException|HttpException|Exception $exception) {
            $payload['message'] = $exception->getMessage();
            $statusCode = (get_class($exception) === 'Exception') ? 500 : $exception->getStatusCode();
        }

        return $this->json($payload, $statusCode);
    }


    /**
     * @param XDUser $user
     * @param array $query
     * @return void
     * @throws Exception
     */
    private function removeRoleFromQuery(XDUser $user, array &$query)
    {
        // If the query doesn't have a config, stop.
        if (!array_key_exists('config', $query)) {
            return;
        }

        // If the query config doesn't have an active role, stop.
        $queryConfig = json_decode($query['config']);
        if (!property_exists($queryConfig, 'active_role')) {
            return;
        }

        // Remove the active role from the query config.
        $activeRoleId = $queryConfig->active_role;
        unset($queryConfig->active_role);

        // Check whether or not $activeRoleId is an acl name or acl display value.
        // ( Old queries may utilize the `display` property).
        $activeRole = Acls::getAclByName($activeRoleId);
        if ($activeRole === null) {
            $activeRole = Acls::getAclByDisplay($activeRoleId);
            if ($activeRole !== null) {
                $activeRoleId = $activeRole->getName();
            }
        }
        // Convert the active role into global filters.
        MetricExplorer::convertActiveRoleToGlobalFilters($user, $activeRoleId, $queryConfig->global_filters);

        // Store the updated config in the query.
        $query['config'] = json_encode($queryConfig);
    }
}