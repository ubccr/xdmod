<?php

namespace Rest\Controllers;

use Models\Services\Organizations;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

use Rest\Utilities\Authentication;

/**
 * Class PersonControllerProvider
 *
 * This class is responsible for maintaining the routes having to do with `persons`
 * REST stack.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class PersonControllerProvider extends BaseControllerProvider
{
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $class = get_class($this);
        $conversions = '\Rest\Utilities\Conversions';

        $controller
            ->get("$root/{id}/organization", "$class::getOrganizationForPerson")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getOrganizationForPerson(Request $request, Application $app, $id)
    {
        // Ensure that this route is only authorized for users with the 'mgr' role.
        $this->authorize($request, array('mgr'));

        return $app->json(
            array(
                'success' => true,
                'results' => array(
                    'id' => Organizations::getOrganizationIdForPerson($id)
                )
            )
        );
    }

}

