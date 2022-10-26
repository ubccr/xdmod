<?php
declare(strict_types=1);

namespace Access\Security\Authenticators;

use Access\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use XDUser;

class FormLoginAuthenticator extends AbstractLoginFormAuthenticator implements AuthenticatorInterface
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
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var bool[]|string[]
     */
    private $options;

    public function __construct(LoggerInterface $logger, HttpUtils $httpUtils, UrlGeneratorInterface $urlGenerator, array $options)
    {
        $this->logger = $logger;
        $this->httpUtils = $httpUtils;
        $this->urlGenerator = $urlGenerator;
        $this->options = array_merge([
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'check_path' => '/login',
            'post_only' => true,
            'form_only' => true,
        ], $options);
    }


    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        $postOnly = (!$this->options['post_only'] || $request->isMethod('POST'));
        $requestPath = $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
        $formOnly = (!$this->options['form_only'] || 'form' === $request->getContentType());

        $this->logger->debug('Checking if FormLoginAuthenticator supports request', [$postOnly, $requestPath, $formOnly]);

        return $postOnly && $requestPath && $formOnly;
    }

    public function authenticate(Request $request)
    {
        $this->logger->debug('Initiating Form Login Authentication', [$request]);

        $credentials = $this->getCredentials($request);
        $this->logger->debug('Attempting to login user' . $credentials['username'], $credentials);

        $passport = new Passport(
            new UserBadge($credentials['username']),
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge()]
        );

        return $passport;
    }

    private function getCredentials(Request $request): array
    {
        $credentials = [];

        if ($this->options['post_only']) {
            $credentials['username'] = ParameterBagUtils::getParameterBagValue($request->request, $this->options['username_parameter']);
            $credentials['password'] = ParameterBagUtils::getParameterBagValue($request->request, $this->options['password_parameter']) ?? '';
        } else {
            $credentials['username'] = ParameterBagUtils::getRequestParameterValue($request, $this->options['username_parameter']);
            $credentials['password'] = ParameterBagUtils::getRequestParameterValue($request, $this->options['password_parameter']) ?? '';
        }

        if (!\is_string($credentials['username']) && (!\is_object($credentials['username']) || !method_exists($credentials['username'], '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        if (\strlen($credentials['username']) > Security::MAX_USERNAME_LENGTH) {
            $this->logger->debug('Username is to long', $credentials);
            throw new BadCredentialsException('Invalid username.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $user = $token->getUser();
        $xdUser = XDUser::getUserByUserName($user->getUserIdentifier());

        return new JsonResponse([
            'success' => true,
            'results' => [
                'token'  => $xdUser->getToken(),
                'name' => $xdUser->getFormalName()
            ]
        ]);
    }

    protected function getLoginUrl(Request $request): string
    {
        return '/login';
    }
}
