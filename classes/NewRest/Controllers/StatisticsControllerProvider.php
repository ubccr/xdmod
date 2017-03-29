<?php namespace NewRest\Controllers;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

use \Statistics;
use \Statistic;

class StatisticsControllerProvider extends BaseControllerProvider
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
        $prefix = $this->prefix;
        $class = get_class($this);

        $controller
            ->get("$prefix", "$class::getStatistics");
        $controller
            ->get("$prefix/permitted", "$class::getPermittedStatistics");
    }

    public function getStatistics(Request $request, Application $app)
    {
        $statistics = Statistics::listStatistics();
        $success = isset($statistics) && count($statistics) > 0;

        return $app->json(array(
            'success' => $success,
            'data' => $statistics
        ));
    }

    public function getPermittedStatistics(Request $request, Application $app)
    {
        $user = $this->getUserFromRequest($request);
        $realmName = $this->getStringParam($request, 'realm', true);
        $groupByName = $this->getStringParam($request, 'group_by', true);
        $old = $this->getBooleanParam($request, 'old', false, false);


        $statistics = $old == false
            ? Statistics::listPermittedStatistics($user, $realmName, $groupByName)
            : $userStatistics = $user->getMostPrivilegedRole()->getQueryDescripters(
                'tg_usage',
                $realmName,
                $groupByName
            )->getPermittedStatistics();
        $success = isset($statistics) && count($statistics) > 0;
        return $app->json(array(
            'success' => $success,
            'data' => $statistics
        ));
    }
}
