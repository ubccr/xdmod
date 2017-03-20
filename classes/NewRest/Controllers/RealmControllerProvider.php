<?php namespace NewRest\Controllers;


use Realm;
use Realms;
use Silex\Application;
use Silex\ControllerCollection;

use Symfony\Component\HttpFoundation\Request;

class RealmControllerProvider extends BaseControllerProvider
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

        $controller->get("$root", "$class::listRealms");
        $controller->get("$root/module", "$class::listRealmsByModule");
    }

    public function listRealms(Request $request, Application $app)
    {
        $realms = Realms::listRealms();
        $success = isset($realms);
        $data = array();
        if ($success == true) {
            $data = array_reduce($realms, function ($carry, Realm $item) {
                $carry []= $item->getName();
                return $carry;
            }, array());
        }

        return $app->json(array(
            'success' => $success,
            'data' => $data
        ));
    }

    public function listRealmsByModule(Request $request, Application $app)
    {
        $moduleId = $this->getIntParam($request, 'module_id');
        $moduleName = $this->getStringParam($request, 'module_name');

        if (!isset($moduleId) && !isset($moduleName)) {
            throw new \Exception('You must provide a module_id or module_name');
        }

        if (isset($moduleId)) {
            $results = Realms::listRealmsForModuleId($moduleId);
            $success = isset($results);
            return $app->json(array(
                'success' => $success,
                'data' => $results
            ));
        }

        if (isset($moduleName)) {
            $results = Realms::listRealmsForModuleName($moduleName);
            $success = isset($results);
            return $app->json(array(
                'success' => $success,
                'data' => $results
            ));
        }

        return $app->json(array(
            'success' => false,
            'data' => array()
        ));
    }

}