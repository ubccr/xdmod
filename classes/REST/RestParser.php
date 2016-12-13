<?php

  /*
   * @Class RestParser
   *
   * Used for parsing a URL and interpreting its respective components for the sake of proper routing
   * by RestHandler
   *
   */

class RestParser {
  
  private $_token = NULL;
  
  // ===========================
  
  /* parseRequest
   *
   * Parses the URL (or specified internal call) into multiple fragments, each
   * of which are accessed by the respective functions in the RestElements class
   * If $internalCall is specified in the call to parseRequest(), the value of
   * that variable will be used in lieu of $_SERVER['REQUEST_URI'].  (see
   * Rest::internalRequest() for this scenario)
   * 
   * @return an XDRestElements instance, which will contain the status,
   * category, request, and token
   *
   */

  public function parseRequest($internalCall = NULL) {

    if($internalCall !== null) {
      $rawRequest = $internalCall;
      $requestURI = "";
      $origRequest = "";
    }
    else {
      // Reduce contiguous blocks of forward slashes to one forward slash
      $requestURI = preg_replace('~\/{2,}~', '/', $_SERVER['REQUEST_URI']);
      $rawRequest = substr($requestURI, strlen(dirname($_SERVER['SCRIPT_NAME'])) + 1);
      $origRequest = $requestURI;
    }
    
    // Detach request portion of URL from the Argument portion  
    // E.g. /request/portion?argument=portion ==>  /request/portion, argument=portion

       
    $index = strpos($rawRequest, '?');
         
    $token = NULL;
      
    if ($index !== false) {
         
      list($rawRequest, $args) = explode('?', $rawRequest, 2);
            
      // At this point, we can attempt to extract a token, should one be specified in the argument portion
      $token = $this->_fetchArgument('token', $args);
      
    }

    if( $token === NULL && function_exists('getallheaders')  ) {
        // Check to see if the token was in the HTTP headers
        $headers = getallheaders();
        foreach($headers as $name => $value) {
            if( strtolower($name) == 'authorization' ) {
                $token = $value;
                break;
            }
        }
    }

    // Extract remaining elements from request ------------------------------------
         
    $structureFragments = explode('/', 'realm/category/action/action_args/output_format');
         
    $rawRequest .= '/';
         
    $requestComponents = array();
      
    foreach($structureFragments as $fragment) {
            
      if ($fragment == 'action_args')
      {
        $rawRequest = preg_replace('/\/+$/', '', $rawRequest);
        break;
      }
            
      $index = strpos($rawRequest, '/');
         
      if ($index === false)
      {
        $msg = $origRequest." -- ".$fragment." required";
        throw new Exception($msg);
      }

      list($requestComponents[$fragment], $rawRequest) = explode('/', $rawRequest, 2);

      //print "$fragment ==> {$requestComponents[$fragment]}<br><br>";

      if (empty($requestComponents[$fragment])) {
        $msg = $origRequest." -- ".$fragment." required";
        throw new Exception($msg);
      }

    }//foreach($structureFragments as $fragment)

    $actionArguments = $rawRequest;

    // Determine the output format -----------------------------------------------
         
    $trailingComponents = explode('/', $actionArguments);
         
    // Use default output format unless another valid format is specified at the end of the request
    $outputFormat = REST_DEFAULT_FORMAT;
                            
    $validFormats = \xd_rest\enumerateOutputFormats();
   
    // -----------------------------------------
         
    if ( in_array( end($trailingComponents), array_keys($validFormats) ) ){
            
      $outputFormat = array_pop($trailingComponents);
      $actionArguments = implode('/', $trailingComponents);
            
    }
   
    // ------------------

    $response = new RestElements();
         
    $response->setToken(($token != NULL) ? $token : '');	
    $response->setUrl($requestURI);
    if( isset($_SERVER['REMOTE_ADDR']) ) {
        $response->setIPAddress($_SERVER['REMOTE_ADDR']);
    }
    $response->setRealm($requestComponents['realm']);
    $response->setCategory($requestComponents['category']);
    $response->setAction($requestComponents['action']);
    $response->setActionArguments($actionArguments);
    $response->setOutputFormat($outputFormat);			            		
    $response->setStatus(REST_PARSE_OK);

    if( $internalCall !== null ) {
        // If this is an internal call, then the IP address is set to a special
        // value (INTERNAL_CALL). This is used by the XDSessionManager to skip the
        // over-the-network user authentication code.
        $response->setIPAddress('INTERNAL_CALL');
    }
      
    return $response;

  }  // parseRequest()

  // ===========================

  private function _fetchArgument($arg, $arg_string) {

    $args = explode('&', $arg_string);

    foreach($args as $current_arg) {

      $kv_pair = explode('=', $current_arg);

      $key = $kv_pair[0];

      if ($key == $arg) {

        if (count($kv_pair) == 2){
          if (empty($kv_pair[1])) {
            return NULL;
          }
          return $kv_pair[1];
        }
        else{
          return NULL;
        }

      }

    }  // foreach($args as $current_arg)

    return NULL;

  }  // _fetchArgument()

  }  // class RestParser

?>
