<?php

namespace Rest\Controllers;

use CCR\DB;
use DataWarehouse\Export\QueryHandler;
use DateTime;
use Exception;
use Models\Services\Realms;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use xd_utilities;

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
        $controller->post("$root/request", "$current::createRequest");
        $controller->get("$root/requests", "$current::getRequests");
        $controller->delete("$root/requests", "$current::deleteRequests");

        $controller->get("$root/request/{id}", "$current::getRequest")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller->delete("$root/request/{id}", "$current::deleteRequest")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");
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
        $userRealms = Realms::getRealmsForUser($user); // XXX Returns data from moddb.realms.display column.

        // TODO: Get list of exportable realms.
        $realms = [
            ['id' => 'jobs', 'name' => 'Jobs'],
            ['id' => 'supremm', 'name' => 'SUPReMM'],
            ['id' => 'accounts', 'name' => 'Accounts'],
            ['id' => 'allocations', 'name' => 'Allocations'],
            ['id' => 'requests', 'name' => 'Requests'],
            ['id' => 'resourceallocations', 'name' => 'ResourceAllocations']
        ];

        $realms = array_filter(
            $realms,
            function ($realm) use ($userRealms) {
                return in_array($realm['name'], $userRealms);
            }
        );

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
        return $app->json(
            [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ]
        );
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
        $userRealms = Realms::getRealmsForUser($user); // XXX Returns data from moddb.realms.display column.

        $realm = $this->getStringParam($request, 'realm', true);

        // TODO: Get list of exportable realms.
        $realms = [
            'jobs',
            'supremm',
            'accounts',
            'allocations',
            'requests',
            'resourceallocations'
        ];
        if (!in_array($realm, $realms)) {
            throw new BadRequestHttpException('Invalid realm');
        }
        // TODO: Check that realm is in list of exportable realms.
        //if (!in_array($realm, $userRealms)) {
        //    throw new BadRequestHttpException('Invalid realm');
        //}

        $startDate = $this->getDateFromISO8601Param($request, 'start_date', true);
        $endDate = $this->getDateFromISO8601Param($request, 'end_date', true);
        $now = new DateTime();

        if ($startDate > $now) {
            throw new BadRequestHttpException('Start date cannot be in the future');
        }

        if ($endDate > $now) {
            throw new BadRequestHttpException('End date cannot be in the future');
        }

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
            'data' => [['id' => $id]],
            'total' => 1
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
                return $request['id'] == $id;
            }
        );

        if (count($requests) === 0) {
            throw new NotFoundHttpException('Export request not found');
        }

        // Using `array_shift` because `array_filter` preserves keys so the
        // request may not be at index 0.
        $request = array_shift($requests);

        if ($request['state'] !== 'Available') {
            throw new BadRequestHttpException('Requested data is not available');
        }

        try {
            $exportDir = xd_utilities\getConfiguration('data_warehouse_export', 'export_directory');
        } catch (Exception $e) {
            throw new NotFoundHttpException('Export directory is not configured');
        }

        $file = sprintf(
            '%s/%s.%s.zip',
            $exportDir,
            $id,
            strtolower($request['export_file_format'])
        );

        if (!is_file($file)) {
            throw new NotFoundHttpException('Exported data not found');
        }

        if (!is_readable($file)) {
            throw new AccessDeniedHttpException('Exported data is not readable');
        }

        $fileName = sprintf(
            '%s--%s-%s.%s.zip',
            $request['realm'],
            $request['start_date'],
            $request['end_date'],
            strtolower($request['export_file_format'])
        );

        return $app->sendFile(
            $file,
            200,
            [
                'Content-type' => 'application/zip',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName)
            ]
        );
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
            throw new NotFoundHttpException('Export request not found');
        }

        return $app->json([
            'success' => true,
            'message' => 'Deleted export request',
            'data' => [['id' => $id]],
            'total' => 1
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
            $requestIds = @json_decode($request->getContent());

            if ($requestIds === null) {
                throw new Exception('Failed to decode JSON');
            }

            if (!is_array($requestIds)) {
                throw new Exception('Export request IDs must be in an array');
            }

            foreach ($requestIds as $id) {
                if (!is_int($id)) {
                    throw new Exception('Export request IDs must integers');
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
                    throw new NotFoundHttpException('Export request not found');
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
            'message' => 'Deleted export requests',
            'data' => array_map(
                function ($id) {
                    return ['id' => $id];
                },
                $requestIds
            ),
            'total' => count($requestIds)
        ]);
    }
}
