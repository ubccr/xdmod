<?php

namespace Rest;

use CCR\DB\PDODB;
use Configuration\XdmodConfiguration;
use Rest\Controllers\BaseControllerProvider;
use Rest\Utilities\Authentication;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates or retrieves a Silex application configured for the XDMoD REST API.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class XdmodApplicationFactory
{

    /**
     * A key which will be used to define our base url.
     *
     * @param string
     */
    const API_SYMBOL = 'api_version';

    /**
     * A regex that will be used to define what routes match our
     * pre-defined base url.
     *
     * @param string
     */
    const API_REGEX = '^v\d+(\.\d+)?$';

    /**
     * The Silex application instance for the XDMoD REST API.
     *
     * This is not created until getInstance() is called.
     *
     * @param \Silex\Application
     */
    private static $instance = null;

    private static $loggingBlacklist = array('password');

    /**
     * Create or retrieve a Silex application configured for the XDMoD REST API.
     *
     * @return \Silex\Application The XDMoD REST API handler.
     */
    public static function getInstance()
    {
        // If the application instance has already been created, return it.
        if (self::$instance !== null) {
            return self::$instance;
        }

        // CREATE: our new Silex Application
        $app = new Application();

        // SET: whether debug mode is on
        $app['debug'] = filter_var(\xd_utilities\getConfiguration('general', 'debug_mode'), FILTER_VALIDATE_BOOLEAN);

        // REGISTER: a URL Generator.
        $app->register(new UrlGeneratorServiceProvider());

        // SET: the regex that will be used to filter the API_SYMBOL in a route.
        //      in this case we're using it as our base url.
        $app['controllers']->assert(self::API_SYMBOL, self::API_REGEX);

        // Set the default value for the REST API version to a string
        // representing the latest version.
        $app['controllers']->value(self::API_SYMBOL, 'latest');

        $app['logger.db'] = $app->share(function () {
            return \CCR\Log::factory('rest.logger.db', array(
                'console' => false,
                'file' => false,
                'mail' => false,
                'dbLogLevel' => \CCR\Log::INFO
            ));
        });

        $app->before(function (Request $request, Application $app) {
            $request->attributes->set('timing.start', microtime(true));
            return $app;
        }, Application::EARLY_EVENT);

        // SETUP: a before middleware that detects / starts the query debug mode for a request.
        $app->before(function (Request $request, Application $app) {
            if ($request->query->getBoolean('debug')) {
                PDODB::debugOn();
            }
        });

        // SETUP: the authentication Middleware to be run before the route is.
        $app->before("\Rest\Controllers\BaseControllerProvider::authenticate", Application::EARLY_EVENT);

        $app->after(function (Request $request, Response $response, Application $app) {
            $logger = $app['logger.db'];

            $retval = array('message' => "Route called");

            $authInfo = Authentication::getAuthenticationInfo($request);
            if (!isset($authInfo['username']) && $request->attributes->has(BaseControllerProvider::_USER)) {
                $authInfo['username'] = $request->attributes->get(BaseControllerProvider::_USER)->getUsername();
            }
            $method = $request->getMethod();
            $host = $request->getHost();
            $port = $request->getPort();

            // Extracting any POST variables provided in the Request.
            $post = array();
            foreach($request->request->getIterator() as $key => $value) {
                if (!in_array($key, self::$loggingBlacklist)) {
                    $post[$key] = (
                    is_string($value)
                        ? json_decode($value, true)
                        : null
                    );
                }
            }

            // Calculate the amount of time that has elapsed serving this request.
            $start = $request->attributes->get('timing.start');
            $end = microtime(true);
            $elapsed = $end - $start;

            $referer = null;
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
            }

            // Begin constructing the value to be logged / "returned".
            $retval['path'] = $request->getPathInfo();
            $retval['query'] = $request->getQueryString();
            $retval['referer'] = $referer;
            $retval['elapsed'] = $elapsed;
            $retval['post'] = $post;
            $retval['data'] = array(
                'host' => $host,
                'port' => $port,
                'method' => $method,
                'username' => $authInfo['username'],
                'ip' => $authInfo['ip'],
                'token' => $authInfo['token'],
                'timestamp' => date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME'])
            );

            $logger->info($retval);

        }, Application::EARLY_EVENT);

        // SETUP: an after middleware that detects the query debug mode and, if true, retrieves
        //        and returns the collected sql queries / params.
        $app->after(function (Request $request, Response $response, Application $app) {
            $origin = $request->headers->get('Origin');
            if ($origin !== null) {
                try {
                    $corsDomains = \xd_utilities\getConfiguration('cors', 'domains');
                    if (!empty($corsDomains)){
                        $allowedCorsDomains = explode(',', $corsDomains);
                        if (in_array($origin, $allowedCorsDomains)) {
                            // If these headers change similar updates will need to be made to the `error` section below
                            $response->headers->set('Access-Control-Allow-Origin', $origin);
                            $response->headers->set('Access-Control-Allow-Headers', 'x-requested-with, content-type');
                            $response->headers->set('Access-Control-Allow-Credentials', 'true');
                            $response->headers->set('Vary', 'Origin');
                        }
                    }
                } catch (\Exception $e) {
                    // this catches if the section or config item does not exist
                    // in that case we just carry on
                }
            }
            if (PDODB::debugging()) {
                $debugInfo = PDODB::debugInfo();

                $contentType = $response->headers->get('content-type', null);
                if ('application/json' === strtolower($contentType)) {
                    $content = $response->getContent();
                    $jsonContent = json_decode($content);

                    if (is_array($jsonContent)) {
                        foreach ($jsonContent as $entry) {
                            if (is_object($entry)) {
                                $entry->debug = $debugInfo;
                                break;
                            }
                        }
                    } elseif (is_object($jsonContent)) {
                        $jsonContent->debug = $debugInfo;
                    }


                    $response->setContent(json_encode($jsonContent));
                }
            }
        });

        // MOUNT: our Controllers ( note: this calls the BaseControllerProvider::connect method )
        //        which calls each of the abstract methods in turn.
        $versionedPathMountPoint = "/{" . self::API_SYMBOL . "}";
        $unversionedPathMountPoint = '';

        // Retrieve the rest end point configuration
        $restControllers = XdmodConfiguration::assocArrayFactory('rest.json', CONFIG_DIR);

        foreach ($restControllers as $key => $config) {
            if (!array_key_exists('prefix', $config) || !array_key_exists('controller', $config)) {
                throw new \Exception("Required REST endpoint information (prefix or controller) missing for $key.");
            }

            $prefix = $config['prefix'];
            $ControllerClass = $config['controller'];
            $controller = new $ControllerClass(
                array(
                    'prefix' => $prefix
                )
            );

            $app->mount($versionedPathMountPoint, $controller);
            $app->mount($unversionedPathMountPoint, $controller);
        }

        // SETUP: error handler
        $app->error(function (\Exception $e, $code) use ($app) {
            if($code == 405 && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS' && array_key_exists('HTTP_ORIGIN', $_SERVER)){
                try {
                    $corsDomains = \xd_utilities\getConfiguration('cors', 'domains');
                } catch (\Exception $cors) {
                    $corsDomains = null;
                }
                if (!empty($corsDomains)){
                    $allowedCorsDomains = explode(',', $corsDomains);
                    $origin = $_SERVER['HTTP_ORIGIN'];
                    if (in_array($origin, $allowedCorsDomains)) {
                        // if these headers change we will need to update the `after` above
                        return new Response(
                            '',
                            204, /* in `$app->error` this value is ignored use header `X-Status-Code` to force a different status code */
                            [
                                'X-Status-Code' => 204,
                                'Vary' => 'Origin',
                                'Access-Control-Allow-Origin' => $origin,
                                'Access-Control-Allow-Headers' => 'x-requested-with, content-type',
                                'Access-Control-Allow-Credentials' => 'true'
                            ]
                        );
                    }
                }
            }
            $exceptionOutput = \handle_uncaught_exception($e);
            return new Response(
                $exceptionOutput['content'],
                $exceptionOutput['httpCode'],
                $exceptionOutput['headers']
            );
        });

        // Set the application instance as the global instance and return it.
        self::$instance = $app;
        return $app;
    }  // getInstance()
}
