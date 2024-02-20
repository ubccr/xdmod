<?php
declare(strict_types=1);

namespace Access\Controller;

use CCR\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/about")
 * This controller handles the urls for XDMoD's 'About' tab.
 */
class AboutController extends BaseController
{
    /**
     * @Route("/xdmod", methods={"GET"})
     * @return Response
     */
    public function xdmod(): Response
    {
        return $this->render('about/xdmod.html.twig', [
            'xdmod_version' => \xd_versioning\getPortalVersion(true)
        ]);
    }

    /**
     * @Route("/open_xdmod", methods={"GET"})
     * @return Response
     */
    public function openXdmod(): Response
    {
        return $this->render('about/open_xdmod.html.twig');
    }

    /**
     * @Route("/supremm", methods={"GET"})
     * @return Response
     */
    public function supremm(): Response
    {
        return $this->render('about/supremm.html.twig');
    }

    /**
     * @Route("/federated", methods={"GET"})
     * @return Response
     * @throws Exception if unable to retrieve a connection to the 'datawarehouse' DB.
     */
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
            $lastCloudResults = $db->query('SELECT * FROM ' . implode($lastCloudQuery, ' UNION ALL SELECT * FROM '));
            foreach ($lastCloudResults as $result) {
                $instances[$result['prefix']]['lastCloudEvent'] = $result['event_ts'];
            }

            $parameters['instances'] = $instances;
        }

        return $this->render('about/federated.html.twig', $parameters);
    }

    /**
     * @Route("/roadmap", methods={"GET"})
     * @return Response
     */
    public function roadmap(): Response
    {
        return $this->render('about/roadmap.html.twig', [
            'header' => $this->getConfigValue('roadmap', 'header'),
            'url' => $this->getConfigValue('roadmap', 'url')
        ]);
    }

    /**
     * @Route("/team", methods={"GET"})
     * @return Response
     */
    public function team(): Response
    {
        return $this->render('about/team.html.twig');
    }

    /**
     * @Route("/publications", methods={"GET"})
     * @return Response
     */
    public function publications(): Response
    {
        return $this->render('about/publications.html.twig');
    }

    /**
     * @Route("/links", methods={"GET"})
     * @return Response
     */
    public function links(): Response
    {
        return $this->render('about/links.html.twig');
    }

    /**
     * @Route("/release_notes/{xdmodType}", methods={"GET"})
     * @param string $xdmodType
     * @return Response
     */
    public function releaseNotes(string $xdmodType): Response
    {
        if (!in_array($xdmodType, ['xdmod', 'xsede'])) {
            throw new BadRequestHttpException('Invalid XDMoD installation type specified.');
        }

        $xsedeInstall = $this->getConfigValue('features', 'xsede', false);
        if (!$xsedeInstall && $xdmodType === 'xsede') {
            throw new BadRequestHttpException('Invalid XDMoD installation type specified.');
        }

        return $this->render("about/{$xdmodType}_release_notes.html.twig");
    }

    /**
     * @Route("/presentations", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function teamPresentations(Request $request): Response
    {
        return $this->render('about/presentations.html.twig');
    }
}