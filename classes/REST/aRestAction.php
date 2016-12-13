<?php

// ================================================================================
// Abstract class for defining REST operations and actions.
//
// An action is designed to be as flexible and easy as possible for a developer
// to implement and this class defines the basic operations that are supported.
// Additional machinery can be inserted to support authentication and/or
// tracking without requiring changes to the application level code.  For any
// given action, a number of operations is defined to provide functionality such
// as executing the action or providing help on the action.
//
// An operation is the operation to perform on an action target such as
// executing the target or providing help.  Operations are inserted between the
// action and target in the object method call list (e.g.,
// $handler->action()->operation()->target()).  If the operation is omitted then
// "execute" is assumed (e.g., $handler->query()->resources() is the same as
// $handler->query()->execute()->resources()).  This determines the method that
// will be called in the implementation of the action.  Additional operations
// can easily be added to this class and will be immeditately available to any
// extending classes.  The operation methods return the object so that the
// desired method can be called on it.
//
// To implement an action (such as "query"), the developer extends this class,
// implements the abstract methods, and implements one or more private methods
// that provide the desired functionality, resourcesAction() or resourcesHelp()
// for example.  The __call() method intercepts a call to the desired action and
// based on the specified operation ("execute" by default) attempts to call a
// method named for the action with the appropriate operation appended.  For
// example, calling $handler->query()->resources() would result in
// Query::resourcesAction() being called and
// $handler->query()->help()->resources() would result in Query::resourcesHelp()
// being called.
//
// If the help() function for an action is not defined (e.g. queryHelp()) when help
// is requested, that action's documentation() method will then be consulted for
// a response (e.g. queryDocumentation()).  Should the action's documentation() method
// not be defined in this case, the service will return a message, indicating that
// help is not available for that particular action.

// The target methods defined in the extending class should be private so
// that they cannot be called directly should additional instrumentation be
// added within the call chain.
//
// NOTE: It is assumed that the class implementing the action handler is
// defined in a namespace that same as the category, e.g., "namespace Inca".
// ================================================================================

abstract class aRestAction
{

   // Define the available actions

   const OP_HELP = 'Help';                    // Return help information
   const OP_EXECUTE = 'Action';               // Perform an action (default)
   const OP_DOCUMENTATION = 'Documentation';  // Return an instance of RestDocumentation (so the catalog can format the documentation properly)
   const OP_ARGS = 'ArgumentSchema';          // Return an instance of RestArguments (pertains primarily to the REST Call Builder)

   // Set the type of operation to perform

   protected $_operation = self::OP_EXECUTE;
   protected $_request = NULL;
   protected $_argument_schema = array();

   // --------------------------------------------------------------------------------

   protected function __construct($request) {
      $this->_request = $request;
   }

   // --------------------------------------------------------------------------------
   // _authenticateUser:
   //
   // @returns a reference to an XDUser upon successful authentication via token analysis
   //          (and optional role analysis).
   //
   // If the action which calls _authenticatedUser requires that a user have a specific role,
   // those roles can be passed into _authenticateUser.  The value for this argument can
   // be either a single constant (defined in 'configuration/constants.php' in the 'ROLES' section)
   // e.g. ROLE_ID_MANAGER, ROLE_ID_USER, etc..
   // or can be an array of these constants if any collection of roles will suffice for a particular
   // action.
   //
   // If the user cannot be resolved, an Exception is thrown

   // --------------------------------------------------------------------------------

   protected function _authenticateUser($roles = NULL) {

      $user = \XDSessionManager::resolveUserFromToken($this->_request);

      if ($roles != NULL) {

         $roles = (is_array($roles)) ? $roles : array($roles);

         $currentRoles = $user->getRoles();

         foreach ($roles as $role) {
            if (in_array($role, $currentRoles)) {
               return $user;
            }
         }

         throw new \Exception('Based on the role you have been assigned, access to this action is prohibited.', 401);
      }

      return $user;

   }//_authenticateUser

   // --------------------------------------------------------------------------------
   // _requireKeyOrToken:
   //
   // @returns an array containing data indicative of how the caller was able to satisfy the "key or token" requirement
   //
   // If neither an api key or (valid) token is supplied, an Exception is thrown

   // --------------------------------------------------------------------------------

   protected function _requireKeyOrToken() {

      $authState = array(
         'api_key' => null,                         // If the caller supplied a valid key, this will be populated with the value of the api key
         'token_resolved_user' => null              // If the caller supplied a valid token in lieu of a key, this will hold a reference to the XDUser mapped to the token
      );

      $api_key = $this->_request->getAPIKey();

      if (!empty($api_key)) {

         // Attempt to use an API key. if it has been supplied, we already know it's valid (the front controller took
         // care of validation for us).

         $authState['api_key'] = $api_key;

      }
      else {

         // If an API key has not been specified, the second probable situation is that the call invoking this function is being
         // made via the portal, and the caller is logged in.

         try {

            $user = $this->_authenticateUser();

            $authState['token_resolved_user'] = $user;

         }
         catch(\Exception $e) {

            throw new \Exception("The action you are trying to use requires either an api key, or you must be logged into the XDMoD portal");

         }

      }//catch

      return $authState;

   }//_requireKeyOrToken

   // --------------------------------------------------------------------------------
   // _parseRestArguments:
   //
   // @returns an associative array with a mapping of argument names (required
   // by an action) and values as determined from a portion of the REST call.
   //
   // For actions which require arguments 'arg1' and 'arg2' and no optional arguments:
   // _parseRestArguments('arg1/arg2');
   //
   // For actions which can take optional arguments 'opt1' and 'opt2' and no required arguments:
   // _parseRestArguments('', 'opt1/opt2');
   //
   // For actions which require arguments 'arg1', 'arg2', and can take optional arguments 'opt1' and 'opt2':
   // _parseRestArguments('arg1/arg2', 'opt1/opt2');
   //
   // NOTE that for optional arguments, the action implementation will have to make the appropriate checks
   // before working with those arguments (e.g. if (isset($params['opt1'])){ ... })

   // --------------------------------------------------------------------------------

   protected function _parseRestArguments($requiredFormat = '') {


      $requiredArguments = ($requiredFormat == '') ? array() : explode('/', $requiredFormat);

      $suppliedArguments = ($this->_request->getActionArguments() == '') ? array() : explode('/', $this->_request->getActionArguments());

      $suppliedArgsKV = array();

      foreach ($suppliedArguments as $arg) {

         if (strpos($arg, '=') !== false) {

            list($argument_name, $argument_value) = explode('=', $arg, 2);

            $suppliedArgsKV[$argument_name] = $argument_value;

         }

      }//foreach

      // ------------------------

      // Also account for key-value pairs supplied via GET and/or POST

      // $unioned_array = $array_a + $array_b;    ($array_a's contents are of higher priority wrt those of $array_b)

      $allSuppliedArgs = $_REQUEST + $suppliedArgsKV;

      // ------------------------

      $suppliedArguments = array();

      foreach ($allSuppliedArgs as $param => $value) {
         $suppliedArguments[] = $param.'='.$value;
      }

      // ------------------------

      $params = array();

      foreach ($suppliedArguments as $arg) {

         // Arguments must be of the form: argument_name=argument_value

         if (strpos($arg, '=') !== false) {

            list($argument_name, $argument_value) = explode('=', $arg, 2);

            if (!empty($argument_name)) {

               $index = array_search($argument_name, $requiredArguments);

               $params[$argument_name] = $argument_value;

               if ($index !== false) {
                  // The argument just consulted is a required argument, so remove it from the array
                  unset($requiredArguments[$index]);
               }

            }//if (!empty($argument_name) && !empty($argument_value))

         }//if (strpos($arg, '=') !== false)

      }//foreach ($suppliedArguments)

      // If all required arguments have been supplied, then count($requiredArguments) will be 0
      // Otherwise, all required arguments have not been supplied

      if (count($requiredArguments) > 0){
         throw new \Exception("Expecting arguments: '".implode(', ', $requiredArguments)."'");
      }

      return $params;

   }//_parseRestArguments

   // --------------------------------------------------------------------------------
   // _getRawFormat:
   //
   // @Determine the raw format for rendering the response.  The last argument is consulted for a
   // user-specified format.  If the last argument is not recognizable as a valid RAW format, the
   // default RAW format will be used.

   // --------------------------------------------------------------------------------

   protected function _getRawFormat() {

      $requested_format = $this->_request->getOutputFormat();

      $acceptable_formats = \xd_rest\enumerateRAWFormats();

      $raw_format = (in_array($requested_format, array_keys($acceptable_formats))) ? $requested_format : REST_DEFAULT_RAW_FORMAT;

      $obj_format = strtoupper($raw_format).'Format';

      return new $obj_format();

   }//getRawFormat

   // --------------------------------------------------------------------------------
   // Set the catalog operation
   //
   // @returns a copy of this object so that other actions can be performed on
   //   it.
   // --------------------------------------------------------------------------------

   public function catalog()
   {
      $this->_operation = self::OP_DOCUMENTATION;
      return $this;
   }

   // --------------------------------------------------------------------------------
   // Set the help operation
   //
   // @returns a copy of this object so that other actions can be performed on
   //   it.
   // --------------------------------------------------------------------------------

   public function help()
   {
      $this->_operation = self::OP_HELP;
      return $this;
   }

   // --------------------------------------------------------------------------------
   // Set the argument schema operation
   //
   // @returns a copy of this object so that other actions can be performed on
   //   it.
   // --------------------------------------------------------------------------------

   public function argumentSchema()
   {
      $this->_operation = self::OP_ARGS;
      return $this;
   }

   // --------------------------------------------------------------------------------
   // Set the action operation
   //
   // @returns a copy of this object so that other actions can be performed on
   //   it.
   // --------------------------------------------------------------------------------

   public function execute()
   {
      $this->_operation = self::OP_EXECUTE;
      return $this;
   }

   // --------------------------------------------------------------------------------
   // Factory design pattern for instantiating objects.
   //
   // @returns An instantiation of this class.
   // --------------------------------------------------------------------------------

   // Gives Error No: 2048
   // Error Str: Static function aRestAction::factory() should not be abstract

   // abstract public static function factory($request);

   // --------------------------------------------------------------------------------
   // Intercept calls to methods that are not explicitly defined.  Combined with
   // the operation type that has been set at instantiation, this information is
   // used to call the appropriate method to service the request.
   //
   // @param $target Desired method invoked
   // @param $arguments Array of arguments passed to the method
   //
   // @returns The value returned by the invoked method
   //
   // @throws Exception if the method does not exist
   // --------------------------------------------------------------------------------

   abstract public function __call($target, $arguments);

}//aRestAction

?>
