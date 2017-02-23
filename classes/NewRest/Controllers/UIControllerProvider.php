<?php namespace NewRest\Controllers;



use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerCollection;
use Tabs;
use Tab;

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

        // Ensure that the results are returned in the format that is expected.
        $tabs = array_reduce($tabs, function($carry, Tab $item) {
            $carry []= array(
                'isDefault' => $item->getIsDefault(),
                'javascriptClass' => $item->getJavascriptClass(),
                'javascriptReference' => $item->getJavascriptReference(),
                'pos' => $item->getPosition(),
                'tab' => $item->getName(),
                'title' => $item->getDisplay(),
                'tooltip' => $item->getTooltip(),
                'userManualSectionName' => $item->getUserManualSectionName()
            );
            return $carry;
        }, array());

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
