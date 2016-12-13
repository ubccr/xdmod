<?php

namespace NewRest\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class LegacyControllerProvider
 *
 * This class is responsible for redirecting old REST stack routes used
 * externally to the appropriate functionality in the new REST stack.
 */
class LegacyControllerProvider extends BaseControllerProvider
{
    /**
     * A mapping of legacy routes to options that determine how to use
     * their current, equivalent routes.
     *
     * @var array
     */
    private static $legacyRouteMapping = array(
        '/version/xdmodversion/current' => array(
            'route' => '/versions/current',
            'method' => 'GET',
        ),
    );

    /**
     * Convert a URL arguments string from the old REST stack
     * into an associative array.
     *
     * The arguments string must not be decoded for this to work properly.
     * This means the string cannot be passed in from Silex's route helper
     * functions, as they will automatically decode the string.
     *
     * Based on the old REST stack's URL parser.
     *
     * @param  string $urlArgumentsString A string of URL arguments, as defined
     *                                    by the old REST stack.
     * @return array                      A mapping of URL argument keys to
     *                                    their values.
     */
    private function parseUrlArguments($urlArgumentsString)
    {
        // Replace any blocks of slashes with a single slash.
        $urlArgumentsString = preg_replace('/\/{2,}/', '/', $urlArgumentsString);

        // Break up the string by key-value pairs.
        $urlArgumentPairs = explode('/', $urlArgumentsString);

        // Create an associative array from the pairs.
        $urlArguments = array();
        foreach ($urlArgumentPairs as $urlArgumentPair) {
            $urlArgumentPairComponents = explode('=', $urlArgumentPair, 2);

            if (count($urlArgumentPairComponents) < 2) {
                continue;
            }

            $urlArgumentPairComponents = array_map('urldecode', $urlArgumentPairComponents);
            $urlArguments[$urlArgumentPairComponents[0]] = $urlArgumentPairComponents[1];
        }

        // Return the associative array.
        return $urlArguments;
    }

    /**
     * @see BaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        foreach (self::$legacyRouteMapping as $legacyRoute => $legacyRouteOptions) {
            $controller->match($legacyRoute, '\NewRest\Controllers\LegacyControllerProvider::redirectLegacyRoute')
                ->value('legacyRoute', $legacyRoute)
                ->value('options', $legacyRouteOptions);

            $controller->match("$legacyRoute/{urlArguments}", '\NewRest\Controllers\LegacyControllerProvider::redirectLegacyRoute')
                ->assert('urlArguments', '.*')
                ->value('legacyRoute', $legacyRoute)
                ->value('options', $legacyRouteOptions);
        }
    }

    /**
     * Internally redirect a legacy route to its current equivalent.
     *
     * @param  Request     $request     The request used to make this call.
     * @param  Application $app         The router application.
     * @param  string      $legacyRoute The route that invoked this function.
     * @param  array       $options     A set of options for redirecting the call.
     * @return Response                 The response from the call this route
     *                                  was redirected to.
     */
    public function redirectLegacyRoute(Request $request, Application $app, $legacyRoute, $options)
    {
        // Extract the URL arguments from the URL.
        //
        // This cannot be passed in from the route definition,
        // as Silex will apply a different method of URL decoding than the
        // old REST stack did.
        list($routeMountPoint, $urlArgumentsAndParamsString) = explode($legacyRoute, $request->getRequestUri(), 2);
        list($urlArgumentsString, $urlParamsString) = explode('?', $urlArgumentsAndParamsString, 2);

        $urlArguments = $this->parseUrlArguments($urlArgumentsString);

        // Create a sub-request which points to the new route.
        $subrequestParams = new ParameterBag();
        $subrequestParams->add($request->query->all());
        $subrequestParams->add($request->request->all());
        $subrequestParams->add($urlArguments);

        $subrequest = Request::create(
            '/' . \xd_utilities\getConfiguration('rest', 'version') . $options['route'],
            $options['method'],
            $subrequestParams->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        // Launch the sub-request and return the response.
        return $app->handle($subrequest, HttpKernelInterface::SUB_REQUEST, false);
    }
}
