<?php

namespace Access\Errors;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * This controller will turn all unhandled exceptions into json responses that follow the XDMoD format.
 */
class ErrorController extends \Symfony\Component\HttpKernel\Controller\ErrorController
{

    /**
     * Specifically designed to work with instances of FlattenException. We return a JsonResponse due to XDMoD expecting
     * errors in this way.
     *
     * @param Throwable $exception
     * @return Response
     */
    public function __invoke(Throwable $exception): Response
    {
        $headers = [];
        if (method_exists($exception, 'getHeaders')) {
            $headers = $exception->getHeaders();
        }

        return new JsonResponse([
            'success' => false,
            'message' => $exception->getMessage()
        ], 200, $headers);
    }

}