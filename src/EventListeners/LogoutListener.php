<?php

declare(strict_types=1);

namespace CCR\EventListeners;

use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Auth\Source;
use SimpleSAML\Session;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {}

    public function __invoke(LogoutEvent $event): void
    {
        $sources = Source::getSources();
        $auth = new Simple($sources[0]);
        $homePage = $this->urlGenerator->generate('xdmod_home');
        $logoutURL = $auth->getLogoutURL($homePage);
        $logoutResponse = new RedirectResponse($logoutURL);
        $event->setResponse($logoutResponse);
    }
}
