<?php

   // XDMoD RESTful Service Front Controller 
   // (accessible only via 2-way Public Key Infrastructure)

   require_once dirname(__FILE__).'/../../configuration/linker.php';

   use CCR\DB;

   define('EXCEPTION_MESSAGE_SUFFIX', \xd_rest\getExceptionMessageSuffix());

   // -----------------------------------------------------------
   
   // Set up files for logging requests and exceptions that are propogated from rest calls

   $logConf = array('mode' => 0644);

   $access_logfile = LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'extrest_access_logfile');

   $access_logger = Log::factory('file', $access_logfile, 'REST', $logConf);
   
   // ------------
   
   $exception_logfile = LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'extrest_exception_logfile');
                   
   $exception_logger = Log::factory('file', $exception_logfile, 'REST', $logConf);

   // -----------------------------------------------------------

   // Clean up request URI  (e.g.  /extrest/realm/category/action --> realm/category/action)
      
   $request_uri = preg_replace('/^\/extrest\//', '', $_SERVER['REQUEST_URI']);
      

   // Acquire distinguished names from both subject (client / requestor) and the issuer (server)
   // Prohibit access if there is a problem retrieving either DN
   
   $subject_dn = isset($_SERVER['SSL_CLIENT_S_DN']) ? $_SERVER['SSL_CLIENT_S_DN'] : '';
   $issuer_dn = isset($_SERVER['SSL_CLIENT_I_DN']) ? $_SERVER['SSL_CLIENT_I_DN'] : '';
   
   if (empty($subject_dn) || empty($issuer_dn)) {
   
      $exception_logger->log($_SERVER['REMOTE_ADDR'].' -- invalid_handshake -- '.$request_uri);
      
      print "Invalid handshake";
      exit;
      
   }//if (empty($subject_dn) || empty($issuer_dn))
   
   // -----------------------------------------------------------
   
   $cert_details = array();
   
   try {
      
      // If the subject DN is not recognized by us, an exception will be thrown...
      
      $cert_details = resolveDistinguishedName($subject_dn);

      $access_logger->log($_SERVER['REMOTE_ADDR']." -- {$cert_details['description']} -- ".$request_uri);


      // Parse the URI here so key-value pairs can be appended to the REST response
      
      $parser = new RestParser();
      $request = $parser->parseRequest($request_uri);
      
            
      // Make an internal REST call, passing in the API key
       
      $response = Rest::internalRequest($request_uri, array(), NULL, $cert_details['api_key']);
 
      $response['action'] = $request->getAction();
      
      
      // The internal request, if successful, will return an array.  At this point, it is the
      // responsibility of this front controller to render a response into the appropriate 
      // output format.
               
      $rest_response = RestResponse::factory($response);
        
      $response = $rest_response->{$request->getOutputFormat()}()->render();
      
      print $response;
      
   }
   catch(Exception $e) {
   
      $response['success'] = false;
      $response['message'] = $e->getMessage().EXCEPTION_MESSAGE_SUFFIX;
 
      if (isset($cert_details['description'])) {
         $exception_logger->log($_SERVER['REMOTE_ADDR']." -- {$cert_details['description']} -- {$e->getMessage()} -- ".$request_uri);
      }
      else {
         $exception_logger->log($_SERVER['REMOTE_ADDR']." -- {$e->getMessage()} -- ".$request_uri);
      }
      
      // It is important that this message be textual (a RAW format is not entirely
      // reliable in this case, so use JSON).
     
      // Rendering has failed nonetheless, so present the error in a 'reliable' format
      // (using json_encode)
         
      print json_encode($response);
      
   }//try/catch
   
   // -----------------------------------------------------------
   //
   // @function resolveDistinguishedName
   // @description Provided a DN, retrieve additional information from the database (provided the DN was registered with us)
   // 
   // @param $dn  type: STRING  (The distinguished name to be looked up)
   //
   // @returns an associative array containing the description and api key mapped to the DN
   // @throws an exception if the DN supplied cannot be mapped
   // 
   // -----------------------------------------------------------
            
   function resolveDistinguishedName($dn) {
   
      $pdo = DB::factory('database');
      
      $results = $pdo->query("SELECT description, api_key FROM RESTx509 WHERE distinguished_name=:dn", array('dn' => $dn));
   
      if (count($results) == 0) {
         throw new Exception('DN not registered');
      }
      
      return $results[0];
   
   }//resolveDistinguishedName
   
?>
