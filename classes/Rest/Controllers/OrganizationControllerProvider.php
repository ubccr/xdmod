<?php

namespace NewRest\Controllers;

use Models\Services\Organizations;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

class OrganizationControllerProvider extends BaseControllerProvider
{

    /**
     * This function is responsible for the setting up of any routes that this
     * ControllerProvider is going to be managing. It *must* be overridden by
     * a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $controller->get("$root/default", '\NewRest\Controllers\OrganizationControllerProvider::getDefaultOrganization');
    }

    /**
     * Returns the default organization for this XDMoD Installation. If this is an
     *
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws \Exception
     */
    public function getDefaultOrganization(Request $request, Application $app)
    {
        // This function is, at the time of writing, should only be accessible from the internal
        // dashboard. Hence requiring the user to have the 'mgr' acl.
        $this->authorize($request, array(ROLE_ID_MANAGER));

        $multipleServiceProviders = \xd_utilities\getConfiguration('features', 'multiple_service_providers');

        // If we have multiple service providers then we can't know which should be the 'default'
        // so we return the 'Unknown' organization.
        if ($multipleServiceProviders === true) {
            $organizationId = UNKNOWN_ORGANIZATION_ID;
        } else {
            // Else we should have just one organization / service provider.
            $organizations = Organizations::getOrganizations();
            $organizationId = count($organizations) > 0 ? $organizations[0]['id'] : UNKNOWN_ORGANIZATION_ID;
        }

        return $app->json(array(
            'success' => true,
            'organization' => $organizationId
        ));
    }
}
