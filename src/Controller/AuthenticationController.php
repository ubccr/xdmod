<?php

declare(strict_types=1);

namespace CCR\Controller;

use CCR\DB;
use Exception;
use Models\Services\JsonWebToken;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Source;
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
use xd_response\buildError;


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
     * This route is here just to provide a route, no actual logging in is done here as evidenced by the only code
     * present is the throwing of an Exception. The actual "login" process is handled by the Symfony Authentication
     * process in conjunction with our custom Authenticators that are responsible for pulling / providing creds from a
     * Request:
     * - `src/Security/Authenticators/FormLoginAuthenticator`
     * - `src/Security/Authenticators/SimpleSamlPhpAuthenticator`
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
    #[Route('{prefix}login', name: 'xdmod_login', requirements: ['prefix' => '.*'], methods: ['POST'])]
    #[Route('/login', name: 'xdmod_new_login', methods: ['POST'])]
    public function login(): NotFoundHttpException
    {
        throw new NotFoundHttpException();
    }

    /**
     * Return an IDP redirect URL for SSO login
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('{prefix}auth/idpredirect', name: 'idp_redirect', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function idpRedirect(Request $request): Response
    {
        $returnTo = $this->getStringParam($request, 'returnTo', true);

        $request->getSession()->set('_security.main.target_path', $returnTo);

        $ssoAuthSources = Source::getSources();
        $ssoAuthSource = !empty($ssoAuthSources) ? $ssoAuthSources[0] : false;
        $auth = ($ssoAuthSource) ? new \SimpleSAML\Auth\Simple($ssoAuthSource) : false;

        $redirectURL = false;
        if ($auth) {
            $redirectURL = $auth->getLoginURL($returnTo);
        } else {
            return $this->json(buildError(new \Exception('SSO not configured.')));
        }

        return new Response($redirectURL, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
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
    #[Route('{prefix}jwt-redirect', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function redirectWithJwt(Request $request): Response
    {
        try {
            $jupyterhub_url = $this->parameters->get('xdmod.portal_settings.jupyterhub.url');
        } catch (Exception $e) {
            throw new HttpException(501, 'JupyterHub not configured.');
        }
        try {
            $user = $this->getXDUser();
        } catch (UnauthorizedHttpException $e) {
            return new RedirectResponse('/#jwt-redirect');
        }
        list($jwt, $expiration) = JsonWebToken::encode($user->getUsername());
        $cookie = new Cookie(
            'xdmod_jwt',
            $jwt,
            $expiration,
            sameSite: 'strict',
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true
        );
        $response = new RedirectResponse($jupyterhub_url);
        $response->headers->setCookie($cookie);
        return $response;
    }
}
