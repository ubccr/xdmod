<?php

   namespace xd_dashboard;

   // -----------------------------------------------------------
   
   function deriveUserEnumerationQuery($group_filter = 'all', $role_filter = 'any', $context_filter = '', $exclude_unspecified_emails = false) {   

      $query  = "SELECT DISTINCT CONCAT(u.first_name, ' ', u.last_name) AS formal_name, u.id, u.username, u.first_name, u.last_name, u.email_address, u.user_type, ";
      $query .= "(SELECT GROUP_CONCAT(r.description SEPARATOR ', ') FROM moddb.UserRoles AS ur, moddb.Roles AS r WHERE ur.role_id = r.role_id AND ur.user_id=u.id) AS role_type, "; 
      
      $query .= "CASE WHEN (SELECT init_time FROM SessionManager WHERE user_id=u.id ORDER BY init_time DESC LIMIT 1) IS NULL ";
      $query .= "THEN '0' ";
      $query .= "ELSE (SELECT init_time FROM SessionManager WHERE user_id=u.id ORDER BY init_time DESC LIMIT 1) ";
      $query .= "END AS last_logged_in ";  
          
      $query .= "FROM moddb.Users AS u, moddb.UserRoles AS ur, moddb.Roles AS r ";
      
      // ===================
            
      if ( ($role_filter != "any") && ($group_filter != "all") ) { 
            
         $query .= "WHERE u.id = ur.user_id AND r.role_id = ur.role_id AND ur.role_id=$role_filter AND u.user_type=$group_filter ";
         
      }
      else if ($role_filter != "any") {

         $query .= "WHERE u.id = ur.user_id AND r.role_id = ur.role_id AND ur.role_id=$role_filter ";
                  
      }
      else if ($group_filter != "all") {

         $query .= "WHERE u.id = ur.user_id AND r.role_id = ur.role_id AND u.user_type=$group_filter ";
                  
      }  
      else {
        
         $query .= "WHERE u.id = ur.user_id AND r.role_id = ur.role_id ";

      }    
      
      if ($exclude_unspecified_emails == true) {
      
         $query .= "AND u.email_address != '".NO_EMAIL_ADDRESS_SET."' ";
      
      }

      if (!empty($context_filter)) {
         $query .= "AND (" . 
                   "u.username LIKE CONCAT('%', :filter, '%') " .
                   "OR u.first_name LIKE CONCAT('%', :filter, '%') " .
                   "OR u.last_name LIKE CONCAT('%', :filter, '%') " .
                   ") ";
      }
            
      // ===================
            
      $query .= "ORDER BY u.last_name";
      
      //print $query;
      
      return $query;
         
   }//deriveUserEnumerationQuery

?>