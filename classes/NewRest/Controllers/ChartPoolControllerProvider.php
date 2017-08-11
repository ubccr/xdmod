<?php namespace NewRest\Controllers;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use XDChartPool;

class ChartPoolControllerProvider extends BaseControllerProvider
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
        $controller->post("$root/add", "$class::addToQueue");
        $controller->post("$root/remove", "$class::removeFromQueue");
    }

    public function addToQueue(Request $request, Application $app)
    {
        $this->authorize($request, array('usr'));

        $result = array();

        $user = $this->getUserFromRequest($request);

        $chartId = $this->getStringParam($request, 'chart_id', true);
        $chartDrillDetails = $this->getStringParam($request, 'chart_drill_details', true);
        $chartDateDesc = $this->getStringParam($request, 'chart_date_desc', true);

        $chartTitle = $this->getStringParam($request, 'chart_title', false, 'Untitled Chart');

        $chart_pool = new XDChartPool($user);

        $chart_pool->addChartToQueue($chartId,$chartTitle, $chartDrillDetails, $chartDateDesc);

        $result['success'] = true;
        $result['action'] = 'add';

        return $app->json($result);
    }

    public function removeFromQueue(Request $request, Application $app)
    {
        $this->authorize($request, array('usr'));

        $result = array();

        $user = $this->getUserFromRequest($request);

        $chartId = $this->getStringParam($request, 'chart_id', true);

        $chartTitle = $this->getStringParam($request, 'chart_title', false, 'Untitled Chart');

        $chartId = str_replace("title=".$chartTitle, "title=".urlencode($chartTitle), $chartId);

        $chartPool = new XDChartPool($user);

        $chartPool->removeChartFromQueue($chartId);

        $result['success'] = true;
        $result['action'] = 'remove';

        return $app->json($result);
    }
}

