<?php
/**
 * HTTP response functions.
 */

namespace xd_response;

use Exception;

use CCR\DB\PDODB;
use xd_controller;
use xd_utilities;

/**
 * Build response data from an error message or exception.
 *
 * @param  string|Exception $error The message/exception to build a response for.
 * @return array                   An associative array containing properties
 *                                 the code might expect in failure cases and,
 *                                 if enabled, properties with debug info.
 */
function buildError($error)
{
    // Default values that may be expected by EXT JS.
    $response = array(
        'success'    => false,
        'count'      => 0,
        'total'      => 0,
        'totalCount' => 0,
        'results'    => array(),
        'data'       => array(),
    );

    if ($error instanceof Exception) {
        $response['message'] = $error->getMessage();
        $response['code']    = $error->getCode();

        if ($error instanceof \XDException) {
            if (!empty($error->errorData)) {
                $response['errorData'] = $error->errorData;
            }
        }

        if (xd_utilities\getConfiguration('general', 'debug_mode') == 'on') {
            $response['stacktrace'] = $error->getTrace();
        }
    } else {
        $response['message'] = $error;
    }

    // If SQL debugging is enabled, include query data.
    if (PDODB::debugging()) {
        $response['sql'] = PDODB::debugInfo();
    }

    return $response;
}

/**
 * The argument passed in can either be a string (message) or an
 * Exception If an Exception is passed in, the respective message and
 * code are output as JSON.
 *
 * @param string|Exception $error
 */
function presentError($error)
{
    xd_controller\returnJSON(buildError($error));
}

/**
 * Sets response headers appropriate for dynamically-generated JavaScript.
 *
 * @param  boolean $allow_caching Allow the generated JavaScript to be cached.
 *                                (Defaults to false.)
 */
function useDynamicJavascriptHeaders($allow_caching = false) {
    // Set the content type of the response to JavaScript.
    header('Content-Type: application/javascript');

    // If desired, prevent this response from being cached.
    // See Example #2: http://php.net/manual/en/function.header.php
    // See: http://stackoverflow.com/a/13640164
    if (!$allow_caching) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Pragma: no-cache");
    }
}
