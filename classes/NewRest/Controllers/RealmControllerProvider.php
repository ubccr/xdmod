<?php namespace NewRest\Controllers;


use Models\Realm;
use Models\Services\Realms;
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
        $conversions = '\NewRest\Utilities\Conversions';

        $controller->get("$root", "$class::listRealms");
        $controller->get("$root/module", "$class::listRealmsByModule");
        $controller->get("$root/user", "$class::listRealmsByUser");
        $controller->get("$root/user/{userId}", "$class::listRealmsByUser")
            ->assert('userId', '\d+')
            ->convert('userId', "$conversions::toInt");
    }

    public function listRealms(Request $request, Application $app)
    {
        $realms = Realms::listRealms();
        $success = isset($realms) && count($realms) > 0;
        $data = array();
        if ($success == true) {
            $data = $this->reduceArray($realms, 'getDisplay');
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
            $success = isset($results) && count($results) > 0;
            $data = array();
            if ($success == true) {
                $data = $this->reduceArray($results, 'getDisplay');
            }
            return $app->json(array(
                'success' => $success,
                'data' => $data
            ));
        }

        if (isset($moduleName)) {
            $results = Realms::listRealmsForModuleName($moduleName);
            $success = isset($results) && count($results) > 0;
            $data = array();
            if ($success == true) {
                $data = $this->reduceArray($results, 'getDisplay');
            }
            return $app->json(array(
                'success' => $success,
                'data' => $data
            ));
        }

        return $app->json(array(
            'success' => false,
            'data' => array()
        ));
    }

    public function listRealmsByUser(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);

        return $this->listRealmsByUserId($request, $app, $user->getUserID());
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param integer $userId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listRealmsByUserId(Request $request, Application $app, $userId)
    {
        $realms = Realms::listRealmsForUserId($userId);
        $success = isset($realms) && count($realms) > 0;
        $data = array();
        if ($success == true) {
            $data = array_reduce($realms, function ($carry, Realm $item) {
                $carry []= $item->getDisplay();
                return $carry;
            }, array());
        }


        return $app->json(array(
            'success'=> $success,
            'data' => $data
        ));
    }

    private function reduceArray(array $source, $functionName)
    {
        return array_reduce($source, function($carry, $item) use($functionName) {
            $carry[]= $item->$functionName();
            return $carry;
        }, array());
    }



}