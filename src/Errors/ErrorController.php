<?php

namespace CCR\Errors;

use CCR\Helper\HttpCodeMessages;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;
use XDException;
use function xd_response\buildError;

/**
 * This controller will turn all unhandled exceptions into json responses that follow the XDMoD format.
 */
class ErrorController extends \Symfony\Component\HttpKernel\Controller\ErrorController
{
    protected LoggerInterface $logger;
    public function __construct(HttpKernelInterface $kernel, $controller, ErrorRendererInterface $errorRenderer, LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $this->logger->error('Exception Code: '.$exception->getCode());
        $this->logger->error('Message: '.$exception->getMessage());
        $this->logger->error('Origin: '.$exception->getFile().' (line '.$exception->getLine().')');

        $stringTrace = (get_class($exception) == 'UniqueException') ? $exception->getVerboseTrace() : $exception->getTraceAsString();

        $this->logger->error("Trace:\n".$stringTrace."\n-------------------------------------------------------");

        $httpCode = 500;
        $headers = array();
        $isServerContext = isset($_SERVER['SERVER_PROTOCOL']);
        if ($isServerContext) {
            $uncheckedExceptionHttpCode = null;
            if ($exception instanceof XDException) {
                $uncheckedExceptionHttpCode = $exception->httpCode;
                $headers = $exception->headers;
            } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $uncheckedExceptionHttpCode = $exception->getStatusCode();
                $headers = $exception->getHeaders();
            }

            if ($uncheckedExceptionHttpCode !== null) {
                $this->logger->error('Unchecked Http Code', [$uncheckedExceptionHttpCode]);
                $this->logger->error('Unchecked Http Code exists', [array_key_exists($uncheckedExceptionHttpCode, HttpCodeMessages::$messages)]);
                if (array_key_exists($uncheckedExceptionHttpCode, HttpCodeMessages::$messages)) {
                    $httpCode = $uncheckedExceptionHttpCode;
                }
            }
        }

        $message = $exception->getMessage();
        $userPos = strpos($message, 'User');
        $alreadyExistsPos = strpos($message, 'already exists');
        if ($userPos && $alreadyExistsPos) {
            return new RedirectResponse('/');
        }

        return new JsonResponse(buildError($exception), $httpCode, $headers);
    }

}
