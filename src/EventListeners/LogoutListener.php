<?php

declare(strict_types=1);

namespace CCR\EventListeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function onLogout(LogoutEvent $event): void
    {
        $this->logger->debug('*** Logging Out w/ Logout Listener *** ');
        $request = $event->getRequest();
        $token = $request->getSession()->get('xdmod_token');
        \XDSessionManager::logoutUser($token);
        $request->getSession()->invalidate();
    }
}

