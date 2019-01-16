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

?>
