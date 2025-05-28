<?php

namespace Rest\Controllers;

use CCR\DB;
use Configuration\Configuration;
use Firebase\JWT\JWT;
use Models\Services\Organizations;
use PhpOffice\PhpWord\Exception\Exception;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use XDUser;

/**
 * Class UserControllerProvider
 *
 * This class is responsible for maintaining routes for the REST stack that
 * handle user-related functionality.
 */
class JupyterHubControllerProvider extends BaseControllerProvider
{

    /**
     * @see BaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;

        $controller->get("$root/jupyterhub/authorize", '\Rest\Controllers\JupyterHubControllerProvider::authorizeUser');
    }

    /**
     * Get details for the current user.
     *
     * @param Request $request The request used to make this call.
     * @param Application $app The router application.
     * @return array                Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the current user.
     */
    public function authorizeUser(Request $request, Application $app)
    {
        // Ensure that the user is logged in.
        $this->authorize($request);

        $jupyterhub_url = \xd_utilities\getConfiguration('jupyterhub', 'url');
        return new RedirectResponse("$root/users/current/api/jsonwebtoken")

        return new RedirectResponse($jupyterhub_url)
        // Extract and return the information for the user.
        return $app->json(array(
            'success' => true,
            'results' => $this->extractUserData($this->getUserFromRequest($request)),
        ));
    }

    /**
     *
     * @param Request $request
     * @param Application $app
     * @return Response
     * @throws \Exception if there is a problem retrieving a database connection.
     */
    public function createJSONWebToken(Request $request, Application $app)
    {
        try {
            $user = $this->authorize($request);
        } catch (UnauthorizedHttpException | AccessDeniedException $e) {
            return new RedirectResponse("/");
        }

        $secretKey  = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $tokenId    = base64_encode(random_bytes(16));
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+6 minutes')->getTimestamp();

        $data = [
            'iat'  => $issuedAt->getTimestamp(),
            'jti'  => $tokenId,
            'exp'  => $expire,
            'upn'  => $user->getUserName()
        ];

        $jwt = JWT::encode(
            $data,
            $secretKey,
            'HS256'
        );

        return $app->json(array(
            'success' => true,
            'data' => $jwt
        ));
    }
}
