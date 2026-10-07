<?php

namespace CCR\Security\EntryPoints;

use CCR\Security\TokenHandlers\TokenHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class TokenAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function start(Request $request, ?AuthenticationException $authException = null): UnauthorizedHttpException
    {
        return TokenHandler::throwUnauthorized(TokenHandler::MISSING_TOKEN_MESSAGE);
    }
}
