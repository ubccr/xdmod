<?php

namespace Rest\Controllers;

use DataWarehouse\Export\QueryHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $controller->get("$root/realms", "$current::getRealms");
        $controller->get("$root/requests", "$current::getRequests");
        $controller->post("$root/request", "$current::createRequest");
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
     * @return array
     * @throws AccessDeniedException
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

        return $app->json(['success' => true, 'data' => ['id' => $id]]);
    }
}
