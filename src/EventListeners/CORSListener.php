<?php declare(strict_types=1);

namespace CCR\EventListeners;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * This event listener is responsible for
 */
#[AsEventListener(event: ResponseEvent::class, method: 'onResponse', priority: -10)]
class CORSListener
{

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $origin = $request->headers->get('Origin');
        if ($origin !== null) {
            try {
                $corsDomains = \xd_utilities\getConfiguration('cors', 'domains');
                if (!empty($corsDomains)) {
                    $allowedCorsDomains = explode(',', $corsDomains);
                    if (in_array($origin, $allowedCorsDomains)) {
                        // If these headers change similar updates will need to be made to the `error` section below
                        $response->headers->set('Access-Control-Allow-Origin', $origin);
                        $response->headers->set('Access-Control-Allow-Headers', 'x-requested-with, content-type');
                        $response->headers->set('Access-Control-Allow-Credentials', 'true');
                        $response->headers->set('Vary', 'Origin');
                    }
                }
            } catch (\Exception $e) {
                // this catches if the section or config item does not exist
                // in that case we just carry on
            }
        }
    }
}

