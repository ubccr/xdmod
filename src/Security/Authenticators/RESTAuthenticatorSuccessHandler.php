<?php

namespace CCR\Security\Authenticators;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

use XDUser;

class RESTAuthenticatorSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        $xdUser = XDUser::getUserByUserName($user->getUserIdentifier());
        $response = new JsonResponse([
            'success' => true,
            'results' => [
                'token' => $xdUser->getToken(),
                'name' => $xdUser->getFormalName()
            ]
        ]);

        return $response;
    }
}

