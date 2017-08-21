<?php

namespace NewRest\Controllers;

use NewRest\Utilities\Authentication;
use NewRest\Utilities\Authorization;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Class BaseControllerProvider
 *
 * An abstract class that provides some basic functionality and helper
 * methods for any class that wishes to be a 'ControllerProvider'. That
 * is to say, handle a portion of the routing for a Silex application.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
abstract class BaseControllerProvider implements ControllerProviderInterface
{

    const _USER = '_request_user';
    const _REQUIREMENTS = 'requirements';
    const _URL_GENERATOR = 'url_generator';

    const KEY_PREFIX = 'prefix';

    const EXCEPTION_MESSAGE = 'An error was encountered while attempting to process the requested authorization procedure. [ Not Authorized ]';

    protected $prefix;

    /**
     * BaseControllerProvider constructor.
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        if (isset($params[self::KEY_PREFIX])) {
            $this->prefix = $params[self::KEY_PREFIX];
        }
    }


    /**
     * This function is called when the ControllerProvider is 'mount'ed.
     * It is also the main entry point for a ControllerProvider and is
     * where the 'setupXXX' functions are called from. All of these methods
     * default to a no-op except for 'setupRoutes' which must be implemented
     * by all child classes. As this is what is at the heart of a
     * ControllerProviders' functionality.
     *
     * @param Application $app
     * @return mixed an instance of the controller collection for this application.
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        $this->setupDefaultValues($app, $controller);
        $this->setupConversions($app, $controller);
        $this->setupMiddleware($app, $controller);
        $this->setupAssertions($app, $controller);
        $this->setupRoutes($app, $controller);

        return $controller;
    } // connect

    /**
     * This function is responsible for the setting up of any routes that this
     * ControllerProvider is going to be managing. It *must* be overridden by
     * a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    abstract public function setupRoutes(Application $app, ControllerCollection $controller);

    /**
     * This function is responsible for setting any global default values that this
     * ControllerProvider may require or provide. It defaults to a no-op
     * function if not overridden by a child class.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupDefaultValues(Application $app, ControllerCollection $controller)
    {
        // NO-OP UNLESS OVERRIDDEN
    } // setupDefaultValues

    /**
     * This function is responsible for setting up any global conversions that may be
     * required by this ControllerProvider to function. A conversion
     * takes in a user provided value and emits a value of a different type.
     *
     * For example:
     * $app->get('/users/{id}', function($id) {
     *     // do something with int $id here....
     * })->convert('id', function($id) { return (int) $id; });
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupConversions(Application $app, ControllerCollection $controller)
    {
        // NO-OP UNLESS OVERRIDDEN
    } //setupConversions

    /**
     * This function is responsible for setting up any global middleware that is particular
     * to this ControllerProvider. Middleware can be thought of as functions that
     * execute either before, after, or weighted before or weighted after ( dependant
     * on how they are set up ). They can be used to provide such functionality as
     * logging, authentication or authorization. Middleware can also "short circuit" the
     * normal execution of a route by returning a 'Response' object. In this case, the
     * next Middleware will not be run nor will the route callback.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupMiddleware(Application $app, ControllerCollection $controller)
    {
        // NO-OP UNLESS OVERRIDDEN
    } // setupMiddleware

    /**
     * This function is responsible for setting up any global assertions that
     * this ControllerProvider will need during it's lifecycle. An assertion
     * allows for the use of regex expressions to restrict the matching of
     * specific route parameters.
     *
     * Example:
     *  $app->get('/blog/{id}', function ($id) {
     *    // ...
     *  })->assert('id', '\d+');
     *
     *  Here we see that the 'id' route parameter must be one or more digits
     *  ( 0-9 ). If the route does not conform to this regex then it does not
     *  match.
     *
     * @param Application $app
     * @param ControllerCollection $controller
     * @return null
     */
    public function setupAssertions(Application $app, ControllerCollection $controller)
    {
        // NO-OP UNLESS OVERRIDDEN
    } // setupAssertions


    /**
     * This function takes in a Request object and an array of required
     * parameter keys. It then determines if all of the parameters are
     * present in the provided Request object. If they are, an array
     * of the parameters and their values are returned. If they are not
     * all found then an Exception is raised.
     *
     * @param Request $request to be used in
     *                         retrieving the the required params.
     * @param array $requiredParams an array of strings that defines the
     *                         parameters that are required to continue.
     * @param boolean $strict if set to true, then it will throw an exception if not all of the provided params are found.
     *                        defaults to true.
     * @return array of all of the required params and their values as supplied
     *                         by the Request object.
     * @throws \Exception if not all of the $requiredParams were found.
     */
    protected function _parseRestArguments(Request $request, $requiredParams = array(), $strict = true, $alternative_source = null)
    {
        if (!isset($request)) return array();

        $found = 0;
        $results = array();
        $length = count($requiredParams);
        foreach ($requiredParams as $requiredParam) {

            $value = $request->get($requiredParam);

            if (!isset($value) && is_string($alternative_source)) {

                $source = $request->get($alternative_source);
                $needsDecoding = is_string($source);

                if ($needsDecoding) $source = json_decode($source, true);

                $found = isset($source[$requiredParam]);
                $value = $found ? $source[$requiredParam] : null;
            }

            $mod = is_string($requiredParam) && isset($value) ? 1 : 0;

            if ($mod > 0) {
                $results[$requiredParam] = $value;
            }
            $found += $mod;
        }

        if ($found !== $length && $strict) throw new MissingMandatoryParametersException('Not all parameters were supplied');

        return $results;
    }//_parseRestArguments

    /**
     * A simple piece of Middleware that ensures that the user making the current
     * request is both authenticated and authorized to do so.
     *
     * @param Request $request that will be used to identify and authorize
     *                         the current user.
     * @param Application $app that will be used to facilitate returning a
     *                         json response if information is found to be
     *                         missing.
     * @return \Symfony\Component\HttpFoundation\JsonResponse if and only if
     *                         the user is missing a token or an ip.
     *
     * @throws Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public static function authenticate(Request $request, Application $app)
    {
        // If the user has already been found, skip this search.
        if ($request->attributes->has(BaseControllerProvider::_USER)) {
            return;
        }

        $user = Authentication::authenticateUser($request);
        if ($user === null) {
            throw new UnauthorizedHttpException('xdmod', 'You must be logged in to access this endpoint.'); // 401 from framework
        } else {
            $request->attributes->set(BaseControllerProvider::_USER, $user);
        }
    }

    /**
     * Will attempt to authorize the provided users' roles against the
     * provided array of role requirements.
     *
     * If the user is not authorized, an exception will be thrown.
     * Otherwise, the function will simply return the authorized user.
     *
     * @param Request $request A request containing user information
     *                         that is to be considered for authorization.
     * @param array $requirements that a users' roles must satisfy to be
     *                            'authorized'. If not specified, then only
     *                            whether or not the user is logged in will
     *                            be checked.
     * @return \XDUser The user that was checked and is authorized according to
     *                the given parameters.
     *
     * @throws  Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *          Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function authorize(Request $request, array $requirements = array())
    {

        $user = $this->getUserFromRequest($request);

        // If role requirements were not given, then the only check to perform
        // is that the user is not a public user.
        $isPublicUser = $user->isPublicUser();
        if (empty($requirements) && $isPublicUser) {
            throw new UnauthorizedHttpException('xdmod', self::EXCEPTION_MESSAGE);
        }

        $authorized = $user->hasAcls($requirements);
        if ($authorized === false && !$isPublicUser) {
            throw new AccessDeniedHttpException(self::EXCEPTION_MESSAGE);
        } elseif ($authorized === false && $isPublicUser) {
            throw new UnauthorizedHttpException('xdmod', self::EXCEPTION_MESSAGE);
        }

        // Return the successfully-authorized user.
        return $user;
    }

    /**
     * Retrieve the XDMoD user from a request object.
     *
     * @param  Request $request The request to retrieve a user from.
     * @return \XDUser           The user who made the request.
     */
    protected function getUserFromRequest(Request $request)
    {
        return $request->attributes->get(BaseControllerProvider::_USER);
    }

    /**
     * Attempt to get a parameter value from a request and filter it.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory If true, an exception will be thrown if
     *                            the parameter is missing from the request.
     * @param  mixed $default The value to return if the parameter was not
     *                            specified and the parameter is not mandatory.
     * @param  int $filterId The ID of the filter to use. See filter_var.
     * @param  mixed $filterOptions The options to use with the filter.
     *                                The filter should be configured so that
     *                                it returns null if conversion is not
     *                                successful. See filter_var.
     * @param  string $expectedValueType The expected type for the value.
     *                                    This is used purely for errors thrown
     *                                    when the parameter value is invalid.
     * @return mixed              If available and valid, the parameter value.
     *                            Otherwise, if it is missing and not mandatory,
     *                            the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value is not valid
     *                                 according to the given filter.
     */
    private function getParam(Request $request, $name, $mandatory, $default, $filterId, $filterOptions, $expectedValueType)
    {
        // Attempt to extract the parameter value from the request.
        $value = $request->get($name, null);

        // If the parameter was not present, throw an exception if it was
        // mandatory and return the default if it was not.
        if ($value === null) {
            if ($mandatory) {
                throw new BadRequestHttpException("$name is a required parameter.");
            } else {
                return $default;
            }
        }

        // Run the found parameter value through the given filter.
        $value = filter_var($value, $filterId, $filterOptions);

        // If the value is invalid, throw an exception.
        if ($value === null) {
            throw new BadRequestHttpException("Invalid value for $name. Must be a(n) $expectedValueType.");
        }

        // Return the filtered value.
        return $value;
    }

    /**
     * Attempt to get an integer parameter value from a request.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param  mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as an integer. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to an integer.
     */
    protected function getIntParam(Request $request, $name, $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_VALIDATE_INT,
            array(
                "options" => array(
                    "default" => null,
                ),
            ),
            "integer"
        );
    }

    /**
     * Attempt to get a float parameter value from a request.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param  mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a float. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a float.
     */
    protected function getFloatParam(Request $request, $name, $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_VALIDATE_FLOAT,
            array(
                "options" => array(
                    "default" => null,
                ),
            ),
            "float"
        );
    }

    /**
     * Attempt to get a string parameter value from a request.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param  mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a string. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory.
     */
    protected function getStringParam(Request $request, $name, $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_DEFAULT,
            array(),
            "string"
        );
    }

    /**
     * Attempt to get a boolean parameter value from a request.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param  mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a boolean. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a boolean.
     */
    protected function getBooleanParam(Request $request, $name, $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            array(
                "options" => function ($value) {
                    // Run the found parameter value through a boolean filter.
                    $filteredValue = filter_var(
                        $value,
                        FILTER_VALIDATE_BOOLEAN,
                        array(
                            "flags" => FILTER_NULL_ON_FAILURE,
                        )
                    );

                    // If the filter converted the string, return the boolean.
                    if ($filteredValue !== null) {
                        return $filteredValue;
                    }

                    // Check the value against 'y' for true and 'n' for false.
                    $lowercaseValue = strtolower($value);
                    if ($lowercaseValue === 'y') {
                        return true;
                    }
                    if ($lowercaseValue === 'n') {
                        return false;
                    }

                    // Return null if all conversion attempts failed.
                    return null;
                },
            ),
            "boolean"
        );
    }

    /**
     * Attempt to get a date parameter value from a request where it is
     * submitted as a Unix timestamp.
     *
     * @param  Request $request The request to extract the parameter from.
     * @param  string $name The name of the parameter.
     * @param  boolean $mandatory (Optional) If true, an exception will be
     *                            thrown if the parameter is missing from the
     *                            request. (Defaults to false.)
     * @param  mixed $default (Optional) The value to return if the
     *                            parameter was not specified and the parameter
     *                            is not mandatory. (Defaults to null.)
     * @return mixed              If available and valid, the parameter value
     *                            as a DateTime. Otherwise, if it is missing
     *                            and not mandatory, the given default.
     *
     * @throws BadRequestHttpException If the parameter was not available
     *                                 and the parameter was deemed mandatory,
     *                                 or if the parameter value could not be
     *                                 converted to a DateTime.
     */
    protected function getDateTimeFromUnixParam(Request $request, $name, $mandatory = false, $default = null)
    {
        return $this->getParam(
            $request,
            $name,
            $mandatory,
            $default,
            FILTER_CALLBACK,
            array(
                "options" => function ($value) {
                    $value_dt = \DateTime::createFromFormat('U', $value);
                    if ($value_dt === false) {
                        return null;
                    }
                    return $value_dt;
                },
            ),
            "Unix timestamp"
        );
    }

    /**
     * Get the best match for the acceptable content type for the request, given a
     * list of supported content types.
     *
     * @param Request $request The request from which to extract the data
     * @param array $supportedTypes A list of supported MIME types.
     * @param string $paramname (Optional) A parameter that will also be
     *                          checked for the accept type, in addition to the Accept header
     *                          contents. This parameter is checked first.
     * @return mixed the best matching entry from the $supportedTypes list or null if no supported types
     *               were allowable.
     */
    protected function getAcceptContentType(Request $request, $supportedTypes, $paramname = null)
    {
        $acceptTypes = $request->getAcceptableContentTypes();

        if ($paramname !== null) {
            $acceptType = $this->getStringParam($request, $paramname);
            if ($acceptType !== null) {
                array_unshift($acceptTypes, $acceptType);
            }
        }

        $selectedType = null;

        foreach ($acceptTypes as $type) {
            if (in_array($type, $supportedTypes)) {
                $selectedType = $type;
                break;
            }
        }

        return $selectedType;
    }

    /**
     * Helper function that creates a Response object that will result in
     * a file download on the client.
     *
     * @param $content The content of the file that will be sent
     * @param $filename The name of the file to send
     * @param $mimetype (Optional) The mimetype to set for the file. If omitted
     *                  then the mime type will be guessed using the finfo() fn.
     */
    protected function sendAttachment($content, $filename, $mimetype = null)
    {
        if ($mimetype === null) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->buffer($content);
        }

        $response = new Response(
            $content,
            Response::HTTP_OK,
            array('Content-Type' => $mimetype)
        );
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            )
        );

        return $response;
    }

    /**
     * Retrieve the 'id' property from the supplied array of values. The 'id'
     * property is defined by the provided 'selector'. If the 'id' does not
     * exist than a default can be supplied, otherwise null will be returned.
     *
     * @param array $values
     * @param string $selector
     * @param null $default
     * @return null
     */
    protected function _getId(Array $values, $selector = 'dtype', $default = null)
    {
        if (!isset($values) || !isset($selector) || !is_string($selector)) return null;

        $idSelector = isset($values[$selector]) ? $values[$selector] : null;

        return isset($idSelector) && isset($values[$idSelector]) ? $values[$idSelector] : $default;
    }

    /** ------------------------------------------------------------------------------------------
     * Format a data structure suitable for logging. The logger will convert an array into a JSON
     * blob for storage in the database.
     *
     * @param string                                    $message A general message
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param boolean                                   $includeParams if set to
     * TRUE include the GET and POST parameters in the log message.
     *
     * @return array An associative array containing the message, request path, and a block of
     *  supplemental data including host, port, method, ip address, get & post parameters, etc.
     *
     * array('message' => <string>,
     *       'path'    => <URI request path>
     *       'data'    => array(...)
     *       );
     *
     * Note: We need to define a standard log message with optional additional information. To
     * facilitate parsing/display, I suggest that all log entries have:
     *   message - human readable message
     *   internal - optional internal-only message describing the error
     *   path - the rest path or file/method that the exception was thrown
     *   data - an associative array of optional data specific to the section
     *
     * ------------------------------------------------------------------------------------------
     */

    public function formatLogMesssage($message, Request $request, $includeParams = FALSE)
    {
        $retval = array('message' => $message);

        $authInfo = Authentication::getAuthenticationInfo($request);
        $method = $request->getMethod();
        $host = $request->getHost();
        $port = $request->getPort();
        $retval['path'] = $request->getPathInfo();

        $retval['data'] = array(
            'host' => $host,
            'port' => $port,
            'method' => $method,
            'username' => $authInfo['username'],
            'ip' => $authInfo['ip'],
            'token' => $authInfo['token'],
            'timestamp' => date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME'])
        );

        if ($includeParams) {
            $retval['data']['get'] = $request->query->all();
            $retval['data']['post'] = $request->request->all();
        }

        return $retval;

    }  // formatLogMessage()

}
