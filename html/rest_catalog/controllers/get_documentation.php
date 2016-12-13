<?php

   // XDMoD REST API Documentation Template (controller)
   // Author: Ryan Gentner <rgentner@ccr.buffalo.edu>
   // Last Updated: Tuesday, February 8, 2011
    
?>

<div class="rest_documentation">

   <?php
   
      require_once dirname(__FILE__).'/../../../configuration/linker.php';
   
      $restDirectoryBase = dirname(__FILE__).'/../../classes/REST/';
            
      // -----------------------
      
      function getArgumentsForCall($doc) {
      
         $args = $doc->getArguments();
         
         $arg_syntax = array();
         
         foreach($args as $arg_name => $arg_details) {
         
            $enclosure_characters = (!$arg_details['is_required']) ? array('[', ']') : array('','');
            
            $arg_syntax[] = $enclosure_characters[0]."$arg_name=..".$enclosure_characters[1];
         
         }
      
         return implode('/', $arg_syntax);
         
      }//getArgumentsForCall
   
      // -----------------------
            
      $action_id = $_GET['action_id'];
      
      list($realm, $category, $action) = explode('_', $action_id);
      
      /* Permalink generation */
      
      /*
      $url_fragments = explode('/', $_SERVER['SCRIPT_URI']);
      $base_url = implode('/', array_splice($url_fragments, 0, -2));
   
      $permalink = $base_url.'?jump_to='.strtolower($action_id);
      */
      
      $permalink = '?jump_to='.strtolower($action_id);
      
      print '<div class="permalink"><table border=0><tr><td><img src="images/link_add.png"></td><td><a href="'.$permalink.'" target="_blank">Permalink</a></td</tr></table></div>';
      
      /* Display hierarchy */
      
      print "<span class='rest_entity'>$realm</span> > ";
      print "<span class='rest_entity'>$category</span> > ";
      print "<span class='rest_entity'>$action</span>";
      print "<br /><br />";
   
      // -----------------------
      
      try {
         
         $elements = new RestElements();

         $elements->setRealm($realm);
         $elements->setCategory($category);      
         $elements->setAction($action);
      
         $handler = RestHandler::factory($elements);
               
         $category_obj_ref = $handler->{$category}();
         
         $documentation = $category_obj_ref->catalog()->{$action}();
         
         /* Purpose */
         print '<h2>Purpose: <span style="color: #00f">'.$documentation->getDescription()."</span><h2><br><br>\n";
         
         /* REST Structure */
         
         $tokenSuffix = $documentation->getAuthenticationRequirement() ? '<span class="rest_token">?token=<i>[token]</i></span>' : '';
         
         $basePrefix = strtolower("$realm/$category/$action");
         
         $trailingSlash = (strlen(getArgumentsForCall($documentation)) > 0) ? '/' : '';
         
         // Usage --------------------------------------------------
         
         print '<table border=0 class="section_usage" cellspacing=0>';
         print '<tr><td class="section_header">Usage</td></tr>';
         print "<tr><td><span class='rest_call'>/rest/<span class='rest_base'>$basePrefix{$trailingSlash}</span>";
         print "<span class='rest_arguments'>".getArgumentsForCall($documentation).'</span><span class="rest_output_format">[/output_format]</span>'.$tokenSuffix.'</span></td></tr>';
         print '</table><br /><br />';

         // Additional Information ---------------------------------
         
         if ($documentation->getAuthenticationRequirement() == true) {
         
            print '<table border=0 cellspacing=0 class="authentication_notice" width=280>';
            print '<tr><td class="section_header" colspan=2>Additional Information</td></tr>';
            print '<tr><td><img src="images/key_icon.png"></td><td>Authentication is required for this call<br>';
            print '(See <a href="javascript:void(0)" onClick="tree_depth_cache = 0; autoPilot(\'Authentication\', \'Utilities\', \'login\')">login</a>)</td></tr>';
            print '</table><br /><br />';
         

         }
                
         // Arguments ---------------------------------
                  
         $arguments = $documentation->getArguments();
         
         $optional_flag = '';
         $optional_argument_discovery = 0;
         
         if (count($arguments) > 0) {
        
            print '<table border=0 class="section_arguments" cellspacing=0>';
      
               print '<tr><td class="section_header" colspan=2>Arguments';
               
               if (count($arguments) > 1){
                  print '<br><span style="color: #00f">(NOTE: The arguments <span style="color: #f00">do not</span> need to be supplied in this exact order)</span></td></tr>';
               }
 
               $required_args = array();
               $optional_args = array();
               
               foreach ($arguments as $name => $arg_data) {
            
                  $is_required = $arg_data['is_required'];
         
                  if ($arg_data['is_required']) {
                     $required_args[$name] = $arg_data;
                  }
                  else {
                     $optional_args[$name] = $arg_data;                  
                  }
                                 
               }//foreach
                                      
               $arguments = array_merge($required_args, $optional_args);     
                                      
               foreach ($arguments as $name => $arg_data) {
               
                  $description = $arg_data['description'];
                  $is_required = $arg_data['is_required'];
         
                  if (!$is_required && $optional_argument_discovery == 0) {
                     $optional_argument_discovery = 1;
                     $optional_flag = " class = 'rest_optional_argument'";
                     print "<tr class=\"rest_optional_arguments_header\"><td colspan=2><b>Optional Arguments</b></td></tr>\n";
                  }
                  
                  print "<tr $optional_flag><td width=100><span class='rest_arguments'>$name</span></td><td>$description</td></tr>\n";
               
               }//foreach
            
            print '</table><br /><br />';
         
         }//if (count($arguments) > 0)
         
         
         // Output Elements ---------------------------------
                  
         $return_elements = $documentation->getReturnElements();
         
         print '<table border=0 class="section_output" cellspacing=0>';
   
            print '<tr><td class="section_header" colspan=2>Output (Return Elements)</td></tr>';                
                         
            if (count($return_elements) > 0) {
            
               $output_format_description = $documentation->getOutputFormatDescription();
               
               if (!empty($output_format_description)){
                  print "<tr bgcolor=\"#ccffcc\"><td colspan=2><i>$output_format_description</i></td></tr>\n";
               }
               
               foreach ($return_elements as $name => $description) {
            
                  print "<tr><td width=100><span class='rest_output'>$name</span></td><td>$description</td></tr>\n";
               
               }
            
            }
            else {
               print "<tr><td><i>This section is currently unavailable</i></td></tr>\n";
            }
         
         print '</table><br /><br />';  
         
 
         // Output Formats ---------------------------------
                  
         $default_output_format =  (in_array('RAW', array_keys($return_elements))) ? REST_DEFAULT_RAW_FORMAT : REST_DEFAULT_FORMAT;
         $output_formats = (in_array('RAW', array_keys($return_elements))) ? \xd_rest\enumerateRAWFormats() : \xd_rest\enumerateOutputFormats();
         
         print '<table border=0 class="section_output" cellspacing=0>';
   
            print '<tr><td class="section_header" colspan=2>Output Formats</td></tr>';
                             
            print "<tr><td width=160 valign=top><span class='rest_output'><b>$default_output_format</b></span> (default)</td><td width=300><i>{$output_formats[$default_output_format]}</i></td></tr>\n";
            
            unset($output_formats[$default_output_format]);
            
            foreach ($output_formats as $format => $description) {
         
               print "<tr><td width=160 valign=top><span class='rest_output'>$format</span></td><td width=300><i>$description</i></td></tr>\n";

            }
         
         print '</table><br /><br />';
         
         
      }
      catch (Exception $e) {
      
         print "<center>There was a problem retrieving the documentation for this call: " . $e->getMessage() . "</center>";
      
      }      
   
   ?>
   
</div>
