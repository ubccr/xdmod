<?php

declare(strict_types=1);

namespace Access\Controller;

use CCR\DB;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 */
class AdminController extends AbstractController
{

    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/reset_user_tour_viewed", methods={"POST"})
     * @return void
     * @throws \Exception
     */
    public function resetUserTourViewed(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $params = ['username' => $user->getUserIdentifier()];

        return $this->json(
            [
                'success' => true,
                'total' => 1,
                'message' => 'This user will now be prompted to view the New User Tour the next time they visit XDMoD'
            ]
        );
    }

}