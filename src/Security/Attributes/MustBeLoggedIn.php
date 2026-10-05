<?php declare(strict_types=1);
namespace CCR\Security\Attributes;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class MustBeLoggedIn extends \CCR\Security\Attributes\RoleRequired
{
    public function __construct(Expression|string $attribute = '', array|Expression|string|null $subject = null, ?string $message = null, ?int $statusCode = null, ?int $exceptionCode = null)
    {
        parent::__construct(
            new Expression('is_authenticated() and "pub" not in role_names'),
            $subject,
            $message,
            $statusCode,
            Response::HTTP_UNAUTHORIZED
        );
    }
}
