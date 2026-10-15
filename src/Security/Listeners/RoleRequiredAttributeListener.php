<?php declare(strict_types=1);

namespace CCR\Security\Listeners;

use CCR\Security\Attributes\RoleRequired;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * This class' purpose is to listen for the KernelControllerArguments event and if the event has an attribute that
 * extends `RoleRequired` Attribute, proceed to check the specified subject ( the user requesting the controller )
 * conforms to the requirement provided by `RoleRequired` attribute. If the user does, then they are authorized and
 * processing continues, if they do not, then an `AccessDeniedHttpException` is thrown.
 */
class RoleRequiredAttributeListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authChecker,
        private ?ExpressionLanguage                    $expressionLanguage = null,
    ) {
    }

    /**
     * @param ControllerArgumentsEvent $event
     * @return void
     */
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        // Filter the event's ( routes ) attributes so that we only end up with attributes that extend
        // RoleRequired.
        $attributes = array_filter(
            $event->getAttributes(),
            function ($key) {
                return is_subclass_of($key, RoleRequired::class);
            },
            ARRAY_FILTER_USE_KEY
        );

        // If we don't have any RoleRequired attributes we don't care.
        if (empty($attributes)) {
            return;
        }

        $request = $event->getRequest();
        $arguments = $event->getNamedArguments();

        /** @var RoleRequired[] $attributes */
        $attributes = array_shift($attributes);
        foreach ($attributes as $attribute) {
            $subject = null;
            if ($subjectRef = $attribute->subject) {
                if (\is_array($subjectRef)) {
                    foreach ($subjectRef as $refKey => $ref) {
                        $subject[\is_string($refKey) ? $refKey : (string)$ref] = $this->getIsGrantedSubject($ref, $request, $arguments);
                    }
                } else {
                    $subject = $this->getIsGrantedSubject($subjectRef, $request, $arguments);
                }
            }

            $isGranted = $this->authChecker->isGranted($attribute->attribute, $subject);
            if (!$isGranted) {
                $message = $attribute->message ?: \sprintf('Access Denied by #[RoleRequired(%s)] on controller', $this->getIsGrantedString($attribute));

                if ($statusCode = $attribute->statusCode) {
                    throw new HttpException($statusCode, $message, code: $attribute->exceptionCode ?? 0);
                }
                throw new AccessDeniedHttpException($message, code: $attribute->exceptionCode ?? 403);
            }
        }
    }

    /**
     * Returns the events that this listener subscribes to.
     *
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 30]];
    }

    /**
     * @param string|Expression $subjectRef
     * @param Request $request
     * @param array $arguments
     * @return mixed
     */
    private function getIsGrantedSubject(string|Expression $subjectRef, Request $request, array $arguments): mixed
    {
        if ($subjectRef instanceof Expression) {
            $this->expressionLanguage ??= new ExpressionLanguage();

            return $this->expressionLanguage->evaluate($subjectRef, [
                'request' => $request,
                'args' => $arguments,
            ]);
        }

        if (!\array_key_exists($subjectRef, $arguments)) {
            throw new RuntimeException(\sprintf('Could not find the subject "%s" for the #[RoleRequired] attribute. Try adding a "$%s" argument to your controller method.', $subjectRef, $subjectRef));
        }
        return $arguments[$subjectRef];
    }

    /**
     * Retrieve a string representation of the RoleRequired object in the form "<attribute><subject>"
     *
     * @param RoleRequired $isGranted
     * @return string
     */
    private function getIsGrantedString(RoleRequired $isGranted): string
    {
        $processValue = fn($value) => \sprintf($value instanceof Expression ? 'new Expression("%s")' : '"%s"', $value);

        $argsString = $processValue($isGranted->attribute);

        if (null !== $subject = $isGranted->subject) {
            $subject = !\is_array($subject) ? $processValue($subject) : array_map(function ($key, $value) use ($processValue) {
                $value = $processValue($value);

                return \is_string($key) ? \sprintf('"%s" => %s', $key, $value) : $value;
            }, array_keys($subject), $subject);

            $argsString .= ', ' . (!\is_array($subject) ? $subject : '[' . implode(', ', $subject) . ']');
        }

        return $argsString;
    }
}
