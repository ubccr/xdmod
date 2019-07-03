<?php

namespace Rest\Controllers;

use Models\Services\Organizations;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use DataWarehouse\Query\Exceptions\BadRequestException;
use XDUser;
use Rest\Utilities\Authentication;

/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */
class AdminControllerProvider extends BaseControllerProvider
{
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $class = get_class($this);

        $controller->post("$root/reset_user_tour_viewed", "$class::resetUserTourViewed");
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function resetUserTourViewed(Request $request, Application $app)
    {
        $this->authorize($request, array('mgr'));
        $viewedTour = $this->getIntParam($request, 'viewedTour', true);
        $selected_user = XDUser::getUserByID($this->getIntParam($request, 'uid', true));

        if (!in_array($viewedTour, [0,1])) {
            throw new BadRequestException('Invalid data parameter');
        }

        $storage = new \UserStorage($selected_user, 'viewed_user_tour');
        $storage->upsert(0, ['viewedTour' => $viewedTour]);

        return $app->json(
             array(
                 'success' => true,
                 'total' => 1,
                 'message' => 'This user will be now be propmted to view the New User Tour the next time they visit XDMoD'
             )
         );
    }
}
