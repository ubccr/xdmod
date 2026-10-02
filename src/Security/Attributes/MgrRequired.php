<?php

namespace CCR\Security\Attributes;

use CCR\Security\Listeners\RoleRequiredAttributeListener;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * This attribute, when used to decorate a Symfony Controller class or Route function, restricts which users are
 * authorized to request the Controller or Route to those with the Manager Acl ( those who have access to the Internal
 * Dashboard. ).
 *
 * By pairing this Attribute with an accompanying AttributeListener ( @see RoleRequiredAttributeListener ) class attached
 * to the Kernel Controller Event, we are able to provide the "Restrict this route to users w/ the specified ACL".
 *
 * Note: the implementation of this Attribute and the RoleRequiredAttributeListener was directly copied from the
 * Symfony `IsGranted` Attribute and the associated `IsGrantedAttributeListener`.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class MgrRequired extends RoleRequired
{
    public function __construct(Expression|string $attribute = '', array|Expression|string|null $subject = null, ?string $message = null, ?int $statusCode = null, ?int $exceptionCode = null)
    {
        parent::__construct(new Expression('"mgr" in role_names'), $subject, $message, $statusCode, $exceptionCode);
    }
}
