<?php

declare(strict_types=1);

namespace CCR\Controller;

use CCR\Security\Helpers\Tokens;
use Exception;
use Models\Services\JsonWebToken;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;


/**
 * This controller handles the authentication routes for XDMoD. Please note that it works in conjunction with Symfony's
 * security framework, our customizations of which reside in `src/Security`. These customizations consist of
 * UserProviders and Authenticators. Authenticators are responsible for retrieving the credentials ( username / pasword,
 * tokens, etc. ) from the request. UserProviders are responsible for identifying which user is logged in after they have been
 * logged in. Symfony then compares these credentials against those retrieved by the UserProvider and if verified the
 * user is logged in.
 */
class AuthenticationController extends BaseController
{

    /**
     * If SSO is setup, this is the url that will be used to log into the configured Identity Provider.
     */
    private string $ssoUrl;

    /**
     * @param LoggerInterface $logger
     * @param ContainerBagInterface $parameters
     * @param Environment $twig
     * @param Tokens $tokenHelper
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface this is thrown if the `sso` section of `services.yaml` is not present.
     */
    public function __construct(LoggerInterface $logger, ContainerBagInterface $parameters, Environment $twig, Tokens $tokenHelper)
    {
        $this->logger = $logger;
        $this->ssoUrl = $parameters->get('sso')['login_link'];
        parent::__construct($logger, $twig, $tokenHelper);
    }

    /**
     * This route is here just to provide a route, no actual logging in is done here as evidenced by the only code
     * present is the throwing of an Exception. The actual "login" process is handled by the Symfony Authentication
     * process in conjunction with our custom Authenticators that are responsible for pulling / providing creds from a
     * Request:
     * - `src/Authenticators/FormLoginAuthenticator`
     * - `src/Authenticators/SimpleSamlPhpAuthenticator`
     *
     * and our UserProviders that know how to lookup a user in our database:
     * - `src/Security/UsernameUserProvider.php`
     *
     * This information is then combined and Symfony handles the validation of username / password hash etc.
     *
     * The configuration can be found in `config/packages/security.yaml`.
     *
     * @return NotFoundHttpException
     */
    #[Route('{prefix}/login', name: 'xdmod_login', requirements: ['prefix' => '.*'], methods: ['POST'])]
    #[Route('/login', name: 'xdmod_new_login', methods: ['POST'])]
    public function login(): NotFoundHttpException
    {
        throw new NotFoundHttpException();
    }

    /**
     * This route is responsible for any logic that may need to be executed when a user is logged out. Currently, the
     * actual heavy lifting of logging out is done by the configuration in `config/packages/security.yaml`. This function
     * just ensures that we clean up our custom Session.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/rest/logout', name: 'xdmod_logout', methods: ['POST', 'GET'])]
    #[Route('/logout', name: 'xdmod_new_logout', methods: ['POST'])]
    #[Route('/rest/auth/logout', name: 'xdmod_rest_auth_logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        $token = $request->getSession()->get('xdmod_token');
        \XDSessionManager::logoutUser($token);
        $request->getSession()->invalidate();

        $response = $this->redirectToRoute('xdmod_home');
        $response->headers->removeCookie('xdmod_token');
        return $response;
    }

    /**
     * Return an IDP redirect URL for SSO login
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('{prefix}/auth/idpredirect', name: 'idp_redirect', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function idpRedirect(Request $request): Response
    {
        $returnTo = $this->getStringParam($request, 'returnTo');
        $value = $this->ssoUrl;
        if (!empty($returnTo)) {
            $ssoUrl = $this->ssoUrl;
            $returnTo = urlencode($returnTo);
            $value = "{$ssoUrl}?ReturnTo=$returnTo";
            $request->getSession()->set('_security.main.target_path', $returnTo);
        }
        return new Response($value, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }


    /**
     * If a JupyterHub is configured, redirect to it with a new JSON Web Token in a cookie.
     *
     * @param Request $request
     * @return RedirectResponse to the configured JupyterHub root if the user is
     *                           authenticated, otherwise to the sign-in
     *                           screen.
     * @throws Exception if a JupyterHub is not configured.
     */
    #[Route('/jwt-redirect', methods: ['GET'])]
    public function redirectWithJwt(Request $request): Response
    {
        try {
            $jupyterhub_url = \xd_utilities\getConfiguration('jupyterhub', 'url');
        } catch (Exception $e) {
            throw new HttpException(501, 'JupyterHub not configured.');
        }
        try {
            $user = $this->authorize($request);
        } catch (UnauthorizedHttpException $e) {
            return new RedirectResponse('/#jwt-redirect');
        }
        list($jwt, $expiration) = JsonWebToken::encode($user->getUsername());
        $cookie = new Cookie(
            'xdmod_jwt',
            $jwt,
            $expiration,
            '/',  // path
            null, // domain
            true, // secure
            true  // httpOnly
        );
        $response = new RedirectResponse($jupyterhub_url);
        $response->headers->setCookie($cookie);
        return $response;
    }
}

