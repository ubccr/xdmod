<?php

namespace Rest\Controllers;

use CCR\DB;
use DataWarehouse\Export\QueryHandler;
use Exception;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WarehouseExportControllerProvider extends BaseControllerProvider
{
    /**
     * Set up data warehouse export routes.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     */
    public function setupRoutes(
        Application $app,
        ControllerCollection $controller
    ) {
        $root = $this->prefix;
        $current = get_class($this);
        $conversions = '\Rest\Utilities\Conversions';

        $controller->get("$root/realms", "$current::getRealms");
        $controller->get("$root/requests", "$current::getRequests");
        $controller->post("$root/request", "$current::createRequest");

        $controller->get("$root/request/{id}", "$current::getRequest")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller->delete("$root/request/{id}", "$current::deleteRequest")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller->delete("$root/requests/{id}", "$current::deleteRequests");
    }

    /**
     * Get all the realms available for exporting for the current user.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function getRealms(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // TODO: Determine realms using user ACLs.
        $realms = [
            ['id' => 'jobs', 'name' => 'Jobs'],
            ['id' => 'supremm', 'name' => 'SUPReMM'],
            ['id' => 'accounts', 'name' => 'Accounts'],
            ['id' => 'allocations', 'name' => 'Allocations'],
            ['id' => 'requests', 'name' => 'Requests'],
            ['id' => 'resource_allocations', 'name' => 'Resource Allocations']
        ];

        return $app->json(
            [
                'success' => true,
                'data' => $realms,
                'total' => count($realms)
            ]
        );
    }

    /**
     * Get all the existing export requests for the current user.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function getRequests(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $handler = new QueryHandler();
        $results = $handler->listUserRequestsByState($user->getUserId());
        return $app->json(['success' => true, 'data' => $results]);
    }

    /**
     * Create a new export request for the current user.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws BadRequestHttpException
     */
    public function createRequest(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $realm = $this->getStringParam($request, 'realm', true);

        // TODO: Validate realm from user ACLs.
        if (!in_array(
            $realm,
            [
                'jobs',
                'supremm',
                'accounts',
                'allocations',
                'requests',
                'resource_allocations'
            ]
        )) {
            throw new BadRequestHttpException('Invalid realm');
        }

        $startDate = $this->getDateFromISO8601Param($request, 'start_date', true);
        $endDate = $this->getDateFromISO8601Param($request, 'end_date', true);

        $interval = $startDate->diff($endDate);

        if ($interval === false) {
            throw new BadRequestHttpException('Failed to calculate date interval');
        }

        if ($interval->invert === 1) {
            throw new BadRequestHttpException('Start date must be before end date');
        }

        $format = strtoupper($this->getStringParam($request, 'format', true));

        if (!in_array($format, ['CSV', 'JSON'])) {
            throw new BadRequestHttpException('format must be CSV or JSON');
        }

        $handler = new QueryHandler();

        $id = $handler->createRequestRecord(
            $user->getUserId(),
            $realm,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $format
        );

        return $app->json([
            'success' => true,
            'message' => 'Created export request',
            'data' => ['id' => $id]
        ]);
    }

    /**
     * Get the requested data.
     *
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function getRequest(Request $request, Application $app, $id)
    {
        $user = $this->authorize($request);
        $handler = new QueryHandler();

        $requests = array_filter(
            $handler->listUserRequestsByState($user->getUserId()),
            function ($request) use ($id) {
                return $request['id'] === $id;
            }
        );

        if (count($requests) === 0) {
            throw new NotFoundHttpException();
        }

        $request = $requests[0];

        if ($request['state'] !== 'Available') {
            throw new BadRequestHttpException('Requested data is not available');
        }

        try {
            $exportDir = xd_utilities\getConfiguration('data_warehouse_export', 'export_directory');
        } catch (Exception $e) {
            throw new BadRequestHttpException('Export directory is not configured');
        }

        $file = sprintf(
            '%s/%s.%s',
            $exportDir,
            $id,
            strtolower($request['format'])
        );

        if (!is_readable($file)) {
            throw new AccessDeniedHttpException();
        }

        return $this->sendFile($file);
    }

    /**
     * Delete a single request.
     *
     * @param Request $request
     * @param Application $app
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws NotFoundHttpException
     */
    public function deleteRequest(Request $request, Application $app, $id)
    {
        $user = $this->authorize($request);
        $handler = new QueryHandler();
        $count = $handler->deleteRequest($id, $user->getUserId());

        if ($count === 0) {
            throw new NotFoundHttpException();
        }

        return $app->json([
            'success' => true,
            'message' => 'Deleted export request'
        ]);
    }

    /**
     * Delete multiple requests.
     *
     * The request body content must be a JSON encoded array of request IDs.
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws NotFoundHttpException
     */
    public function deleteRequests(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $handler = new QueryHandler();

        $requestIds = [];

        try {
            $requestIds = json_decode($request->getContent());

            if ($requestIds === null) {
                throw new Exception('Failed to decode JSON');
            }

            if (!is_array($requestIds)) {
                throw new Exception('Request IDs must be in an array');
            }

            foreach ($requestIds as $id) {
                if (!is_int($id)) {
                    throw new Exception('Request IDs must integers');
                }
            }
        } catch (Exception $e) {
            throw new BadRequestHttpException(
                'Malformed HTTP request content: ' . $e->getMessage()
            );
        }

        try {
            $dbh = DB::factory('database');
            $dbh->beginTransaction();

            foreach ($requestIds as $id) {
                $count = $handler->deleteRequest($id, $user->getUserId());
                if ($count === 0) {
                    throw new NotFoundHttpException();
                }
            }

            $dbh->commit();
        } catch (NotFoundHttpException $e) {
            $dbh->rollBack();
            throw $e;
        } catch (Exception $e) {
            $dbh->rollBack();
            throw new BadRequestHttpException('Failed to delete export requests');
        }

        return $app->json([
            'success' => true,
            'message' => 'Deleted export requests'
        ]);
    }
}
