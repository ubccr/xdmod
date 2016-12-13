<?php

   session_start();
   
   $response = array();
   
   // --------------------------------------
   
   assert_variable_set('operation');
   assert_variable_set('variable');
         
   // --------------------------------------
        
   $variable = $_POST['variable'];
   $operation = $_POST['operation'];
   
   // --------------------------------------
   
   // When setting/getting session variables this way, it is VERY important that certain
   // keys (variable names) be written and accessed via this controller
            
   $allowed_names = array('cached_call');

   // --------------------------------------
      
   switch($operation) {
   
      case "set_variable":
        
         if (!in_array($variable, $allowed_names)) {
         
            $response['success'] = false;
            $response['message'] = "invalid use of this controller.  write access denied";   
   
            echo json_encode($response);         
            
            exit;
            
         }

         assert_variable_set('value');
                  
         $_SESSION[$variable] = $_POST['value'];
         
         $response['success'] = true;
         
         break;
   
      case "get_variable":

         if (!in_array($variable, $allowed_names)) {
         
            $response['success'] = false;
            $response['message'] = "invalid use of this controller.  read access denied";   
   
            echo json_encode($response);         
            
            exit;
            
         }
               
         if (isset($_SESSION[$variable])) {
 
            $response['success'] = true;
            $response[$variable] = $_SESSION[$variable];         
            
         }
         else {
         
            $response['success'] = false;
            $response['message'] = 'variable not set';           
         
         }
      
         break;
         
      default:
      
         $response['success'] = false;
         $response['message'] = "unknown operation $operation";   
         
   }//switch
      
   echo json_encode($response);
   
   // ========================================================
   
   function assert_variable_set($var) {
 
      if (!isset($_POST[$var])) {
   
         $response['success'] = false;
         $response['message'] = "$var not specified";   
   
         echo json_encode($response);
         exit;
   
      }//if
        
   }//assert_variable_set
   
?>