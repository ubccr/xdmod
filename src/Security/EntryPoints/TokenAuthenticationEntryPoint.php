<?php

namespace CCR\Security\EntryPoints;

use CCR\Security\TokenHandlers\TokenHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class TokenAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private LoggerInterface $logger) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response(TokenHandler::MISSING_TOKEN_MESSAGE, Response::HTTP_UNAUTHORIZED);
    }
}
