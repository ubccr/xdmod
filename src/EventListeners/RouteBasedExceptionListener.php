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
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

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
    public function __construct(private Security $security, private LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->logger->debug('Running RouteBasedExceptionListener');
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $exception = $event->getThrowable();
        $event->allowCustomResponseCode();
        $this->logger->debug("Exception occurred:", [$exception]);
        $statusCode = Response::HTTP_UNAUTHORIZED;
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
        // For src/Controller/InternalDashboard/AdminController::resetUserTourViewed
        if ($route == 'ccr_internaldashboard_admin_resetusertourviewed') {
             if ($exception instanceof AccessDeniedHttpException) {
                $response = new JsonResponse($content, Response::HTTP_FORBIDDEN);
                $event->setResponse($response);
             } elseif ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_FORBIDDEN) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, Response::HTTP_UNAUTHORIZED);
                    $event->setResponse($response);
                }
            }
        // Support Legacy format for the Internal Dashboard controller endpoints
        } elseif ($route == 'ccr_chartpool_index') {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif ($route == 'ccr_dashboard_setviewedusertour') {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_dashboard_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_internaldashboard_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Reponse::HTTP_FORBIDDEN) {
                    // This is specifically for ControllerTest::testSabRejectsPublic
                    if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
                        $statusCode = Response::HTTP_OK;
                    }
                    $content = [
                        'status' => 'not_a_manager',
                        'success' => false,
                        'totalCount' => 0,
                        'message' => 'not_a_manager',
                        'data' => array()
                    ];
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif ($route == 'ccr_metricexplorer_createquery') {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content = [
                        'success' => false,
                        'message' => $error_during_authorization_message,
                        'action' => 'creatQuery',
                    ];
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif ($route == 'ccr_metricexplorer_updatequerybyid') {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content = [
                        'success' => false,
                        'message' => $error_during_authorization_message,
                        'action' => 'updateQuery',
                    ];
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_metricexplorer_')) {
            if ($exception instanceof UnauthorizedHttpException) {
                $content['message'] = $exception->getMessage();
                $content['code'] = 0;
                $response = new JsonResponse($content, $statusCode);
                $event->setResponse($response);
            } elseif ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_organization_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_FORBIDDEN) {
                    $content = [
                        'success' => false,
                        'status' => 'not_a_center_director',
                        'message' => 'not_a_center_director',
                        'totalCount' => 0,
                        'data' => array(),
                    ];
                    $response = new JsonResponse($content, Response::HTTP_OK);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_reportbuilder_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_user_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif (
            str_starts_with($route, 'ccr_warehouse_searchhistory')
            || str_starts_with($route, 'ccr_warehouse_createhistory')
            || str_starts_with($route, 'ccr_warehouse_gethistorybyid')
            || str_starts_with($route, 'ccr_warehouse_deletehistory')
            || str_starts_with($route, 'ccr_warehouse_deleteallhistory')
            || str_starts_with($route, 'ccr_warehouse_searchjobsby')
            || str_starts_with($route, 'ccr_warehouse_getaggregatedata')
            || str_starts_with($route, 'ccr_warehouse_getdimensions')
            || str_starts_with($route, 'ccr_warehouse_getdimensionvalues')
            || str_starts_with($route, 'ccr_warehouse_getrawdata')
        ) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, Response::HTTP_UNAUTHORIZED);
                    $event->setResponse($response);
                }
           }
        } elseif (str_starts_with($route, 'ccr_warehouse_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, Response::HTTP_OK);
                    $event->setResponse($response);
                }
            }
        } elseif (str_starts_with($route, 'ccr_warehouseexport_')) {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        } elseif ($route == 'get_current_user') {
            if ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                }
                $response = new JsonResponse($content, $statusCode);
                $event->setResponse($response);
            }
        } elseif ($route == 'legacy_user_interface') {
            if ($exception instanceof UnauthorizedHttpException) {
                $content['message'] = $exception->getMessage();
                $content['code'] = 0;
                $response = new JsonResponse($content, $statusCode);
                $event->setResponse($response);
            } elseif ($exception instanceof HttpException) {
                if ($statusCode == Response::HTTP_UNAUTHORIZED) {
                    $content['message'] = $error_during_authorization_message;
                    $content['code'] = 0;
                    $response = new JsonResponse($content, $statusCode);
                    $event->setResponse($response);
                }
            }
        }

    }
}
