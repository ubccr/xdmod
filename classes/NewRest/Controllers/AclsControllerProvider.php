<?php namespace NewRest\Controllers;

use CCR\DB;
use Silex\Application;
use Silex\ControllerCollection;

use Symfony\Component\HttpFoundation\Request;
use User\Acls;

class AclsControllerProvider extends BaseControllerProvider
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
        $class = get_class($this);
        $controller->get("$root/", "$class::listAcls");
    }

    public function listAcls(Request $request, Application $app)
    {
        $connection = DB::factory('database');
        $user = $request->attributes->get(BaseControllerProvider::_USER);
        $acls = Acls::listAcls($connection, $user);
        return $app->json($acls);
    }
}
