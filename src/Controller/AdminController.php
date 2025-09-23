<?php

declare(strict_types=1);

namespace Access\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;

/**
 * This controller is nominally responsible for user administrative tasks.
 */
class AdminController extends BaseController
{

    /**
     * Updates a user so that they will be prompted to see the User Tour the next time they log in.
     *
     * @param Request $request
     * @return Response
     *
     * @throws Exception if the user calling this endpoint is not authorized to do so.
     * @throws BadRequestHttpException if no user is found for the provided uid.
     * @throws BadRequestHttpException if the viewedTour parameter is any integer value other than 0 or 1.
     */
    #[Route('{prefix}/reset_user_tour_viewed', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function resetUserTourViewed(Request $request): Response
    {
        $this->authorize($request, ['mgr']);

        $viewedTour = $this->getIntParam($request, 'viewedTour', true);
        $selectedUser = XDUser::getUserByID(
            $this->getIntParam($request, 'uid', true)
        );

        if (!isset($selectedUser)) {
            throw new BadRequestHttpException('User not found');
        }

        if (!in_array($viewedTour, [0, 1])) {
            throw new BadRequestHttpException('Invalid data parameter');
        }

        $storage = new \UserStorage($selectedUser, 'viewed_user_tour');
        $upserted = $storage->upsert(0, ['viewedTour' => $viewedTour]);

        if (!isset($upserted)) {
            $this->logger->error(
                sprintf(
                    'reset_user_tour_viewed failed for %s (%s)',
                    $selectedUser->getUsername(),
                    $selectedUser->getUserID()
                )
            );

            return $this->json([
                [
                    'success' => false,
                    'total' => 0,
                    'message' => 'An error has occurred while updating this user, please contact support.'
                ]
            ]);
        }

        return $this->json(
            [
                'success' => true,
                'total' => 1,
                'message' => 'This user will be now be prompted to view the New User Tour the next time they visit XDMoD'
            ]
        );
    }

}
