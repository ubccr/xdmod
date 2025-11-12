<?php

declare(strict_types=1);

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use CCR\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
class LogController extends BaseController
{

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/log.php', name: 'log_index_legacy', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $operation = $request->get('operation');
        switch ($operation) {
            case 'get_levels':
                return $this->getLevels($request);
            case 'get_summary':
                return $this->getSummary($request);
            case 'get_messages':
                return $this->getMessages($request);
            default:
                throw new BadRequestHttpException();
        }
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    #[Route('{prefix}/internal_dashboard/logs/levels', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getLevels(Request $request): Response
    {
        $levels = [
            ['id' => \CCR\Log::EMERG, 'name' => 'Emergency'],
            ['id' => \CCR\Log::ALERT, 'name' => 'Alert'],
            ['id' => \CCR\Log::CRIT, 'name' => 'Critical'],
            ['id' => \CCR\Log::ERR, 'name' => 'Error'],
            ['id' => \CCR\Log::WARNING, 'name' => 'Warning'],
            ['id' => \CCR\Log::NOTICE, 'name' => 'Notice'],
            ['id' => \CCR\Log::INFO, 'name' => 'Info'],
            ['id' => \CCR\Log::DEBUG, 'name' => 'Debug'],
        ];

        return $this->json([
            'success' => true,
            'response' => $levels,
            'count' => count($levels)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/logs/messages', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getMessages(Request $request): Response
    {
        $pdo = DB::factory('logger');

        $sql = '
        SELECT id, logtime, ident, priority, message
        FROM log_table
    ';

        $clauses = array();
        $params = array();

        $ident = $this->getStringParam($request, 'ident');

        if (isset($ident)) {
            $clauses[] = 'ident = ?';
            $params[] = $ident;
        }

        $logLevels = $request->get( 'logLevels');
        if (isset($logLevels) && is_array($logLevels)) {
            $clauses[] = sprintf(
                'priority IN (%s)',
                implode(',', array_pad([], count($logLevels), '?'))
            );
            $params = array_merge($params, $logLevels);
        }

        $onlyMostRecent = $this->getBooleanParam($request, 'only_most_recent');
        if (isset($onlyMostRecent) && $onlyMostRecent) {
            if (!isset($ident)) {
                throw new Exception('"ident" required');
            }

            $summary = \Log\Summary::factory($ident);

            if (null !== ($startRowId = $summary->getProcessStartRowId())) {
                $clauses[] = 'id >= ?';
                $params[] = $startRowId;
            }

            if (null !== ($endRowId = $summary->getProcessEndRowId())) {
                $clauses[] = 'id <= ?';
                $params[] = $endRowId;
            }
        } else {
            $startDate = $this->getStringParam($request, 'start_date');
            if (isset($startDate)) {
                $clauses[] = 'logtime >= ?';
                $params[] = $startDate . ' 00:00:00';
            }

            $endDate = $this->getStringParam($request, 'end_date');
            if (isset($endDate)) {
                $clauses[] = 'logtime <= ?';
                $params[] = $endDate . ' 23:59:59';
            }
        }

        if (count($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY id DESC';

        $start = $this->getIntParam($request, 'start');
        $limit = $this->getIntParam($request, 'limit');
        if (isset($start) && isset($limit)) {
            $sql .= sprintf(
                ' LIMIT %d, %d',
                $start,
                $limit
            );
        }

        $returnData = [
            'success' => true,
            'response' => $pdo->query($sql, $params),
        ];

        $sql = 'SELECT COUNT(*) AS count FROM log_table';

        if (count($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        list($countRow) = $pdo->query($sql, $params);

        $returnData['count'] = $countRow['count'];

        return $this->json($returnData);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/logs/summary', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getSummary(Request $request): Response
    {
        $ident = $this->getStringParam($request, 'ident', true);
        $summary = \Log\Summary::factory($ident);
        return $this->json([
            'success' => true,
            'response' => [$summary->getData()],
            'count' => 1
        ]);
    }
}
