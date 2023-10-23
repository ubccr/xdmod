<?php
declare(strict_types=1);

namespace Access\Security\Authenticators;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class SessionAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    use TargetPathTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var bool[]|string[]
     */
    private $options;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param LoggerInterface $logger
     * @param HttpUtils $httpUtils
     * @param array $options
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(LoggerInterface $logger, HttpUtils $httpUtils, array $options, UrlGeneratorInterface $urlGenerator)
    {
        $this->logger = $logger;
        $this->httpUtils = $httpUtils;
        $this->options = array_merge([
            'xdmod_session' => 'xdUser',
            'dashboard_session' => 'xdDashboardUser',
            'check_path' => '/login',
        ], $options);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns true, authenticate() will be called. If false, the authenticator will be skipped.
     *
     * Returning null means authenticate() can be called lazily when accessing the token storage.
     */
    public function supports(Request $request): bool
    {
        $session = $request->getSession();
        $hasXdmodSession = (!$this->options['xdmod_session'] || $session->has($this->options['xdmod_session']));
        $hasDashboardSession = (!$this->options['dashboard_session'] || $session->has($this->options['dashboard_session']));

        $requestPath = $this->httpUtils->checkRequestPath($request, $this->options['check_path']);

        $this->logger->debug(
            'Checking if FormLoginAuthenticator supports request',
            [
                'xdmod_session' => $hasXdmodSession,
                'dashboard_session' => $hasDashboardSession,
                'request_path' => $requestPath
            ]
        );

        return $requestPath && ( $hasXdmodSession || $hasDashboardSession);
    }

    public function authenticate(Request $request)
    {
        $this->logger->debug('Initiating Session Authenticator');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }


}
