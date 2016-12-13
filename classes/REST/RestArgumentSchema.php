<?php

  /*
   * @Class RestArgumentSchema
   *
   */
   
class RestArgumentSchema {
    
   private $_mappings;
   private $_user;

   // ----------------------------------------------------
          
   function __construct (\XDUser $user) {
    
      $this->_user = $user;
      $this->_mappings = array();
       
   }//__construct
 
   // ----------------------------------------------------

   // Maps an argument to a function which returns an array (of id/label pairs) representing
   // all the possible values that can be assigned to that argument.
   //
   // The second argument to map(...) is a function name representing the function which will generate an enumeration (array)
   // of possible values that can be assigned to the respective argument.  Each element in the returned array will be an 
   // associative array of the following structure:
   //
   //  $enum = (
   //    array(id => ..., label => ...)
   //    array(id => ..., label => ...),
   //    array(id => ..., label => ...),
   //    ...
   //  );
   //
   // For each value, the 'id' represents the variable name recognized by REST when parsing out arguments from the call (URL).
   // The 'label' represents the full textual (more humanly-readable) version of that argument.  This is definitely useful for
   // a user wishing to use the REST Call Builder, being that the 'label' is less cryptic than the corresponding 'id'.
   //
   // If the associated function (second argument to map(...)) takes arguments itself, then the respective argument
   // (first argument to map(...)) has dependencies.  Those dependencies are presented as argument names in an array 
   // specified as the third argument to map(...)
   //
   // The order in which map is called should dictate a 'dependency chain' (e.g. non-dependent arguments should come
   // before dependent arguments).
   //
   // Examples:
   // map('group_by', 'enum_group_by');   // The function 'enum_group_by' provides an enumeration of all values
   //                                     // that could be assigned to the 'group_by' argument.
   //                                     // Since there is no third argument to map(...), the function 'enum_group_by'
   //                                     // takes no arguments (hence 'group_by' has no dependencies).
   //
   //
   // map('statistic', 'enum_stats', array('group_by', 'another_dependency'));  // The function 'enum_stats' provides an enumeration for the 'statistic' argument.  The presence
   //                                                                           // of a third argument immediately implies that 'statistic' is a dependent argument, and the
   //                                                                           // enumeration of values is strictly dependent on the values assigned to arguments 'group_by' 
   //                                                                           // and 'another_dependency'
   //
   // To illustrate the 'dependency chain' properly (for documentation purposes and the REST Call Builder), it is important
   // to make calls to map(...) such that non-dependent arguments are addressed prior to dependent arguments, e.g.:
   //
   // (1)  map('group_by', 'enum_group_by');
   // (2)  map('another_dependency', 'enum_dependency_values');
   // (3)  map('statistic', 'enum_stats', array('group_by', 'another_dependency'));    <--- first dependent argument in 'dependency chain' (listed third in the group of calls to map(...))
   //
   // As can be seen (above), the arguments 'statistic' depends on for enumeration of its own values are addressed prior (in calls (1) and (2)) to map('statistic', ....)
   
   // Type: TYPE_ENUM (an enumeration, with the $enum_function specified)
   //       TYPE_DATE (a date, which is to be specified manually)
   
   public function map($arg_name, $type, $enum_function = NULL, $dependencies = array()) {
    
      $this->_mappings[] = array('argument' => $arg_name, 'type' => $type, 'enum' => $enum_function, 'dependencies' => $dependencies);
 
   }//map
 
   // ----------------------------------------------------
 
   public function get_argument_type($argument) {

      foreach ($this->_mappings as $mapping) {
      
         if ($mapping['argument'] == $argument){
            
            switch($mapping['type']) {
            
               case TYPE_ENUM: 
                  return 'enumeration';

               case TYPE_DATE: 
                  return 'date';
               
            }//switch
            
         }
      
      }//foreach
   
      return 'unknown';
         
   }//get_argument_type
   
   // ----------------------------------------------------
     
   public function get_dependencies_for($argument) {
   
      foreach ($this->_mappings as $mapping) {
      
         if ($mapping['argument'] == $argument){
            return $mapping['dependencies'];
         }
      
      }//foreach
   
      return array();
   
   }//get_dependencies_for
   
   // Returns an array of arguments that the user can now supply, provided what the user has currently supplied.
   // This function operates on the assumption that the calls to map(...) represent a top-down dependency chain (argument
   // dependencies are satisfied before dependent arguments).
   // Also, no cyclic dependencies.
   
   public function get_available_arguments(&$supplied_arguments) {
   
      // Phase 1:  Determine what arguments are assigned, and of those assigned, are assigned to a correct value.
      
      $internal_stack = array();
      
      foreach ($this->_mappings as $mapping) {
         
         $argument_name = $mapping['argument'];
         
         if (isset($supplied_arguments[$argument_name])) {
                  
            $argument_value = $supplied_arguments[$argument_name];
            
            $dependencies = $this->get_dependencies_for($argument_name);
         
            $validation_args = array();
            
            foreach ($dependencies as $dependency) {
            
               if (isset($internal_stack[$dependency])) {
                  $validation_args[] = $internal_stack[$dependency];
               }
               
            }
            
            // Make sure all argument dependencies have been supplied before proceeding
            
            if (count($validation_args) == count($dependencies)){
            
               $validCheck = $this->validate_argument($argument_name, $argument_value, $validation_args);
               
               if ($validCheck) {
                  $internal_stack[$argument_name] = $argument_value;
               }
               
            }
            
         }//if (isset)
         
      }//foreach
      
      // At this point, $internal_stack holds the properly assigned arguments
      
      // ========================
      
      // Phase 2: Determine what new arguments should be made available (based on phase 1)
      
      $available_arguments = array();
       
      foreach ($this->_mappings as $mapping) {
      
         $argument_name = $mapping['argument'];
         $dependencies = $this->get_dependencies_for($argument_name);
         
         // If all the dependencies for this argument have been supplied (based on what is in $internal_stack),
         // then this argument should be returned
         
         $intersect = array_intersect(array_keys($internal_stack), $dependencies);
   
         $is_subset = (count($dependencies) == count($intersect));
   
         if ($is_subset) {
         
            $available_arguments[] = $argument_name;
         
         }
         
      }//foreach
      
      // ========================
      
      $all_arguments_satisfied = (count($internal_stack) == count($this->_mappings));
      
      // The first element in the array to be returned is a listing of all the argument names that can be used at this point in time.
      // The second argument in the array to be returned is a boolean value indicating whether all the arguments required have been assigned correctly.
      
      return array($available_arguments, $all_arguments_satisfied);
      
   }//get_available_arguments
   
   // ----------------------------------------------------
       
   public function get_possible_values($arg_name, $func_args = array()) {
              
      foreach($this->_mappings as $mapping) {
       
         if ($mapping['argument'] == $arg_name) {
          
            if ($mapping['type'] != TYPE_ENUM){
               return array('user_specified');
            }
            
            $function_name = $mapping['enum']; 
          
            if (count($mapping['dependencies']) != count($func_args)){
               throw new Exception("$function_name requires exactly ".count($mapping['dependencies'])." arguments: ".implode(', ', $mapping['dependencies']));
            }
                
            return call_user_func_array($function_name, $func_args);
                
         }//if
       
      }//foreach
          
      return NO_ENUMERATION;
    
   }//get_possible_values

   // ----------------------------------------------------
   
   public function resolve_argument_label($arg_value_id, $arg_possible_values) {
      
      foreach ($arg_possible_values as $value) {
         
         if ($value['id'] == $arg_value_id) {
            return $value['label'];
         }
            
      }//foreach
         
      return NULL;
   
   }//resolve_argument_label
   
   // ----------------------------------------------------
    
   public function validate_argument($arg_name, $value_to_check, $func_args = array()) {
    
      $check_status = FALSE;

      $possible_values = $this->get_possible_values($arg_name, $func_args);
          
      if ($possible_values == NO_ENUMERATION){
         return FALSE;
      }
      
      foreach($possible_values as $value) {
             
         if ($value['id'] == $value_to_check) {
            return TRUE;
         }
          
      }//foreach
          
      return $check_status;
    
   }//validate_argument
       
 
}//RestArgumentSchema
   
?>