<?php
declare(strict_types=1);

namespace Access\Controller;

use CCR\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller handles the urls for XDMoD's 'About' tab.
 */
#[Route("/about")]
class AboutController extends BaseController
{
    /**
     * @return Response
     */
    #[Route('/xdmod', methods: ["GET"])]
    #[Route('/xdmod.php', methods: ["GET"])]
    public function xdmod(): Response
    {
        return $this->render('about/xdmod.html.twig', [
            'xdmod_version' => \xd_versioning\getPortalVersion(true)
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/open_xdmod', methods: ["GET"])]
    #[Route('/openxd.html', methods: ["GET"])]
    public function openXdmod(): Response
    {
        return $this->render('about/open_xdmod.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/supremm', methods: ['GET'])]
    #[Route('/supremm.html', methods: ['GET'])]
    public function supremm(): Response
    {
        return $this->render('about/supremm.html.twig');
    }

    /**
     * @return Response
     * @throws Exception if unable to retrieve a connection to the 'datawarehouse' DB.
     */
    #[Route('/federated', methods: ["GET"])]
    #[Route('/federated.php', methods: ["GET"])]
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

        return $this->render('about/federated.html.twig', $parameters);
    }

    /**
     * @return Response
     */
    #[Route('/roadmap', methods: ['GET'])]
    #[Route('/roadmap.php', methods: ["GET"])]
    public function roadmap(): Response
    {
        return $this->render('about/roadmap.html.twig', [
            'header' => $this->getConfigValue('roadmap', 'header'),
            'url' => $this->getConfigValue('roadmap', 'url')
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/team', methods: ['GET'])]
    #[Route('/team.html', methods: ['GET'])]
    public function team(): Response
    {
        return $this->render('about/team.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/publications', methods: ['GET'])]
    #[Route('/publications.html', methods: ['GET'])]
    public function publications(): Response
    {
        return $this->render('about/publications.html.twig');
    }

    /**
     * @return Response
     */
    #[Route('/links', methods: ['GET'])]
    #[Route('/links.html', methods: ['GET'])]
    public function links(): Response
    {
        return $this->render('about/links.html.twig');
    }

    /**
     * @param string $xdmodType
     * @return Response
     */
    #[Route('/release_notes/{xdmodType}', methods: ['GET'])]
    public function releaseNotes(string $xdmodType): Response
    {
        if (str_contains($xdmodType, '.')) {
            $parts = explode('.', $xdmodType);
            $xdmodType = $parts[0];
        }
        if (!in_array($xdmodType, ['xdmod', 'xsede'])) {
            throw new BadRequestHttpException('Invalid XDMoD installation type specified.');
        }

        $xsedeInstall = $this->getConfigValue('features', 'xsede', false);
        if (!$xsedeInstall && $xdmodType === 'xsede') {
            throw new BadRequestHttpException('Invalid XDMoD installation type xsede specified.');
        }

        return $this->render("about/{$xdmodType}_release_notes.html.twig");
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/presentations', methods: ['GET'])]
    #[Route('/presentations.html', methods: ['GET'])]
    public function teamPresentations(Request $request): Response
    {
        return $this->render('about/presentations.html.twig');
    }
}
