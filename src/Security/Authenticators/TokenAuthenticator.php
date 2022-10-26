<?php
declare(strict_types=1);

namespace Access\Security\Authenticators;



use Access\Security\TokenUserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

/**
 *
 */
class TokenAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var string[]
     */
    private $options;

    public function __construct(LoggerInterface $logger, HttpUtils $httpUtils, array $options)
    {
        $this->logger = $logger;
        $this->httpUtils = $httpUtils;
        $this->options = array_merge([
            'token_parameter' => 'xdmod_token',
            'check_path' => 'login'
        ], $options);
    }


    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): bool
    {
        /*$supports = $request->cookies->has($this->options['token_parameter']);
        $this->logger->warning('Checking that TokenAuthenticator supports a request', [$supports]);*/
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request): PassportInterface
    {
        $this->logger->warning('Initiating token authentication');
        $token = $request->cookies->get($this->options['token_parameter']);
        return new SelfValidatingPassport(new UserBadge($token), [new RememberMeBadge()]);
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
