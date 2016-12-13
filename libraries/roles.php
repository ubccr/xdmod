<?php

	namespace xd_roles;

    use CCR\DB;

   // ----------------------------------------------------------

   /*
    *
    * @function getRoleIDFromIdentifier
    *
    * @param string $identifier (see constants.php, ROLE_ID_... constants)
    *
    * @return int (the numerical id corresponding to the role identifier passed in)
    *
    */
    		
   function getRoleIDFromIdentifier($identifier) {
	 
      $pdo = DB::factory('database');

      $role_data = $pdo->query("SELECT role_id FROM Roles WHERE abbrev=:abbrev", array(
         ':abbrev' => $identifier,
      ));

      if (count($role_data) == 0) {
         throw new Exception('Invalid role identifier specified -- '.$identifier);
      }
          
      return $role_data[0]['role_id'];

   }//getRoleIDFromIdentifier
   
   // ----------------------------------------------------------

   /*
    *
    * @function getFormalRoleNameFromIdentifier
    *
    * @param string $identifier (e.g. 'po', 'pi', 'cd;574', 'cc;28')
    *
    * @return String (the formal name associated with the $identifier passed in)
    *
    */
    		
   function getFormalRoleNameFromIdentifier($identifier) {
	 
      $pdo = DB::factory('database');
      
      $role_config = explode(';', $identifier);
      
      $role_data = $pdo->query("SELECT description FROM Roles WHERE abbrev=:abbrev", array(
         ':abbrev' => $role_config[0],
      ));

      if (count($role_data) == 0) {
         return "Unknown Role ID $identifier";
      }
      
      $role_label = $role_data[0]['description'];
      
      switch($role_config[0]) {
      
         case ROLE_ID_CAMPUS_CHAMPION:
         
            $role_query = $pdo->query("SELECT o.name FROM modw.organization AS o WHERE o.id=:id", array(
               ':id' => $role_config[1],
            ));
                         
            $role_label .= ' - '.$role_query[0]['name'];
            break;
         
         case ROLE_ID_CENTER_DIRECTOR:
         case ROLE_ID_CENTER_STAFF:

            $role_query = $pdo->query("SELECT CONCAT(o.abbrev, ' (', o.name, ')') AS description FROM modw.organization AS o WHERE o.id=:id", array(
               ':id' => $role_config[1],
            ));
                         
            $role_label .= ' - '.$role_query[0]['description'];        
            break;
    
      }//switch($role_data)
      
      return $role_label;

   }//getFormalRoleNameFromIdentifier
   
   // ----------------------------------------------------------

   /*
    *
    * @function determineActiveRoleForUser
    *
    * Determines what active role this user should take on (if 'active_role' is supplied in a URL that triggers this
    * function, then the active role will be derived from the value associated with the 'active_role' param in the URL)
    *
    * @param XDUser $user
    *
    * @return an instance of a role class (e.g. any of which extends aRole -- CenterDirector, CampusChampion, etc..)
    *
    */
       
   function determineActiveRoleForUser($user) {
   
   	if (isset($_REQUEST['active_role'])) {
   	  
   	  $role_data = explode(';', $_REQUEST['active_role']);
   	  $role_data = array_pad($role_data, 2, NULL);
   	  
   	  return $user->assumeActiveRole($role_data[0], $role_data[1]);
   	  
   	}
   
   	return $user->getActiveRole();
   
   }//determineActiveRoleForUser
   
   function determineActiveRoleForUser2($user, $active_role) {
   
   	if (isset($active_role)) {
   	  
   	  $role_data = explode(':', $active_roles);
   	  $role_data = array_pad($role_data, 2, NULL);
   	  
   	  return $user->assumeActiveRole($role_data[0], $role_data[1]);
   	  
   	}
   
   	return $user->getActiveRole();
   
   }//determineActiveRoleForUser2
      
?>
