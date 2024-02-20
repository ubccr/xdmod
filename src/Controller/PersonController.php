<?php

namespace Access\Controller;

use Exception;
use Models\Services\Organizations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/persons")
 */
class PersonController extends BaseController
{

    /**
     * @Route("/{id}/organization", methods={"GET"}, requirements={"id": "(-)?\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws Exception
     */
    public function getOrganizationForPerson(Request $request, int $id): Response
    {
        $this->authorize($request, ['mgr']);

        return $this->json([
            'success' => true,
            'results' => [
                'id' => Organizations::getOrganizationIdForPerson($id)
            ]
        ]);
    }
}