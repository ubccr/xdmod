<?php

declare(strict_types=1);

namespace Access\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


/**
 *
 */
class AuthenticationController extends AbstractController
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/login", methods={"POST"}, name="xdmod_login")
     * @param Request $request
     * @return Response
     */
    public function formLogin(Request $request): Response
    {
        // If we've gotten this far than this should give us a user.
        $user = $this->getUser();

        if (null === $user) {
            return $this->json([
                'success' => false,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // If for some reason we didn't get an \XDUser then fail fast.
        // ( Honestly this is really here to make sure auto-complete works for $user )
        if (!($user instanceof \XDUser)) {
            return $this->json([
                'success' => false,
                'message' => 'User type mismatch'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $user->postLogin();
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error occurred during post login process.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $firewallContextName = $request->attributes->get('_firewall_context');
        $tokenStorage = $this->container->get('security.token_storage');
        /*$userToken = new UsernamePasswordToken($user, $firewallContextName);

        $this->logger->debug('Setting user token', [$firewallContextName, $userToken]);
        $tokenStorage->setToken($userToken);*/

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

        return $response;
    }

    /**
     * @Route("/logout", methods={"POST"}, name="xdmod_logout")
     * @return Response
     */
    public function formLogout(): Response
    {
        return $this->json(['success' => true]);
    }

    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
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

        // This accounts for the setting of
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
     * @Route("/api/logout", name="api_logout", methods={"POST"})
     * @return Response
     * @throws Exception since this should never be called.
     */
    public function logout(): Response
    {
        session_destroy();
        throw new Exception("Don't forget to activate logout.");
    }
}

