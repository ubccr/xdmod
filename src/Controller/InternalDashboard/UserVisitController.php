<?php

declare(strict_types=1);

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
#[Route('{prefix}/internal_dashboard/users/visits', requirements: ['prefix' => '.*'],)]
class UserVisitController extends BaseController
{
    public static $columns = [
        "Last Name",
        "First Name",
        "E-Mail",
        "Roles",
        "Visit Frequency",
        "User Type",
        "Date",
        "Count"
    ];

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('', methods: ['POST'])]
    public function getUserVisits(Request $request): Response
    {
        list($data,) = $this->getUserVisitData($request);
        return $this->json([
            'success' => true,
            'stats' => $data
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/export', methods: ['POST'])]
    public function exportUserVisits(Request $request): Response
    {
        list($data, list($timeframe,)) = $this->getUserVisitData($request);

        $data = array_map(function($row) {
            return implode(',', $row);
        }, $data);
        array_unshift($data, implode(',', self::$columns));

        $content = sprintf("%s\n", implode("\n", $data));
        $this->logger->debug(sprintf("Export User Visits: Content: %s", $content));
        return new Response($content, 200, [
            'Content-Type' => 'text/csv',
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
        $results = \XDStatistics::getUserVisitStats($timeframe, $userTypes);
        return [$results, [$timeframe, $userTypes]];
    }
}
