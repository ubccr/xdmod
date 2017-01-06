<?php

namespace NewRest\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use NewRest\Utilities\Authentication;

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
     * @see aBaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;
        $controller->post("$root/login", '\NewRest\Controllers\AuthenticationControllerProvider::login');
        $controller->post("$root/logout", '\NewRest\Controllers\AuthenticationControllerProvider::logout');
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
     * @throws Exception if the user could not be found or if their account
     *                   is disabled.
     */
    public function login(Request $request, Application $app)
    {
        $this->authenticate($request, $app);
        $user = $request->get(BaseControllerProvider::_USER);

        $token = \XDSessionManager::recordLogin($user);

        return $app->json(array(
            'success' => true,
            'results' => array('token' => $token, 'name' => $user->getFormalName())
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
}
