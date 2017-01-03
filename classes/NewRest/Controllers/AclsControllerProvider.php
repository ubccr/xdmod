<?php namespace NewRest\Controllers;

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
        $conversions = '\NewRest\Utilities\Conversions';

        $controller->get("$root/", "$class::listAcls");
        $controller->post("$root/", "$class::createAcl");
        $controller->get("$root/{id}", "$class::getAcl")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");
        $controller->put("$root/{id}", "$class::updateAcl")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");
        $controller->delete("$root/{id}", "$class::deleteAcl")
            ->assert('id', '\d+')
            ->convert('id', "$conversions::toInt");

        $controller->get("$root/current", "$class::listUserAcls");
    }

    public function listAcls(Request $request, Application $app)
    {
        return $app->json(
            array(
                'success' => true,
                'results' => Acls::getAcls()
            )
        );
    }

    public function createAcl(Request $request, Application $app)
    {
        return $app->json(array('success' => false));
    }

    public function getAcl(Request $request, Application $app, $id)
    {
        return $app->json(array('success' => false));

    }

    public function updateAcl(Request $request, Application $app, $id)
    {
        return $app->json(array('success' => false));
    }

    public function deleteAcl(Request $request, Application $app, $id)
    {
        return $app->json(array('success' => false));
    }

    public function listUserAcls(Request $request, Application $app)
    {
        $user = $request->attributes->get(BaseControllerProvider::_USER);
        $acls = Acls::listUserAcls($user);
        return $app->json(array('results' => $acls));
    }
}
