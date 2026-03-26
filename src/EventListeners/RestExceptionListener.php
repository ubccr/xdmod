<?php
declare(strict_types=1);
namespace CCR\EventListeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function xd_response\buildError;

/**
 * This class' responsibility is to ensure that when an exception occurs we don't scare our front end w/ HTTP status codes
 * that aren't 200.
 *
 * The integration of this class as a listener is done via the PHP Attribute `AsEventListener`, so no further
 * configuration is required to get it working.
 */
#[AsEventListener('kernel.exception')]
class RestExceptionListener
{

    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Make sure we log that the exception occurs.
        $this->logger->error(
            sprintf(
                'Exception [%s] %s - %s#%s',
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            )
        );

        // Capture any headers set by the exception.
        $headers = [];
        if ($exception instanceof HttpException) {
            $headers = $exception->getHeaders();
        }

        // This allows the 200 status code we set to be what's returned to the requester.
        $event->allowCustomResponseCode();

        // Note, Setting the response will stop the future event listeners from firing.
        $event->setResponse(new JsonResponse(buildError($exception), 200, $headers));
    }
}
