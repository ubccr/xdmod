<?php namespace NewRest\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerCollection;
use Tabs;
use Tab;

class UIControllerProvider extends BaseControllerProvider
{


    /**
     * Result of JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_NUMERIC_CHECK
     * @var integer
     */
    const DEFAULT_JSON_FLAGS = 47;

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
            $carry [$item->getName()]= array(
                'isDefault' => (bool) $item->getIsDefault(),
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

        return $app->json(array(
            'success' => true,
            'totalCount' => 1,
            'message' => '',
            'data' => array(
                'tabs' => json_encode(array_values($tabs), self::DEFAULT_JSON_FLAGS)
            )
        ));
    }
}
