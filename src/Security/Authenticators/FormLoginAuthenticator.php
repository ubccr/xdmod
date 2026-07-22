<?php
declare(strict_types=1);

namespace CCR\Security\Authenticators;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use XDUser;

class FormLoginAuthenticator extends AbstractLoginFormAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
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

    /**
     * @param LoggerInterface $logger
     * @param HttpUtils $httpUtils
     * @param UrlGeneratorInterface $urlGenerator
     * @param array $options
     */
    public function __construct(LoggerInterface $logger, HttpUtils $httpUtils, UrlGeneratorInterface $urlGenerator, array $options)
    {
        $this->logger = $logger;
        $this->httpUtils = $httpUtils;
        $this->urlGenerator = $urlGenerator;
        $this->options = array_merge([
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'check_paths' => ['xdmod_login', 'xdmod_new_login'],
            'failure_path' => 'xdmod_home',
            'post_only' => true,
            'form_only' => true,
        ], $options);
    }


    /**
     * This method is overwritten because we specifically only want this Authenticator to apply when the request is a
     * POST with a content-type of application/x-www-form-urlencoded w/ a path that matches our `check_path`.
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        $postOnly = (!$this->options['post_only'] || $request->isMethod('POST'));
        $formOnly = (!$this->options['form_only'] || 'form' === $request->getContentTypeFormat());
        if ($request->attributes->has('_route')) {
            $requestPath = $request->attributes->get('_route');
        } else {
            $requestPath = $request->getPathInfo();
        }
        $this->logger->debug('Checking Path', [$requestPath]);

        $found = false;
        foreach ($this->options['check_paths'] as $checkPath) {
            $requestPathMatches = $this->httpUtils->checkRequestPath($request, $checkPath);
            if ($requestPathMatches) {
                $found = true;
                break;
            }
        }

        $this->logger->debug('Checking if FormLoginAuthenticator supports request', [$postOnly, $found, $formOnly]);

        return $postOnly && $found && $formOnly;
    }

    /**
     * Create a passport for the current request.
     *
     * The passport contains the user, credentials and any additional information
     * that has to be checked by the Symfony Security system. For example, a login
     * form authenticator will probably return a passport containing the user, the
     * presented password and the CSRF token value.
     *
     * You may throw any AuthenticationException in this method in case of error (e.g.
     * a UserNotFoundException when the user cannot be found).
     *
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        $this->logger->debug('Initiating Form Login Authentication', [$request]);

        $credentials = $this->getCredentials($request);
        $this->logger->debug('Attempting to login user ' . $credentials['username'], $credentials);

        return new Passport(
            new UserBadge($credentials['username']),
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge()]
        );
    }

    /**
     * Retrieve user credentials from the provided Request. Validates that the username length is less than or equal to
     * Security::MAX_USERNAME_LENGTH and if not it throws a BadCredentialsException. If credentials are able to be
     * successfully retrieved and they are valid than the Security::LAST_USERNAME session variable is set to the
     * retrieved username.
     *
     * @param Request $request
     * @return array containing the username / password retrieved from the provided Request.
     * @throws BadRequestHttpException if the username parameter is not a string, or if it's an object that does not provide a __toString method.
     * @throws BadCredentialsException if the provided username is longer than Security::MAX_USERNAME_LENGTH.
     */
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
            $this->logger->error('Username is to long', $credentials);
            throw new BadCredentialsException('Invalid username.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }

    /**
     * We do the translation from Symfony User to XDUser here by looking for an XDUser that has the same username as
     * the authenticated Symfony User. When found, we set the `xdUser` session variable equal to the XDUser's user id.
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response
     * @throws \Exception if unable to find an XDUser that matches the provided Symfony User
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $user = $token->getUser();
        $xdUser = XDUser::getUserByUserName($user->getUserIdentifier());
        $xdUser->postLogin();
        $request->getSession()->set('xdUser', $xdUser->getUserID());
        $response = new JsonResponse([
            'success' => true,
            'results' => [
                'token' => $xdUser->getToken(),
                'name' => $xdUser->getFormalName()
            ]
        ]);
        $response->headers->setCookie(new Cookie('xdmod_token', $xdUser->getToken()));
        return $response;
    }

    /**
     * Return the URL to the login page.
     * @param Request $request
     * @return string the login url that this FormLoginAuthenticator supports.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->httpUtils->generateUri($request, $this->options['check_path']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }
        return new JsonResponse([], 401);
    }

    /**
     * This is required for the Authenticator to be set as an entrypoint. We need to set an entrypoint because we have
     * multiple authenticators setup for our main firewall ( FormLoginAuthenticator, TokenAuthenticator, SSOAuthenticator )
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('xdmod_home'));
    }
}
