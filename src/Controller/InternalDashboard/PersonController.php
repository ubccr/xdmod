<?php

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use CCR\Security\Attributes\MgrRequired;
use Models\Services\Organizations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
#[Route('{prefix}persons', requirements: ['prefix' => '.*'])]
class PersonController extends BaseController
{

    /**
     *
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    #[MgrRequired]
    #[Route('/{id}/organization', requirements: ["id" => "(-)?\d+"], methods: ['GET'])]
    public function getOrganizationForPerson(Request $request, int $id): Response
    {
        return $this->json([
            'success' => true,
            'results' => [
                'id' => Organizations::getOrganizationIdForPerson($id)
            ]
        ]);
    }
}
