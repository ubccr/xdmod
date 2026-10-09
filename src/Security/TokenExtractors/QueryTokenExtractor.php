<?php

namespace CCR\Security\TokenExtractors;

use CCR\Security\TokenHandlers\TokenHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class QueryTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct(
        private readonly string $parameter = 'Bearer',
    ) {
    }

    public function extractAccessToken(Request $request): ?string
    {
        $queryParameter = $request->query->get($this->parameter);
        if ('' === $queryParameter) {
            TokenHandler::throwUnauthorized(TokenHandler::MISSING_TOKEN_MESSAGE)
        }
        return $queryParameter;
    }
}
