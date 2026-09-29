<?php

namespace CCR\EventListeners;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * This event listener is intended to be used to format excpetion
 * messages identically to the pre-symfony version of XDMoD.
 * This is done to ensure compatibility with the existing js frontend
 * code. The intent is to remove this compatiblity conversion in a
 * future release (after the frontend code is modified to accept 'standard'
 * exceptions.
 */
class RouteBasedExceptionListener
{
    public function __construct(private LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->logger->debug('Running RoutebasedExceptionListener');
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $exception = $event->getThrowable();
        $event->allowCustomResponseCode();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        $this->logger->debug("Received response:", $response);
        $this->logger->debug("Received status code:", $statusCode);

        $content = [
            'success' => false,
            'count' => 0,
            'total' => 0,
            'totalCount' => 0,
            'results' => array(),
            'data' => array(),
            'message' => 'Session Expired',
            'code' => 2
        ];
        $error_during_authorization_message = 'An error was encountered while attempting to process the requested authorization procedure.';
        // Support Legacy format for the Internal Dashboard controller endpoints
        if (
            $exception instanceof AccessDeniedHttpException
            || $exception instanceof AccessDeniedException
        ) {
            if (str_starts_with($route, 'ccr_internaldashboard_')) {
                    $statusCode = Response::HTTP_OK;
                    $content = [
                        'status' => 'not_a_manager',
                        'success' => false,
                        'totalCount' => 0,
                        'message' => 'not_a_manager',
                        'data' => array()
                    ];

                    // For src/Controller/InternalDashboard/AdminController::resetUserTourViewed
                    if (str_ends_with($route, '_resetusertourviewed')) {
                        $statusCode = Response::HTTP_FORBIDDEN;
                        $content = [
                            'success' => false,
                            'count' => 0,
                            'total' => 0,
                            'totalCount' => 0,
                            'results' => [],
                            'data' => [],
                            'message' => $error_during_authorization_message,
                            'code' => 0
                        ];
                    }

                    // This is specifically for ControllerTest::testSabRejectsPublic, it expects a 401.
                    if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
                        $statusCode = Response::HTTP_UNAUTHORIZED;
                    }
            } elseif (
                $route == 'ccr_organization_upgrademember'
                || $route == 'ccr_organization_downgrademember'
                || $route == 'ccr_organization_index'
            ) {
                $content = [
                    "status" => "not_a_center_director",
                    "success" => false,
                    "totalCount" => 0,
                    "message" => "not_a_center_director",
                    "data" => []
                ];
                $statusCode = Response::HTTP_OK;
            }
        } elseif ($exception instanceof UnauthorizedHttpException) {
            if (str_starts_with($route, 'ccr_warehouseexport_')) {
                $content['message'] = $error_during_authorization_message;
                $content['code'] = 0;
            } elseif ($route == 'legacy_user_interface') {
                $statusCode = Response::HTTP_UNAUTHORIZED;
            }
        } elseif ($exception instanceof AuthenticationException) {
            if ($route == 'ccr_metricexplorer_createquery') {
                # Yes, this is supposed to be 'creatQuery' without an 'e'
                $content['action'] = 'creatQuery';
                $content['message'] = $error_during_authorization_message;
                unset($content['code']);
                unset($content['total']);
                unset($content['totalCount']);
                unset($content['results']);
                unset($content['data']);
                unset($content['count']);
            } elseif ($route == 'ccr_metricexplorer_updatequerybyid') {
                $content['action'] = 'updateQuery';
                $content['message'] = $error_during_authorization_message;
                unset($content['code']);
                unset($content['total']);
                unset($content['totalCount']);
                unset($content['results']);
                unset($content['data']);
                unset($content['count']);
            } elseif (
                $route == 'ccr_warehouseexport_createrequest'
                || $route == 'ccr_warehouseexport_getrequests'
                || $route == 'ccr_warehouseexport_getrealms'
                || str_starts_with($route, 'ccr_warehouse_getdimensions')
                || str_starts_with($route, 'ccr_warehouse_getaggregatedata')
                || str_starts_with($route, 'ccr_warehouse_searchhistory')
                || $route == 'ccr_dashboard_setlayout'
                || $route == 'ccr_user_getcurrentapitoken'
                || $route == 'get_current_user'
                || $route == 'ccr_user_createapitoken'
                || $route == 'ccr_internaldashboard_admin_resetusertourviewed'
                || str_starts_with($route,'ccr_warehouse_searchjobs')
            ) {
                $content['message'] = $error_during_authorization_message;
                $content['code'] = 0;
            } elseif (
                $route == 'ccr_organization_upgrademember'
                || $route == 'ccr_organization_downgrademember'
                || $route == 'ccr_organization_index'
            ) {
                $content = [
                    "status" => "not_a_center_director",
                    "success" => false,
                    "totalCount" => 0,
                    "message" => "not_a_center_director",
                    "data" => []
                ];
                $statusCode = Response::HTTP_OK;
            } elseif ($route == 'ccr_chartpool_index') {
                $statusCode = Response::HTTP_OK;
            }
        } elseif ($exception instanceof NotFoundException) {
            if ($route == 'ccr_user_createapitoken') {
                $content = [
                    'message' => 'API token not found.'
                ];
                $statusCode = Response::HTTP_NOT_FOUND;
            }
        }
        $response = new JsonResponse($content, $statusCode);
        $event->setResponse($response);
        return;
    }
}
