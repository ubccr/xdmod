<?php

namespace CCR\Security\TokenExtractors;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class QueryStringTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct() {
    }

    public function extractAccessToken(Request $request): ?string
    {
        return $request->query->get('Bearer', null);
    }
}
