<?php

namespace Authentication;

class Utilities extends \aRestAction
{

   // --------------------------------------------------------------------------------
   // @see aRestAction::__call()
   // --------------------------------------------------------------------------------

    public function __call($target, $arguments)
    {
         
        // Verify that the target method exists and call it.

        $method = $target . ucfirst($this->_operation);
    
        if (! method_exists($this, $method)) {
            if ($this->_operation == 'Help') {
                // The help method for this action does not exist, so attempt to generate a response
                // using that action's Documentation() method
            
                $documentationMethod = $target.'Documentation';
            
                if (! method_exists($this, $documentationMethod)) {
                    throw new \Exception("Help cannot be found for action '$target'");
                }
            
                return $this->$documentationMethod()->getRESTResponse();
            } elseif ($this->_operation == "ArgumentSchema") {
                $schemaMethod = $target.'ArgumentSchema';
         
                if (! method_exists($this, $schemaMethod)) {
                    throw new \Exception("Argument schema information cannot be found for action '$target'");
                }
         
                return $this->$schemaMethod();
            } else {
                throw new \Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
            }
        }
         
        return $this->$method($arguments);
    }//__call

   // --------------------------------------------------------------------------------
   // @see aRestAction::factory()
   // --------------------------------------------------------------------------------

    public static function factory($request)
    {
        return new Utilities($request);
    }


   // ACTION: login ================================================================================
   
    private function loginAction()
    {
      
        $actionParams = $this->_parseRestArguments('username/password');

        $password = urldecode($actionParams['password']);
      
        $user = \XDUser::authenticate($actionParams['username'], $password);
         
        if ($user == null) {
            throw new \Exception('Invalid credentials specified');
        }
      
        if ($user->getAccountStatus() == false) {
            throw new \Exception('This account is disabled');
        }
      
        $token = \XDSessionManager::recordLogin($user);
                     
        return array(
         'success' => true,
         'results' => array('token' => $token, 'name' => $user->getFormalName())
        );
    }//loginAction

   // -----------------------------------------------------------
  
    private function loginDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Authenticate and login to the REST service.');
      
        $documentation->setAuthenticationRequirement(false);
            
        $documentation->addArgument('username', 'The username of the account you wish to login as');
        $documentation->addArgument('password', 'The password of the account associated with the username');
            
        $documentation->addReturnElement("token", "The unique string used for making requests to the REST service which require authentication");
        $documentation->addReturnElement("name", "The formal name associated with the account");
                  
        return $documentation;
    }//loginDocumentation
   
   
   // ACTION: logout ================================================================================
   
    private function logoutAction()
    {
      
        // This handler makes conditional use of a token
        // (the token consulted could be invalidated, yet the logout should still proceed)
      
        \XDSessionManager::logoutUser($this->_request->getToken());
      
        return array (
         'success' => true,
         'message' => 'User logged out successfully'
        );
    }//logoutAction

   // -----------------------------------------------------------
  
    private function logoutDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Logout from the REST service (invalidate token).');
            
        $documentation->setAuthenticationRequirement(true);
                     
        $documentation->addReturnElement("logged_out", "A boolean value which indicates if the logout operation succeeded");
                  
        return $documentation;
    }//logoutDocumentation
}// class Utilities
