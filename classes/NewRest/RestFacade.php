<?php

namespace NewRest;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use NewRest\XdmodApplicationFactory;
use NewRest\Controllers\BaseControllerProvider;

/**
 * Provides an interface for interacting with the REST API from outside of it.
 */
class RestFacade
{

    /**
     * Launch a sub-request with the given options.
     *
     * @param  array $options A set of options for launching a request, including:
     *         * string route The route being requested.
     *         * string method The request method to use (GET, POST, etc.).
     *         * XDUser user The user making the request.
     *         * array params (Optional) The parameters for the request.
     *         * string content (Optional) The body content of the request.
     *         * array cookies (Optional) Cookies for the request.
     *                         (Defaults to $_COOKIE.)
     *         * array files (Optional) Files for the request.
     *                       (Defaults to $_FILES.)
     *         * array server (Optional) Server parameters for the request.
     *                        (Defaults to $_SERVER.)
     *         * boolean catch (Optional) Controls whether exceptions are
     *                         caught or not. (Defaults to false.)
     *         * boolean returnResponse (Optional) Controls whether the response
     *                                  object or its contents are returned.
     *                                  (Defaults to false.)
     *         * boolean decodeResponse (Optional) Controls whether the contents
     *                                  of a response are decoded before being
     *                                  returned. This is only effective if
     *                                  contents are being returned and not the
     *                                  Response object. This can either use
     *                                  the originalContent property of a Response,
     *                                  if set, or decode response content of
     *                                  supported formats (currently: JSON).
     *                                  (Defaults to true.)
     * @return mixed The content of the response to the handled request or the
     *               raw request object, depending on returnResponse.
     * @throws \Exception Required parameters are missing or parameters are invalid.
     */
    public static function launchRequest(array $options)
    {
        // Check for mandatory parameters in the options.
        if (!array_key_exists('route', $options)) {
            throw new \Exception('Must provide a route for an internal REST request.');
        }
        if (!array_key_exists('method', $options)) {
            throw new \Exception('Must provide a request method to make an internal REST request.');
        }
        if (!array_key_exists('user', $options) || !($options['user'] instanceof \XDUser)) {
            throw new \Exception('Must provide a user to launch an internal REST request.');
        }

        // Get optional parameters from the options.
        $params = array_key_exists('params', $options) ? $options['params'] : array();
        $content = array_key_exists('content', $options) ? $options['content'] : null;
        $cookies = array_key_exists('cookies', $options) ? $options['cookies'] : $_COOKIE;
        $files = array_key_exists('files', $options) ? $options['files'] : $_FILES;
        $server = array_key_exists('server', $options) ? $options['server'] : $_SERVER;
        $catch = array_key_exists('catch', $options) ? $options['catch'] : false;
        $returnResponse = array_key_exists('returnResponse', $options) ? $options['returnResponse'] : false;
        $decodeResponse = array_key_exists('decodeResponse', $options) ? $options['decodeResponse'] : true;

        // Get the instance of the REST API application.
        $app = XdmodApplicationFactory::getInstance();

        // Create a request object using the given options.
        $route = $options['route'];
        if (!\xd_utilities\string_begins_with($route, '/')) {
            $route = "/$route";
        }
        $request = Request::create(
            $route,
            $options['method'],
            $params,
            $cookies,
            $files,
            $server,
            $content
        );
        $request->attributes->set(BaseControllerProvider::_USER, $options['user']);

        // Determine the type of request by checking if an existing request
        // is accessible. If it is, the type of request to launch is a sub-request.
        // Otherwise, a master request needs to be launched.
        $request_level = HttpKernelInterface::MASTER_REQUEST;
        try {
            $existing_request = $app['request'];
            $request_level = HttpKernelInterface::SUB_REQUEST;
        } catch (\Exception $e) {
        }

        // Launch the request.
        $response = $app->handle($request, $request_level, $catch);

        // If the response object was requested, return it.
        if ($returnResponse) {
            return $response;
        }

        // Retrieve the encoded content from the response object.
        $encodedContent = $response->getContent();

        // If decoding was not requested, simply return the encoded contents.
        if (!$decodeResponse) {
            return $encodedContent;
        }

        // Get and return the decoded content of the response.
        // Use the encoded content as the return value, if all else fails.
        $decodedContent = $encodedContent;

        // If the original content is provided in the response, use that as
        // the decoded content to return. Otherwise, attempt to decode the
        // response contents.
        if (property_exists($response, 'originalContent')) {
            $decodedContent = $response->originalContent;
        } else {
            $contentType = $response->headers->get('Content-Type');
            if ($contentType === 'application/json') {
                $decodedContent = json_decode($encodedContent);
            }
        }

        return $decodedContent;
    }
}
