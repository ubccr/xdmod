<?php

namespace CCR\EventListeners;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    public function __construct(
        private Security $security
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $exception = $event->getThrowable();

        // Support Legacy format for the Internal Dashboard controller endpoints
        if (str_starts_with($route, 'ccr_internaldashboard')) {
            if ($exception instanceof AccessDeniedHttpException || $exception instanceof AccessDeniedException) {
                $statusCode = Response::HTTP_OK;
                $content = [
                    'status' => 'not_a_manager',
                    'success' => false,
                    'totalCount' => 0,
                    'message' => 'not_a_manager',
                    'data' => array()
                ];

                // For src/Controller/InternalDashboard/AdminController::resetUserTourViewed
                if (str_ends_with($route, 'resetusertourviewed')) {
                    $statusCode = Response::HTTP_FORBIDDEN;
                    $content = [
                        'success' => false,
                        'count' => 0,
                        'total' => 0,
                        'totalCount' => 0,
                        'results' => [],
                        'data' => [],
                        'message' => 'An error was encountered while attempting to process the requested authorization procedure.',
                        'code' => 0
                    ];
                }

                // This is specifically for ControllerTest::testSabRejectsPublic, it expects a 401.
                if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
                    $statusCode = Response::HTTP_UNAUTHORIZED;
                }

                $event->allowCustomResponseCode();
                $event->setResponse(new JsonResponse($content, $statusCode));

            } elseif ($exception instanceof UnauthorizedHttpException) {
                $event->setResponse(new JsonResponse([
                    'success' => false,
                    'count' => 0,
                    'total' => 0,
                    'totalCount' => 0,
                    'results' => array(),
                    'data' => array(),
                    'message' => 'Session Expired',
                    'code' => 2
                ]));
            }
            return;
        }
    }
}
