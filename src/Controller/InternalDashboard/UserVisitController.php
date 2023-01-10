<?php

declare(strict_types=1);

namespace Access\Controller\InternalDashboard;

use Access\Controller\BaseController;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/internal_dashboard/users/visits")
 */
class UserVisitController extends BaseController
{

    /**
     * @Route("", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getUserVisits(Request $request): Response
    {
        list($data,) = $this->getUserVisitData($request);
        return $this->json([
            'success' => true,
            'stats' => $data
        ]);
    }

    /**
     * @Route("/export", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function exportUserVisits(Request $request): Response
    {
        list($data, list($timeframe,)) = $this->getUserVisitData($request);
        $content = sprintf("%s\n", implode(',', array_keys($data['stats'][0])));
        return new Response($content, 200, [
            'Content-Type' => 'application/xls',
            'Content-Disposition' => sprintf('attachment;filename="xdmod_visitation_stats_by_%s.csv"', $timeframe)
        ]);
    }

    /**
     * @return array in the form [userVisits, [timeframe, userTypes]]
     * @throws Exception
     */
    private function getUserVisitData(Request $request): array
    {
        $timeframe = $this->getStringParam($request, 'timeframe', true);
        $userTypes = explode(',', $this->getStringParam($request, 'user_types', true));
        if (strtolower($timeframe) !== 'year' && strtolower($timeframe) !== 'month') {
            throw new BadRequestHttpException('Invalid value specified for the timeframe');
        }
        return [\XDStatistics::getUserVisitStats($timeframe, $userTypes), [$timeframe, $userTypes]];
    }
}