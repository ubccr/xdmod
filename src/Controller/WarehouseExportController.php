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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
        $this->logger = Log::factory(
            'data-warehouse-export-rest',
            [
                'console' => false,
                'file' => false,
                'mail' => false
            ]
        );
        $this->realmManager = new RealmManager();
        $this->queryHandler = new QueryHandler($this->logger);
    }


    /**
     * Get all the realms available for exporting for the current user.
     *
     * @param Request $request
     * @return Response
     * @throws UnauthorizedHttpException if there is no authorized user for this request.
     * @throws Exception if there is a problem authorizing the user making the request.
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
     * Get all the existing export requests for the current user.
     *
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
     * Create a new export request for the current user.
     *
     * @param Request $request
     * @return Response
     * @throws Exception if user is not authorized to access this route.
     */
    #[Route('/request', methods: ['POST'])]
    public function createRequest(Request $request): Response
    {
        $user = $this->authorize($request);
        $realm = $this->getStringParam($request, 'realm', true);

        $realms = array_map(
            function ($realm) {
                return $realm->getName();
            },
            $this->realmManager->getRealmsForUser($user)
        );
        if (!in_array($realm, $realms)) {
            throw new BadRequestHttpException('Invalid realm');
        }

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

        try {
            $id = $this->queryHandler->createRequestRecord(
                $user->getUserID(),
                $realm,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $format
            );
        } catch (Exception $e) {
            throw new BadRequestHttpException('Failed to create export request');
        }

        return $this->json([
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

        $this->logger->info(
            '',
            [
                'module' => self::LOG_MODULE,
                'message' => 'Sending data warehouse export file',
                'event' => 'DOWNLOAD',
                'id' => $id,
                'Users.id' => $user->getUserId()
            ]
        );

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
     * Delete a single request.
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
        $count = $this->queryHandler->deleteRequest($id, $user->getUserId());

        if ($count === 0) {
            throw new NotFoundHttpException('Export request not found');
        }

        $this->logger->info('', [
            'module' => self::LOG_MODULE,
            'message' => 'Deleted data warehouse export request',
            'event' => 'DELETE_BY_USER',
            'id' => $id,
            'Users.id' => $user->getUserId()
        ]);

        return $this->json([
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
        $user = $this->authorize($request);

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
                $count = $this->queryHandler->deleteRequest($id, $user->getUserId());
                if ($count === 0) {
                    throw new NotFoundHttpException('Export request not found');
                }
                $this->logger->info(
                    '',
                    [
                        'module' => self::LOG_MODULE,
                        'message' => 'Deleted data warehouse export request',
                        'event' => 'DELETE_BY_USER',
                        'id' => $id,
                        'Users.id' => $user->getUserId()
                    ]
                );
            }

            $dbh->commit();
        } catch (NotFoundHttpException $e) {
            $dbh->rollBack();
            throw $e;
        } catch (Exception $e) {
            $dbh->rollBack();
            throw new BadRequestHttpException('Failed to delete export requests');
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
}
