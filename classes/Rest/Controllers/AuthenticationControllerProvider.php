<?php

namespace Rest\Controllers;

use CCR\MailWrapper;
use Models\Services\Acls;
use Models\Services\JsonWebToken;
use Models\Services\Organizations;
use Rest\Utilities\Authentication;
use Silex\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use XDUser;

/**
 * Class AuthenticationControllerProvider
 *
 * This class is responsible for maintaining the authentication routes for the
 * REST stack.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class AuthenticationControllerProvider extends BaseControllerProvider
{

    /**
     * AuthenticationControllerProvider constructor.
     *
     * @param array $params
     *
     * @throws \Exception if there is a problem retrieving email addresses from configuration files.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }


    /**
     * @see aBaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;
        $controller->post("$root/login", '\Rest\Controllers\AuthenticationControllerProvider::login');
        $controller->post("$root/logout", '\Rest\Controllers\AuthenticationControllerProvider::logout');
        $controller->get("$root/idpredirect", '\Rest\Controllers\AuthenticationControllerProvider::getIdpRedirect');
        $controller->get("$root/jwt-redirect", '\Rest\Controllers\AuthenticationControllerProvider::redirectWithJwt');
    }

    /**
     * Provide the user with an authentication token.
     *
     * The authentication check has already occurred in middleware when this
     * function is called, so it does not perform any authentication work.
     *
     * @param Request $request that will be used to retrieve the user
     * @param Application $app used to facilitate json encoding the response.
     * @return \Symfony\Component\HttpFoundation\JsonResponse which contains a
     *                         token and the users full name if the login
     *                         attempt is successful.
     * @throws \Exception if the user could not be found or if their account
     *                   is disabled.
     */
    public function login(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        $user->postLogin();

        return $app->json(array(
            'success' => true,
            'results' => array('token' => $user->getSessionToken(), 'name' => $user->getFormalName())
        ));
    }

    /**
     * Attempt to log out the user identified by the provided token.
     *
     * @param Request $request that will be used to retrieve the token.
     * @param Application $app that will be used to facilitate the json
     *                         encoding of the response.
     * @return \Symfony\Component\HttpFoundation\JsonResponse indicating
     *                         that the user has been successfully logged
     *                         out.
     */
    public function logout(Request $request, Application $app)
    {
        $authInfo = Authentication::getAuthenticationInfo($request);
        \XDSessionManager::logoutUser($authInfo['token']);

        return $app->json(array(
            'success' => true,
            'message' => 'User logged out successfully'
        ));
    }

    /**
     * Return an IDP redirect URL for SSO login
     */
    public function getIdpRedirect(Request $request, Application $app)
    {
        $auth = new \Authentication\SAML\XDSamlAuthentication();

        $redirectUrl = $auth->getLoginURL($this->getStringParam($request, 'returnTo', true));

        if ($redirectUrl === false ) {
            throw new \Exception('SSO not configured.');
        }

        return $app->json($redirectUrl);
    }

    /**
     * Redirect to a specified path with a new JSON Web Token in a cookie.
     *
     * @param Request $request must contain a 'next' parameter whose value is
     *                         the path to which to redirect.
     * @param Application $app
     * @return RedirectResponse to the 'next' path if the user is
     *                          authenticated, otherwise to the sign-in
     *                          screen.
     * @throws BadRequestHttpException if the 'next' parameter is not present
     *                                 or does not start with '/'.
     */
    public function redirectWithJwt(Request $request, Application $app)
    {
        $next = $this->getStringParam($request, 'next', true);
        if (0 !== strpos($next, '/')) {
            throw new BadRequestHttpException("Invalid 'next' parameter.");
        }

        try {
            $user = $this->authorize($request);
        } catch (UnauthorizedHttpException $e) {
            return new RedirectResponse('/#jwt-redirect?next=' . $next);
        }

        $username = $user->getUsername();
        $usernameClaim = [JsonWebToken::claimKeySubject => $username];
        $jwt = new JsonWebToken();
        $jwt->addClaims($usernameClaim);

        $cookie = new Cookie(
            'xdmod_jwt',
            $jwt->encode(),
            $jwt->getClaim(JsonWebToken::claimKeyExpiration), // expire
            '/',  // path
            null, // domain
            true, // secure
            true  // httpOnly
        );
        $response = $app->redirect($next);
        $response->headers->setCookie($cookie);
        return $response;
    }
}
