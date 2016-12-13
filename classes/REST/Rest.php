<?php

   use CCR\DB;

   class Rest {
   
      // internalRequest allows REST calls to be made within PHP scripts on the backend.
      // @return array (the raw array data returned from an action)
      //
      // Usage (example):
      // $response = Rest::internalRequest('reporting/builder/getmetrics');
      //
      // If an exception is thrown with the message 'invalid token specified', an XDUser object
      // needs to be passed in as the second argument to Rest::internalRequest(...):
      //
      // When dealing with REST calls which do not require authentication, do not specify a third
      // argument ($user) to internalRequest(...)
      //
      // $response = Rest::internalRequest('reporting/builder/getmetrics', array(), $user);
      //
      // If arguments are to be supplied with the internal request, they can be passed in as follows:
      // 
      // $args = array('arg1' => 'arg1value', 'arg2' => 'arg2value');
      //
      // Rest::internalRequest('reporting/builder/create', $args, $user);
      
      public static function internalRequest($internalCall, $callArguments = array(), $user = NULL, $api_key = NULL) {
      
         try {
                        
            $parser = new RestParser();
            
            $request = $parser->parseRequest($internalCall);
            
            if ($user != NULL) {
            
               // Overwrite the token assigned as the result of calling $parser->parseRequest() above.  This new token, coupled with
               // an IP address of INTERNAL_CALL, will allow XDSessionManager to defer to XDUser for token-to-user resolution (using moddb.Users).
               $request->setToken($user->getToken());	
               
            }

            if ($api_key != NULL) {
            
               if (self::isValidAPIKey($api_key) == false) {
                  throw new Exception('The API key supplied is invalid');
               }
            
               $request->setAPIKey($api_key);	
               
            }
                        
            //xd_debug\dumpArray($request);
            
            $handler = RestHandler::factory($request);
                     
            $category_obj_ref = $handler->{$request->getCategory()}();
               
            $arguments = explode('/', $request->getActionArguments());
            
            if ($arguments[0] == 'help'){
               $category_obj_ref = $category_obj_ref->help();
            }

            // -------------------------------------------
                  
            // Process $callArguments (associative) array:
            
            $arguments = array();
            
            foreach ($callArguments as $argumentName => $argumentValue) {
               $arguments[] = $argumentName.'='.$argumentValue;
            }
            
            $argumentList = implode('/', $arguments);
            
            $request->setActionArguments($argumentList);
            
            // -------------------------------------------
            
            $response = $category_obj_ref->{$request->getAction()}();
               
            return $response;
            
         }
         catch (Exception $e) {
         
            throw new Exception($e->getMessage());
         
         }
      
      }//internalRequest
      
      // ==========================================================
      
      private static function isValidAPIKey($api_key) {
   
         $pdo = DB::factory('database');
      
         $results = $pdo->query("SELECT * FROM RESTx509 WHERE api_key=:api_key", array('api_key' => $api_key));
   
         return (count($results) > 0);
   
      }//isValidAPIKey
   
      // ==========================================================
         
      public static function getArgumentSchema($internalCall, $user = NULL) {
      
         try {
            
            $parser = new RestParser();
            
            $request = $parser->parseRequest($internalCall);
            
            if ($user != NULL) {
            
               // Overwrite the token assigned as the result of calling $parser->parseRequest() above.  This new token, coupled with
               // an IP address of INTERNAL_CALL will allow XDSessionManager to defer to XDUser for token-to-user resolution (using moddb.Users).
               $request->setToken($user->getToken());	
               
            }
            
            $handler = RestHandler::factory($request);
                     
            $category_obj_ref = $handler->{$request->getCategory()}();

            return $category_obj_ref = $category_obj_ref->argumentSchema()->{$request->getAction()}();
            
         }
         catch (Exception $e) {
         
            throw new Exception($e->getMessage());
         
         }
      
      }//getArgumentSchema
   
   }//Rest
   
?>
