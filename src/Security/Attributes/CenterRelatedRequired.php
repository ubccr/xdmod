<?php

namespace CCR\Security\Attributes;

use Symfony\Component\ExpressionLanguage\Expression;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class CenterRelatedRequired extends RoleRequired
{
    public function __construct(Expression|string $attribute = '', array|Expression|string|null $subject = null, ?string $message = null, ?int $statusCode = null, ?int $exceptionCode = null)
    {
        parent::__construct(new Expression('"cs" in role_names or "cd" in role_names'), $subject, $message, $statusCode, $exceptionCode);
    }
}
