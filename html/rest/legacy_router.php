<?php
    
// XDMoD RESTful Service Front Controller

require_once(dirname(__FILE__).'../../../configuration/linker.php');

// Pear::Log http://pear.php.net/package/Log
require_once("Log.php");


if (isset($_SERVER['HTTP_USER_AGENT'])
    && preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])
) {
    session_cache_limiter("private");
}
define('EXCEPTION_MESSAGE_SUFFIX', \xd_rest\getExceptionMessageSuffix());

// ----------------------------------------------------------------------


// Declare global variables so that these variables may be used with the global
// keyword when this file is required by another file and not invoked directly.
global $service_handler_start_time;
global $generalAccessLogger;
global $logged_request_uri;
global $rest_caller;
global $generalAccessLogger;
global $logger;

// Set up a file for logging exceptions that are propogated from rest calls
$logfile = LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'rest_logfile');
$logConf = array('mode' => 0644);
$logger = Log::factory('file', $logfile, 'REST', $logConf);

$general_logfile = LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'rest_general_logfile');

$generalAccessLogger = Log::factory('file', $general_logfile, 'REST', $logConf);


$response = array();
$parser = new RestParser();
$request = null;

// ============================================================================================

$service_handler_start_time = microtime(true);

$logged_request_uri = cleanURL($_SERVER['REQUEST_URI']);

try {
// Break REST query (URL) into its constituent components ..
      
    $request = $parser->parseRequest();
   
    $token = $request->getToken();
   
    if (!empty($token)) {
        // If a token has been specified...
      
        try {
            $user = XDSessionManager::resolveUserFromToken($request);
            $rest_caller  = 'token_mapped_to_';
            $rest_caller .= ($user->isXSEDEUser() == true) ? $user->getXSEDEUsername().'_xsede' : $user->getUsername();
        } catch (\Exception $e) {
            // All exceptions thrown on behalf of XDSessionManager::resolveUserFromToken(...) indicate that the
            // token supplied to the REST call was invalid.
         
            $rest_caller = 'invalid_token';
        }
    } else {
        // A token was never specified in the REST call to begin with
            
        $rest_caller = 'no_token';
    }
   
   //$generalAccessLogger->log($_SERVER['REMOTE_ADDR']." $rest_caller ".$logged_request_uri);
  
  /*
   print 'Realm: '.$request->getRealm()."<br>\n";
   print 'Category: '.$request->getCategory()."<br>\n";
   print 'Action: '.$request->getAction()."<br>\n";
   print 'Action Args: '.$request->getActionArguments()."<br>\n";
   print 'Output Format: '.$request->getOutputFormat()."<br>\n";
   print 'Token: '.$request->getToken()."<br>\n";   
  */
          
    try {
        $handler = RestHandler::factory($request);
         
        // Attempt to route the request to the proper class residing in the proper namespace
         
        $category_obj_ref = $handler->{$request->getCategory()}();
         
        $arguments = explode('/', $request->getActionArguments());
            
        if ($arguments[0] == 'help') {
            $category_obj_ref = $category_obj_ref->help();
        }

        // Any exceptions thrown by the action will propogate back up to the front controller,
        // and trip the catch { .. } block below.
         
        $response = $category_obj_ref->{$request->getAction()}();
    } catch (\Exception $e) {
        // REASONS FOR ENTERING THIS CATCH { ... } BLOCK:
        // (1) There was an issue finding the proper action (function) to invoke.
        // (2) The action itself, if found, threw an exception.
         
        // In this case, parsing of the request succeeded, so allow the response to
        // propogate to the logic below in order to present the information in the format
        // the caller requested (or use the default format if no recognizable format was
        // explicitly provided in the call).
    
        $logger->log(generateLogMsg($e), PEAR_LOG_ERR);

        // If a SessionExpiredException was thrown, allow the global exception
        // handler to process it. There should not be session-checking code in the
        // REST API, but the browser client depends on it currently.
        if ($e instanceof \SessionExpiredException) {
            logTimestamps('FAIL', 'session_expired_exception');
            throw $e;
        }

        // If a custom exception was throw, allow the global exception handler
        // to process it.
        if ($e instanceof \XDException) {
            throw $e;
        }

        $response['success'] = false;
        $response['message'] = $e->getMessage().EXCEPTION_MESSAGE_SUFFIX;
    }  // try
} catch (\Exception $e) {
   // If a custom exception was thrown, allow the global exception
   // handler to process it.
    if ($e instanceof \XDException) {
        throw $e;
    }

    logTimestamps('FAIL', 'parse_exception');
    
   // There was an issue parsing the request.  Since parsing failed, there is no
   // way to determine what return format the end-user wanted -- in this case,
   // the default format (JSON) is provided
      
    $response['success'] = false;
    $response['message'] = $e->getMessage().EXCEPTION_MESSAGE_SUFFIX;
   
    $msg = generateLogMsg($e, "Error parsing request");
           
    $logger->log($msg, PEAR_LOG_ERR);

    print json_encode($response);
    exit;
}  // try
    
// ----------------------------------------------------------------------
  
// At this point, the appropriate handler for the request has been reached, and
// it is now time to analyze and present the response ...
      
// Append the action name to the response (which can be used by the caller)
      
$response['action'] = $request->getAction();

try {
    $rest_response = RestResponse::factory($response);
     
    $response = $rest_response->{$request->getOutputFormat()}()->render();
   
    logTimestamps('SUCCESS');
   
    print $response;
} catch (\Exception $e) {
    logTimestamps('FAIL', 'response_render_exception');
  
    $logger->log(generateLogMsg($e, "Handler error"), PEAR_LOG_ERR);
   
   // If a SessionExpiredException was thrown, allow the global exception
   // handler to process it. There should not be session-checking code in the
   // REST API, but the browser client depends on it currently.
    if ($e instanceof \SessionExpiredException) {
        throw $e;
    }

    $response['success'] = false;
  
    $response['message'] = $e->getMessage().EXCEPTION_MESSAGE_SUFFIX;
  
  // It is important that this message be textual (a RAW format is not entirely
  // reliable in this case, so use JSON).
  
  // Rendering has failed nonetheless, so present the error in a 'reliable' format
  // (using json_encode)
   
    print json_encode($response);
}  // try

// --------------------------------------------------------------------------------
// Generate a log message based on the REST request, exception, and an optional
// message.
//
// @param $request A RestElements object containing the request information
// @param $e An Exception object
// @param $msg An optional message to log
//
// @returns A formatted log string with individual elements separated by
//  semi-colons.
// --------------------------------------------------------------------------------

function generateLogMsg(\Exception $e, $msg = null)
{
   
    $logMsg = array();
    $logMsg[] = $_SERVER['REMOTE_ADDR'];  //old: $request->getIPAddress();
    $logMsg[] = $_SERVER['REQUEST_URI'];  // old: cleanURL($request->getUrl());
   
    $t = $e->getTrace();
   
    if (is_array($t) && count($t) > 0) {
        $t = array_shift($t);
        if (array_key_exists('file', $t)) {
            $logMsg[] = $t['file'] . "::" . $t['function'] . "() (line " . $t['line'] . ")";
        }
    }
   
    $logMsg[] = ( null !== $msg ? $msg . ": " : "" ) . $e->getMessage();
   
    return implode("; ", $logMsg);
}//generateLogMsg()

// --------------------------------------------------------------------------------

// @function cleanURL

// @description  It is critical that any logged REST calls are not storing passwords in plain-text.  Should a user make the mistake of passing parameters in the URL
//               (as opposed to POSTing them), the following will process the request URI and render a suitable URI for logging purposes (removing any sensitive data
//               should it exist).
//
// @param $url  type: STRING (the URL to be processed)
//
// @returns a STRING representing the URL devoid of any sensitive information
//
// --------------------------------------------------------------------------------

function cleanURL($url)
{

    $uri_components = explode('/', $url);

    $uri_components_mod = array();
   
    foreach ($uri_components as $component) {
        $uri_components_mod[] = preg_replace('/password=(.+)/', 'password=...', $component);
    }//foreach

    return implode('/', $uri_components_mod);
}//cleanURL

// --------------------------------------------------------------------------------

// @function logTimestamps

// @description  records request status and execution timestamps to general log file
//
// @param $status  type: STRING (the status of the request)
// @param $reason  type: STRING [optional] (the reason as to why the request failed or succeeded)
//
//
// --------------------------------------------------------------------------------

function logTimestamps($status, $reason = '')
{

    global $service_handler_start_time;
    global $generalAccessLogger;
    global $logged_request_uri;
    global $rest_caller;
   
    $service_handler_end_time = microtime(true);
    $service_handler_turnaround = $service_handler_end_time - $service_handler_start_time;
   
    if (!empty($reason)) {
        $reason = " REASON=$reason";
    }
   
    $generalAccessLogger->log($_SERVER['REMOTE_ADDR']." REQUEST CALLER=$rest_caller ENDPOINT=$logged_request_uri STATUS=$status$reason START=$service_handler_start_time END=$service_handler_end_time DELTA=$service_handler_turnaround");
}//logTimestamps
