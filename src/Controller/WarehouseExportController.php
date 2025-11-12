<?php

namespace CCR\Controller;

use CCR\Security\Helpers\Tokens;
use CCR\DB;
use DataWarehouse\Data\RawStatisticsConfiguration;
use DataWarehouse\Export\FileManager;
use DataWarehouse\Export\QueryHandler;
use DataWarehouse\Export\RealmManager;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use function xd_response\buildError;

/**
 *
 */
#[Route('{prefix}/warehouse/export', requirements: ['prefix' => '.*'])]
class WarehouseExportController extends BaseController
{
    /**
     *
     */
    private const LOG_MODULE = 'data-warehouse-export';


    /**
     * @var RealmManager
     */
    private $realmManager;

    /**
     * @var QueryHandler
     */
    private $queryHandler;

    /**
     * @throws Exception if unable to instantiate the logger.
     */
    public function __construct(LoggerInterface $logger, Environment $twig, Tokens $tokenHelper)
    {
        parent::__construct($logger, $twig, $tokenHelper);

        $this->realmManager = new RealmManager();
        $this->queryHandler = new QueryHandler($this->logger);
    }


    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if user is not authorized to access this route.
     */
    #[Route('/realms', methods: ['GET'])]
    public function getRealms(Request $request): Response
    {
        $user = null;

        // We need to wrap the token authentication because we want the token authentication to be optional, proceeding
        // to the normal session authentication if a token is not provided.
        try {
            $user = $this->tokenHelper->authenticate($request, false);
        } catch (Exception $e) {
            // NOOP
        }

        if ($user === null) {
            $user = $this->authorize($request);
        }

        $config = RawStatisticsConfiguration::factory();

        $realms = array_map(
            function ($realm) use ($config) {
                $name = $realm->getName();
                return [
                    'id' => $name,
                    'name' => $realm->getDisplay(),
                    'fields' => $config->getBatchExportFieldDefinitions($name)
                ];
            },
            $this->realmManager->getRealmsForUser($user)
        );

        return $this->json(
            [
                'success' => true,
                'data' => array_values($realms),
                'total' => count($realms)
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/requests', methods: ['GET'])]
    public function getRequests(Request $request): Response
    {
        $user = $this->authorize($request);
        $results = $this->queryHandler->listUserRequestsByState($user->getUserId());
        return $this->json(
            [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ]
        );
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if user is not authorized to access this route.
     */
    #[Route('/request', methods: ['POST'])]
    public function createRequest(Request $request): Response
    {
        $this->logger->debug('Creating Request');
        $user = $this->authorize($request);

        $this->logger->debug('User is Authenticated');

        $realm = $this->getStringParam($request, 'realm', true);

        $realms = array_map(
            function ($realm) {
                return $realm->getName();
            },
            $this->realmManager->getRealmsForUser($user)
        );
        if (!in_array($realm, $realms)) {
            $this->logger->debug('Invalid Realm');
            throw new BadRequestHttpException('Invalid realm');
        }
        $this->logger->debug('Realm is valid');

        $startDate = $this->getDateFromISO8601Param($request, 'start_date', true);
        $endDate = $this->getDateFromISO8601Param($request, 'end_date', true);
        $now = new DateTime();

        if ($startDate > $now) {
            $this->logger->debug('Start Date is invalid');
            throw new BadRequestHttpException('Start date cannot be in the future');
        }

        $this->logger->debug('Start Date is valid.');

        if ($endDate > $now) {
            $this->logger->debug('End Date is invalid');
            throw new BadRequestHttpException('End date cannot be in the future');
        }

        $this->logger->debug('End Date is valid');

        $interval = $startDate->diff($endDate);

        if ($interval === false) {
            $this->logger->debug('Interval is Invalid');
            throw new BadRequestHttpException('Failed to calculate date interval');
        }
        $this->logger->debug('Interval is valid');

        if ($interval->invert === 1) {
            $this->logger->debug('Interval is invalid');
            throw new BadRequestHttpException('Start date must be before end date');
        }

        $format = strtoupper($this->getStringParam($request, 'format', true));

        if (!in_array($format, ['CSV', 'JSON'])) {
            $this->logger->debug('Format is invalid');
            throw new BadRequestHttpException('format must be CSV or JSON');
        }

        try {
            $this->logger->debug('Creating Export Request');
            $id = $this->queryHandler->createRequestRecord(
                $user->getUserID(),
                $realm,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $format
            );
        } catch (Exception $e) {
            $this->logger->debug('Failed to create export request');
            throw new BadRequestHttpException('Failed to create export request');
        }

        $this->logger->debug('Created Export Request');
        return $this->json([
            'success' => true,
            'message' => 'Created export request',
            'data' => [['id' => $id]],
            'total' => 1
        ]);
    }

    /**
     *
     *
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws Exception if the user is not authorized for this route.
     * @throws NotFoundHttpException if there were no requests for the provided id.
     * @throws NotFoundHttpException if the file for the request identified by the provided id is not found on the file system.
     * @throws BadRequestHttpException if the request that corresponds to the provided id is not in the Available state.
     * @throws AccessDeniedHttpException if the file for the request identified by the provided id is not readable.
     */
    #[Route('/download/{id}', requirements: ["id" => "\d+"], methods: ['GET'])]
    public function getExportedDataFile(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->authorize($request);

        $requests = array_filter(
            $this->queryHandler->listUserRequestsByState($user->getUserID()),
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

        $fileManager = new FileManager();
        $file = $fileManager->getExportDataFilePath($id);

        if (!is_file($file)) {
            throw new NotFoundHttpException('Exported data not found');
        }

        if (!is_readable($file)) {
            throw new AccessDeniedHttpException('Exported data is not readable');
        }

        $this->logger->info('Sending data warehouse export file');

        if ($request['downloaded_datetime'] === null) {
            $this->queryHandler->updateDownloadedDatetime($request['id']);
        }
        return new BinaryFileResponse(
            $file,
            200,
            [
                'Content-type' => 'application/zip',
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $fileManager->getZipFileName($request)
                )
            ]
        );
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception if the user is not authorized to access this route.
     * @throws BadRequestHttpException if the provided request ids are not in a json decodable format
     * @throws BadRequestHttpException if the provided request ids are not in a json array.
     * @throws BadRequestHttpException if any of the provided request ids are not integers.
     * @throws HttpException if the sql delete operation fails.
     * @throws NotFoundHttpException if any of the provided request ids are not found.
     *
     */
    #[Route('/requests', methods: ['DELETE'])]
    public function deleteRequests(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->authorize($request);

        $requestIds = [];

        try {
            $this->logger->debug(var_export($request->request->all(), true));
            $requestIds = json_decode($request->get('ids'));
            $this->logger->debug(sprintf('Request ids: %s', var_export($requestIds, true)));
            if ($requestIds === null) {
                throw new Exception('Failed to decode JSON');
            }

            if (!is_array($requestIds)) {
                throw new Exception('Export request IDs must be in an array');
            }

            try {
                $requestIds = array_map(
                    function ($value) {
                        return is_int($value) ? $value : intval($value);
                    },
                    $requestIds
                );
            } catch (Exception $e) {
                throw new Exception('Export request IDs must integers');
            }

        } catch (Exception $e) {
            return $this->json(buildError('Malformed HTTP request content: ' . $e->getMessage()));
        }

        try {
            $dbh = DB::factory('database');
            $dbh->beginTransaction();

            foreach ($requestIds as $id) {
                $count = $this->queryHandler->deleteRequest($id, $user->getUserId());
                if ($count === 0) {
                    throw new NotFoundHttpException('Export request not found');
                }

                $this->logger->info('Deleted data warehouse export request');
            }

            $dbh->commit();
        } catch (NotFoundHttpException $e) {
            $dbh->rollBack();
            throw $e;
        } catch (Exception $e) {
            $dbh->rollBack();
            throw new HttpException(500, 'Failed to delete export requests');
        }

        return $this->json([
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

    /**
     *
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws Exception
     */
    #[Route('/request/{id}', requirements: ["id" => "\w+"], methods: ['DELETE'])]
    public function deleteRequest(Request $request, string $id): Response
    {
        $user = $this->authorize($request);

        $count = $this->queryHandler->deleteRequest($id, $user->getUserID());

        if ($count === 0) {
            throw new NotFoundHttpException('Export request not found');
        }

        $this->logger->info('Deleted data warehouse export request');

        return $this->json([
            'success' => true,
            'message' => 'Deleted export request',
            'data' => [['id' => $id]],
            'total' => 1
        ]);
    }

}
