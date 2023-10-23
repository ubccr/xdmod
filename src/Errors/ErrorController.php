<?php

namespace Access\Errors;

use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;
use function xd_response\buildError;

/**
 * This controller will turn all unhandled exceptions into json responses that follow the XDMoD format.
 */
class ErrorController extends \Symfony\Component\HttpKernel\Controller\ErrorController
{
    public function __construct(HttpKernelInterface $kernel, $controller, ErrorRendererInterface $errorRenderer)
    {
        parent::__construct($kernel, $controller, $errorRenderer);
    }

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

        $message = $exception->getMessage();
        $userPos = strpos($message, 'User');
        $alreadyExistsPos = strpos($message, 'already exists');
        if ($userPos && $alreadyExistsPos) {
            return new RedirectResponse('/');
        }

        return new JsonResponse(buildError($exception), 200, $headers);
    }

}
