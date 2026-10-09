<?php

namespace CCR\Security\TokenExtractors;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;
/**
 */
class QueryTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct(
        private readonly string $parameter = 'Bearer',
    ) {
    }

    public function extractAccessToken(Request $request): ?string
    {
        $queryParameter = $request->query->get($this->parameter, '');
        return $request->query->has($this->parameter) ? $queryParameter : null;
    }
}
