<?php

namespace Portal;

class Profile extends \aRestAction
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
        return new Profile($request);
    }


   // ACTION: fetchAction ================================================================================

    private function fetchVisibility()
    {
      
        return false;
    }//fetchVisibility
   
   // -----------------------------------------------------------
   
    private function fetchAction()
    {
                  
        $user = $this->_authenticateUser();

        $profileData = array();
        $profileData['first_name'] = $user->getFirstName();
        $profileData['last_name'] = $user->getLastName();
        $profileData['email_address'] = $user->getEmailAddress();

        if ($profileData['email_address'] == NO_EMAIL_ADDRESS_SET) {
            $profileData['email_address'] = '';
        }
            
        $profileData['is_xsede_user'] = $user->isXSEDEUser();
        $profileData['first_time_login'] = ($user->getCreationTimestamp() == $user->getLastLoginTimestamp());
        $profileData['autoload_suppression'] = isset($_SESSION['suppress_profile_autoload']);

        //$profileData['token'] = $user->getToken();
        //$profileData['token_expiration'] = $user->getTokenExpiration();

        $profileData['field_of_science'] = $user->getFieldOfScience();
      
        $profileData['active_role'] = $user->getActiveRole()->getFormalName();
   
        return array(
         'success' => true,
         'results' => $profileData
        );
    }//fetchAction

   // -----------------------------------------------------------

    private function fetchDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Retrieve general profile information for a user');
       
        $documentation->setAuthenticationRequirement(true);
      
        $documentation->addReturnElement("first_name", "The first name of the user associated with the token");
        $documentation->addReturnElement("last_name", "The last name of the user associated with the token");
        $documentation->addReturnElement("email_address", "The e-mail of the user associated with the token");
        $documentation->addReturnElement("field_of_science", "The field of science mapped to the user associated with the token");
      
        return $documentation;
    }//fetchDocumentation
  

   // ACTION: updateAction ================================================================================

    private function updateVisibility()
    {
      
        return false;
    }//updateVisibility
   
   // -----------------------------------------------------------
   
    private function updateAction()
    {
                  
        $user = $this->_authenticateUser();

        $actionParams = $this->_parseRestArguments('first_name/last_name/email_address');

        $user->setFirstName(urldecode($actionParams['first_name']));
        $user->setLastName(urldecode($actionParams['last_name']));
        $user->setEmailAddress($actionParams['email_address']);
        //$user->setFieldOfScience($actionParams['field_of_science']);
    
        if (isset($actionParams['password'])) {
            $password = urldecode($actionParams['password']);
            $user->setPassword($password);
        }
            
        $returnData = array();
      
        try {
            $user->saveUser();
            $returnData['success'] = true;
            $returnData['message'] = 'User profile updated successfully';

            // The user has updated his/her profile.  The 'suppress_profile_autoload' session variable primarily pertains
            // to an XSEDE User who has updated his/her profile after the first login.  Should the user reload the entire
            // site, or re-visit their profile after the profile update, this variable assists in making sure the welcome
            // message should not be re-shown.
         
            $_SESSION['suppress_profile_autoload'] = true;
        } catch (Exception $e) {
            $returnData['success'] = false;
            $returnData['message'] = $e->getMessage();
        }
    
        return $returnData;
    }//updateAction

   // -----------------------------------------------------------

    private function updateDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Update profile information for a user');
       
        $documentation->setAuthenticationRequirement(true);

        $documentation->addArgument("first_name", "The first name of the user associated with the token");
        $documentation->addArgument("last_name", "The last name of the user associated with the token");
        $documentation->addArgument("email_address", "The e-mail of the user associated with the token");
        //$documentation->addArgument("field_of_science", "The field of science mapped to the user associated with the token");
        
        $documentation->addArgument("password", "The password associated with the user's account", false);
      
        $documentation->addReturnElement("success", "Boolean value indicating whether the profile update was successful");
        $documentation->addReturnElement("message", "If the update did not succeed, this element will indicate the reason for failure.");
      
        return $documentation;
    }//updateDocumentation
}// class Profile
