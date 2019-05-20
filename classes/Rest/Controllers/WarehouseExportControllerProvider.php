<?php

namespace Rest\Controllers;

use DataWarehouse\Export\QueryHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

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

        $controller
            ->get(
                "$root/warehouse/export/realms",
                __CLASS__ . '::getRealms'
            );

        $controller
            ->get(
                "$root/warehouse/export/requests",
                __CLASS__ . '::getRequests'
            );

        $controller
            ->post(
                "$root/warehouse/export/request",
                __CLASS__ . '::createRequest'
            );
    }

    /**
     * Get all the realms available for exporting for the current user.
     *
     * @param Request $request
     * @param Application $app
     * @return array
     * @throws AccessDeniedException
     */
    public function getRealms(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        // TODO
        return [
            'success' => true,
            'results' => [
                ['id' => 'jobs', 'name' => 'Jobs'],
                ['id' => 'supremm', 'name' => 'SUPReMM'],
                ['id' => 'accounts', 'name' => 'Accounts'],
                ['id' => 'allocations', 'name' => 'Allocations'],
                ['id' => 'requests', 'name' => 'Requests'],
                ['id' => 'resource_allocations', 'name' => 'Resource Allocations']
            ]
        ];
    }

    /**
     * Get all the existing export requests for the current user.
     *
     * @param Request $request
     * @param Application $app
     * @return array
     * @throws AccessDeniedException
     */
    public function getRequests(Request $request, Application $app)
    {
        $user = $this->authorize($request);
        $handler = new QueryHandler();
        $results = $handler->listRequestsForUser($user->getId());
        return ['success' => true, 'results' => $results];
    }

    /**
     * Create a new export request for the current user.
     */
    public function createRequest(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // TODO: Validate input.
        $realm = $this->getStringParam($request, 'realm');
        $startDate = $this->getStringParam($request, 'start_date');
        $endDate = $this->getStringParam($request, 'end_date');
        $format = $this->getStringParam($request, 'format');

        $handler = new QueryHandler();

        $handler->createRequestRecord(
            $user->getId(),
            $realm,
            $startDate,
            $endDate
        );

        return ['success' => true];
    }
}
