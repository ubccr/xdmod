<?php

declare(strict_types=1);

namespace Access\Controller;

use CCR\DB;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/internal_dashboard/accounts")
 */
class AccountController extends BaseController
{

    /**
     * @Route("/requests", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getRequests(Request $request): Response
    {
        $pdo = DB::factory('database');
        $md5Only = $this->getBooleanParam($request, 'md5only', false, false);

        $results = $pdo->query("SELECT id, first_name, last_name, organization, title, email_address, field_of_science, additional_information, time_submitted, status, comments FROM AccountRequests");

        $response['success'] = true;
        $response['count'] = count($results);
        $response['response'] = $results;

        $response['md5'] = md5(json_encode($response));

        if ($md5Only) {
            unset($response['count']);
            unset($response['response']);
        }

        return $this->json($response);
    }

    /**
     * @Route("/{requestId}", methods={"PUT"})
     * @param Request $request
     * @param string $requestId
     * @return Response
     * @throws Exception
     */
    public function updateRequest(Request $request, string $requestId): Response
    {
        $comments = $this->getStringParam($request, 'comments', true);
        $pdo = DB::factory('database');

        $results = $pdo->query('SELECT id FROM AccountRequests WHERE id=:id', ['id' => $requestId]);

        // Check to see if we have an AccountRequest that matches the provided $requestId before updating it.
        if (count($results) == 1) {
            $pdo->execute('UPDATE AccountRequests SET comments=:comments WHERE id=:id', ['comments' => $comments, 'id' => $requestId]);
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['message'] = 'invalid id specified';
        }

        return $this->json($response);
    }

    /**
     * @Route("", methods={"DELETE"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function deleteRequest(Request $request): Response
    {
        $requestIds = $this->getStringParam($request, 'id', true, null, '/^\d+(,\d+)*$/');
        $ids = array_map('intval', explode(',', $requestIds));

        $queryPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
        $query = "DELETE FROM AccountRequests WHERE id IN ($queryPlaceholders)";

        $pdo = DB::factory('database');
        $pdo->execute($query, $ids);

        return $this->json(['success' => true]);
    }




}