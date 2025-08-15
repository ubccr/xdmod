<?php

declare(strict_types=1);

namespace Access\Controller;

use Access\Security\Helpers\Tokens;
use Drenso\OidcBundle\Exception\OidcCodeChallengeMethodNotSupportedException;
use Drenso\OidcBundle\Exception\OidcConfigurationException;
use Drenso\OidcBundle\Exception\OidcConfigurationResolveException;
use Drenso\OidcBundle\OidcClientInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use xd_security\SessionSingleton;


/**
 * This controller handles the authentication routes for XDMoD. Please note that it works in conjunction with Symfony's
 * security framework, our customizations of which reside in `src/Security`. These customizations consist of
 * UserProviders and Authenticators. Authenticators do what they say on the tin and are responsible for the actual
 * authentication of users. UserProviders are responsible for identifying which user is logged in after they have been
 * logged in.
 */
class AuthenticationController extends BaseController
{

    /**
     * @var ContainerBagInterface
     */
    private $parameters;

    private $ssoUrl;

    /**
     * @param LoggerInterface $logger
     * @param ContainerBagInterfaces $parameters
     * @param Environment $twig
     * @param Tokens $tokenHelper
     */
    public function __construct(LoggerInterface $logger, ContainerBagInterface $parameters, Environment $twig, Tokens $tokenHelper)
    {
        $this->logger = $logger;
        $this->parameters = $parameters;
        $this->ssoUrl = $this->parameters->get('sso')['login_link'];
        parent::__construct($logger, $twig, $tokenHelper);
    }

    /**
     * This route is here so that we make sure the XDUser::postLogin function is called and that the users token is set
     * in the appropriate location for use throughout the users session. The actual "login" process is handled by
     * `src/Authenticators/FormLoginAuthenticator` with configuration located in `config/packages/security.yaml`.
     * @return Response
     */
    #[Route('{prefix}/login', name: 'xdmod_login', requirements: ['prefix' => '.*'], methods: ['POST'])]
    #[Route('/login', name: 'xdmod_new_login', methods: ['POST'])]
    public function formLogin(): Response
    {
        $user = $this->getUser();

        if (null === $user) {
            $this->logger->error('No user found during login.');
            return $this->json([
                'success' => false,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // If for some reason we didn't get an \XDUser then fail fast.
        // ( Honestly this is really here to make sure auto-complete works for $user )
        if (!($user instanceof \XDUser)) {
            $this->logger->error('User instance type mismatched.');
            return $this->json([
                'success' => false,
                'message' => 'User type mismatch'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $user->postLogin();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'An error has occurred during the post-login process for %s',
                    $user->getUsername()
                )
            );
            return $this->json([
                'success' => false,
                'message' => 'Error occurred during post login process.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = $user->getToken();
        $response = $this->json([
            'success' => true,
            'results' => [
                'token' => $token,
                'name' => $user->getFormalName()
            ]
        ]);

        // This cookie will tell the HomeController that we have a currently logged in user.
        $response->headers->setCookie(new Cookie('xdmod_token', $token));

        $this->logger->info(sprintf('Successful login by %s', $user->getUsername()));

        return $response;
    }

    /**
     * This route is responsible for any logic that may need to be executed when a user is logged out. Currently, the
     * actual heavy lifting of logging out is done by the configuration in `config/packages/security.yaml`.
     *
     *
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/rest/logout', name: 'xdmod_logout', methods: ['POST', 'GET'])]
    #[Route('/logout', name: 'xdmod_new_logout', methods: ['POST'])]
    #[Route('/rest/auth/logout', name: 'xdmod_rest_auth_logout', methods: ['POST'])]
    public function formLogout(Request $request): Response
    {
        $this->logger->error('*** FormLogout ***');
        $token = $request->getSession()->get('xdmod_token');
        \XDSessionManager::logoutUser($token);
        $request->getSession()->invalidate();

        $response = $this->redirectToRoute('xdmod_home');
        $response->headers->removeCookie('xdmod_token');
        return $response;
    }

    /**
     * This route is responsible for logging API users in. The configuration for this route is located in
     * `config/packages/security.yaml`.
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/api/login', name: 'api_login', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function login(Request $request): Response
    {
        $user = $this->getUser();

        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $xdUser = \XDUser::getUserByUserName($user->getUserIdentifier());

        $xdUser->postLogin();

        $request->getSession()->set('xdUser', $xdUser->getUserID());


        $response = $this->json([
            'user' => $user->getUserIdentifier(),
            'token' => $xdUser->getToken()
        ]);


        // Make sure that we remove any xdmod_token cookie that already exists so that it can be set with the correct
        // token.
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'xdmod_token') {
                $response->headers->removeCookie('xdmod_token');
            }
        }

        $response->headers->setCookie(Cookie::create('xdmod_token', $xdUser->getToken(), 0, '/', '', true));

        return $response;
    }

    /**
     * This Route is responsible for logging API Users out.
     *
     * @return Response
     *
     * @throws Exception since this should never be called.
     */
    #[Route('{prefix}/api/logout', name: 'api_logout', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function logout(): Response
    {
        session_destroy();
        throw new Exception("Don't forget to activate logout.");
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('{prefix}/auth/idpredirect', name: 'idp_redirect', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function idpRedirect(Request $request): Response
    {
        $returnTo = $this->getStringParam($request, 'returnTo');

        if (!empty($returnTo)) {
            $this->logger->warning(sprintf('SSO Url: %s', var_export($this->ssoUrl, true)));
            $this->logger->warning(sprintf('Return To: %s', var_export($returnTo, true)));
            /*$value = "$ssoUrl?returnTo=$returnTo";*/
        }
        return new Response($this->ssoUrl, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}

