<?php
declare(strict_types=1);

namespace CCR\Controller;

use CCR\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller handles the urls for XDMoD's 'About' tab.
 */
class AboutController extends BaseController
{
    /**
     * @return Response
     */
    #[Route('/about/xdmod', methods: ["GET"])]
    #[Route('/about/xdmod.html', methods: ["GET"])]
    public function xdmod(): Response
    {
        return $this->render('twig/about/xdmod.html.twig', [
            'xdmod_version' => \xd_versioning\getPortalVersion(true)
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/about/open_xdmod', methods: ["GET"])]
    #[Route('/about/openxd.html', methods: ["GET"])]
    public function openXdmod(): Response
    {
        return $this->render('twig/about/open_xdmod.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/about/supremm', methods: ['GET'])]
    #[Route('/about/supremm.html', methods: ['GET'])]
    public function supremm(): Response
    {
        return $this->render('twig/about/supremm.html.twig');
    }

    /**
     * @return Response
     * @throws Exception if unable to retrieve a connection to the 'datawarehouse' DB.
     */
    #[Route('/about/federated', methods: ["GET"])]
    #[Route('/about/federated.html', methods: ["GET"])]
    public function federated(): Response
    {
        $parameters = [];
        $federatedRole = $this->getConfigValue('federated', 'role');
        $parameters['federated_role'] = $federatedRole;

        if ($federatedRole === 'instance') {
            $parameters['hub_url'] = $this->getConfigValue('federated', 'huburl');
        } elseif ($federatedRole === 'hub') {
            $db = DB::factory('datawarehouse');
            $instanceResults = $db->query('SELECT * FROM federation_instances;');

            $instances = [];
            $lastCloudQuery = [];
            $derived = 1;
            foreach ($instanceResults as $instance) {
                $prefix = $instance['prefix'];
                $extra = json_decode($instance['extra'], true);
                $instances[$prefix] = [
                    'contact' => $extra['contact'],
                    'url' => $extra['url'],
                    'lastCloudEvent' => null,
                    'lastJobTask' => null
                ];
                unset($extra['contact']);
                unset($extra['url']);
                $instances[$prefix]['extra'] = $extra;
                array_push(
                    $lastCloudQuery,
                    '(SELECT \'' . $prefix . '\' AS prefix, FROM_UNIXTIME(event_time_ts) as event_ts FROM `' . $prefix . '-modw_cloud`.`event` ORDER BY 2 DESC LIMIT 1) `A' . $derived . '`'
                );
                $derived++;
            }
            $lastCloudResults = $db->query('SELECT * FROM ' . implode(' UNION ALL SELECT * FROM ', $lastCloudQuery));
            foreach ($lastCloudResults as $result) {
                $instances[$result['prefix']]['lastCloudEvent'] = $result['event_ts'];
            }

            $parameters['instances'] = $instances;
        }

        return $this->render('twig/about/federated.html.twig', $parameters);
    }

    /**
     * @return Response
     */
    #[Route('/about/roadmap', methods: ['GET'])]
    #[Route('/about/roadmap.html', methods: ["GET"])]
    public function roadmap(): Response
    {
        $header = $this->getConfigValue('roadmap', 'header');
        $url = $this->getConfigValue('roadmap', 'url');
        return $this->render('twig/about/roadmap.html.twig', [
            'header' => $header,
            'url' => $url
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/about/team', methods: ['GET'])]
    #[Route('/about/team.html', methods: ['GET'])]
    public function team(): Response
    {
        return $this->render('twig/about/team.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/about/publications', methods: ['GET'])]
    #[Route('/about/publications.html', methods: ['GET'])]
    public function publications(): Response
    {
        return $this->render('twig/about/publications.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/about/links', methods: ['GET'])]
    #[Route('/about/links.html', methods: ['GET'])]
    public function links(): Response
    {
        return $this->render('twig/about/links.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/about/release_notes/xdmod', methods: ['GET'])]
    public function releaseNotes(): Response
    {
        return $this->render("twig/about/xdmod_release_notes.html.twig");
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/about/presentations', methods: ['GET'])]
    #[Route('/about/presentations.html', methods: ['GET'])]
    public function teamPresentations(Request $request): Response
    {
        return $this->render('twig/about/presentations.html.twig');
    }
}
