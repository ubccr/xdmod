<?php namespace NewRest\Controllers;



use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerCollection;
use Tabs;

class UIControllerProvider extends BaseControllerProvider
{

    /**
     * @inheritdoc
     */
    public function setupRoutes(Application $app, ControllerCollection $controller)
    {
        $root = $this->prefix;
        $class = get_class($this);

        $controller->get("$root/tabs", "$class::listTabsForUser");
    }

    public function listTabsForUser(Request $request, Application $app)
    {
        $user = $request->get(BaseControllerProvider::_USER);

        $tabs = Tabs::getTabsForUser($user);

        $success = isset($tabs);

        return $app->json(array(
            'success' => $success,
            'totalCount' => 1,
            'message' => '',
            'data' => array(
                'tabs' => json_encode(array_values($tabs))
            )
        ));
    }
}
