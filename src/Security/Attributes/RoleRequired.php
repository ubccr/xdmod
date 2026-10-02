<?php

namespace CCR\Security\Attributes;

use \Attribute;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Abstract base class for all "Role" related authorization Attributes.
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
abstract class RoleRequired
{
    public function __construct(
        /**
         * Sets the first argument that will be passed to isGranted().
         */
        public string|Expression            $attribute = 'invalid_replace_in_child_class',
        /**
         * Sets the second argument passed to isGranted().
         *
         * @var array<string|Expression>|string|Expression|null
         */
        public array|string|Expression|null $subject = null,
        /**
         * The message of the exception - has a nice default if not set.
         */
        public ?string                      $message = null,
        /**
         * If set, will throw HttpKernel's HttpException with the given $statusCode.
         * If null, Security\Core's AccessDeniedException will be used.
         */
        public ?int                         $statusCode = null,
        /**
         * If set, will add the exception code to thrown exception.
         */
        public ?int                         $exceptionCode = null,
    ) {
    }
}
