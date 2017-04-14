<?php namespace NewRest\Controllers;

use Silex\Application;
use Silex\ControllerCollection;

use Statistic;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use User\Acl;
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

        $isAuthorized = function (Request $request, Application $app) {
            $authorized = $this->isAuthorized($request, array('mgr'));
            if (!$authorized) {
                throw new UnauthorizedHttpException('Basic realm="acls"','Not authorized for the requested operation.');
            }
        };

        $controller->get("$root/", "$class::listAcls")
            ->before($isAuthorized);
        $controller->post("$root/", "$class::createAcl")
            ->before($isAuthorized);
        $controller->get("$root/{id}", "$class::getAcl")
            ->assert('id', '^\d+')
            ->convert('id', "$conversions::toInt")
            ->before($isAuthorized);
        $controller->put("$root/{id}", "$class::updateAcl")
            ->assert('id', '^\d+')
            ->convert('id', "$conversions::toInt")
            ->before($isAuthorized);
        $controller->delete("$root/{id}", "$class::deleteAcl")
            ->assert('id', '^\d+')
            ->convert('id', "$conversions::toInt")
            ->before($isAuthorized);

        $controller->get("$root/current", "$class::listUserAcls")
            ->before($isAuthorized);
        $controller->put("$root/current/{id}", "$class::addUserAcl")
            ->assert('id', '^\d+')
            ->convert('id', "$conversions::toInt")
            ->before($isAuthorized);
        $controller->delete("$root/current/{id}", "$class::removeUserAcl")
            ->assert('id', '^\d+')
            ->convert('id', "$conversions::toInt")
            ->before($isAuthorized);

        $controller->get("$root/menus/disabled", "$class::getDisabledMenus");

        $controller->get("$root/statistics/permitted", "$class::getPermittedStatistics");

    }

    public function listAcls(Request $request, Application $app)
    {
        $acls = Acls::getAcls();

        return $app->json(array(
            'success' => true,
            'results' => $acls
        ));
    }

    public function createAcl(Request $request, Application $app)
    {
        $moduleId = self::getIntParam($request, Acl::MODULE_ID, true);
        $aclTypeId = self::getIntParam($request, Acl::ACL_TYPE_ID, true);
        $name = self::getStringParam($request, Acl::NAME, true);
        $display = self::getStringParam($request, Acl::DISPLAY, false, $name);
        $enabled = self::getBooleanParam($request, Acl::ENABLED, false);

        $acl = Acls::createAcl(
            new Acl(
                array(
                    Acl::MODULE_ID => $moduleId,
                    Acl::ACL_TYPE_ID => $aclTypeId,
                    Acl::NAME => $name,
                    Acl::DISPLAY => $display,
                    Acl::ENABLED => $enabled
                )
            )
        );

        $success = isset($acl);
        $status = true == $success ? 200 : 500;

        return $app->json(array(
            'success' => $success,
            'results' => $acl
        ), $status);
    }

    public function getAcl(Request $request, Application $app, $id)
    {
        $acl = Acls::getAcl($id);
        $success = isset($acl);
        $status = true == $success ? 200 : 404;

        return $app->json(array(
            'success' => $success,
            'results' => $acl
        ), $status);
    }

    public function updateAcl(Request $request, Application $app, $id)
    {
        $moduleId = self::getIntParam($request, Acl::MODULE_ID, true);
        $aclTypeId = self::getIntParam($request, Acl::ACL_TYPE_ID, true);
        $name = self::getStringParam($request, Acl::NAME, true);
        $display = self::getStringParam($request, Acl::DISPLAY, false, $name);
        $enabled = self::getBooleanParam($request, Acl::ENABLED, false);

        $success = Acls::updateAcl(
            new Acl(
                array(
                    ACL::ACL_ID => $id,
                    Acl::MODULE_ID => $moduleId,
                    Acl::ACL_TYPE_ID => $aclTypeId,
                    Acl::NAME => $name,
                    Acl::DISPLAY => $display,
                    Acl::ENABLED => $enabled
                )
            )
        );
        $status = true == $success ? 200 : 500;

        return $app->json(array(
            'success' => $success,
            'result' => $success
        ), $status);
    }

    public function deleteAcl(Request $request, Application $app, $id)
    {
        $success = Acls::deleteAcl(
            new Acl(
                array(
                    Acl::ACL_ID => $id
                )
            )
        );
        $status = true == $success ? 200 : 500;
        return $app->json(array(
            'success' => $success,
            'results' => $success
        ), $status);
    }

    public function listUserAcls(Request $request, Application $app)
    {
        $user = $request->attributes->get(BaseControllerProvider::_USER);

        $acls = Acls::listUserAcls($user);
        $success = isset($acls);
        $status = true == $success ? 200 : 500;

        return $app->json(array(
            'success' => $success,
            'results' => $acls
        ), $status);
    }

    public function addUserAcl(Request $request, Application $app, $id)
    {
        $user = $request->attributes->get(BaseControllerProvider::_USER);

        $success = Acls::addUserAcl($user, $id);
        $status = true == $success ? 200 : 500;

        return $app->json(array(
            'success' => $success,
            'results' => $success
        ), $status);
    }

    public function removeUserAcl(Request $request, Application $app, $id)
    {
        $user = $request->attributes->get(BaseControllerProvider::_USER);

        $success = Acls::deleteUserAcl($user, $id);
        $status = true == $success ? 200 : 500;

        return $app->json(array(
            'success' => $success,
            'results' => $success
        ), $status);
    }

    public function getDisabledMenus(Request $request, Application $app)
    {
        $user = $request->get(BaseControllerProvider::_USER);
        $realm = self::getStringParam($request, 'realm');
        $realms = self::getStringParam($request, 'realms', false, array());

        if (is_string($realms)) {
            $realms = explode(',', $realms);
        }
        if (isset($realm)) {
            $realms []= $realm;
        }

        $menus = Acls::getDisabledMenus($user, $realms);
        $success = isset($menus);

        return $app->json(array(
            'success' => $success,
            'data' => $menus
        ));
    }

    public function getPermittedStatistics(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);

        $realm = $this->getStringParam($request, 'realm', true);
        $groupBy = $this->getStringParam($request, 'group_by', true);

        $statistics = Acls::getPermittedStatistics($user, $realm, $groupBy);
        $success = isset($statistics) && count($statistics) > 0;
        $data = array();
        if ($success == true) {
            $data = array_reduce($statistics, function($carry, Statistic $item){
                $carry []= $item->getName();
                return $carry;
            }, array());
        }

        return $app->json(array(
            'success' => $success,
            'data' => $data
        ));
    }

}
