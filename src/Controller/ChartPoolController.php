<?php

namespace CCR\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDChartPool;
use XDUser;
use function xd_response\buildError;

/**
 *
 */
class ChartPoolController extends BaseController
{

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/chart_pool.php', methods: ['POST'])]
    #[Route('/chart_pool')]
    public function index(Request $request): Response
    {
        try {
            $user = $this->authorize($request);
        } catch (Exception $e) {
            return $this->json(buildError(new \SessionExpiredException()), 401);
        }

        $operation = $this->getStringParam($request, 'operation', true);
        switch ($operation) {
            case 'add_to_queue':
                return $this->addToQueue($request, $user);
            case 'remove_from_queue':
                return $this->removeFromQueue($request, $user);
            default:
                throw new BadRequestHttpException('invalid operation specified');
        }
    }

    /**
     * @param Request $request
     * @param XDUser $user
     * @return Response
     * @throws Exception
     */
    private function addToQueue(Request $request, XDUser $user): Response
    {
        $chartTitle = $this->getStringParam($request, 'chart_title', false, 'Untitled Chart');
        $chartId = $this->getStringParam($request, 'chart_id');

        /* this is freaking ugly, but it's here so that we can maintain the same expected test output. */
        if (is_null($chartId)) {
            return $this->json(buildError("A chart identifier must be specified"));
        } elseif ($chartId === '') {
            return $this->json(buildError("Invalid value specified for 'chart_id'."));
        } elseif (empty($chartId)){
            return $this->json(buildError("A chart identifier must be specified"));
        }
        $chartDrillDetails = $this->getStringParam($request, 'chart_drill_details');
        $chartDateDesc = $this->getStringParam($request, 'chart_date_desc');

        $chart_pool = new XDChartPool($user);

        try {
            $chart_pool->addChartToQueue(
                $chartId,
                $chartTitle,
                $chartDrillDetails,
                $chartDateDesc
            );
        } catch (Exception $e) {
            return $this->json(buildError($e->getMessage()));
        }

        return $this->json([
            'success' => true,
            'action' => 'add'
        ]);
    }

    /**
     * @param Request $request
     * @param XDUser $user
     * @return Response
     * @throws Exception
     */
    private function removeFromQueue(Request $request, XDUser $user): Response
    {
        $chart_pool = new XDChartPool($user);

        $chartTitle = $this->getStringParam($request, 'chart_title', false, 'Untitled Chart');
        $chartId = str_replace('title=' . $chartTitle, 'title=' . urlencode($chartTitle), $this->getStringParam($request, 'chart_id', true));

        $chart_pool->removeChartFromQueue($chartId);
        return $this->json([
            'success' => true,
            'action' => 'remove'
        ]);
    }

}
