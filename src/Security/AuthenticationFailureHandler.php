<?php

namespace Access\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;

/**
 * This handler is what is responsible for intercepting and interpreting exceptions thrown during the SSO authentication
 * process
 */
class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, UrlGeneratorInterface $router, array $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($httpKernel, $httpUtils, $options, $logger);
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $options = $this->options;
        $failureUrl = ParameterBagUtils::getRequestParameterValue($request, $options['failure_path_parameter']);

        if (\is_string($failureUrl) && (str_starts_with($failureUrl, '/') || str_starts_with($failureUrl, 'http'))) {
            $options['failure_path'] = $failureUrl;
        } elseif ($this->logger && $failureUrl) {
            $this->logger->debug(sprintf('Ignoring query parameter "%s": not a valid URL.', $options['failure_path_parameter']));
        }

            $options['failure_path'] ?? $options['failure_path'] = $options['login_path'];

        if ($options['failure_forward']) {
            if (null !== $this->logger) {
                $this->logger->debug('Authentication failure, forward triggered.', ['failure_path' => $options['failure_path']]);
            }

            $subRequest = $this->httpUtils->createRequest($request, $options['failure_path']);
            $subRequest->attributes->set(Security::AUTHENTICATION_ERROR, $exception);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        if (null !== $this->logger) {
            $this->logger->debug('Authentication failure, redirect triggered.', ['failure_path' => $options['failure_path']]);
        }

        return new RedirectResponse(
            $this->router->generate(
                $options['failure_path'], /* which path to redirect to, by default this is xdmod_home */
                ['error' => $exception->getMessage()] /* These are additional query parameters to include in the request. */
            )
        );
    }
}
