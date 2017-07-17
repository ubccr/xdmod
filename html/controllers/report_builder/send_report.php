<?php

use CCR\XDReportManager;

   try {
      
      $user = \xd_security\getLoggedInUser();
      
      $rm = new XDReportManager($user);
      	
      \xd_security\assertParametersSet(array(
         'report_id',
         'build_only'
      ));
         
      $report_id = $_POST['report_id'];
      $build_only = $_POST['build_only'];
         
      // ==========================================================================        
   
      $export_format = XDReportManager::DEFAULT_FORMAT;
      
      if (isset($_POST['export_format']) && (XDReportManager::isValidFormat($_POST['export_format']) == true)) {
         $export_format = $_POST['export_format'];
      }   
       
      $returnData['action'] = 'send_report';  
      $returnData['build_only'] = ($build_only == "true");         
      
      try {
            
         $build_response = $rm->buildReport($report_id, $export_format);
         
         $working_dir = $build_response['template_path'];
         $report_filename = $build_response['report_file'];
         
         if ($build_only == "true") {
   
            // Present enough information so that the download_report controller can serve up the file
            // (and provide appropriate cleanup) afterwards.
            
            $returnData['report_loc'] = basename($working_dir);
            
            $returnData['message'] = 'Report built successfully<br />';
            $returnData['success'] = true;
            $returnData['report_name'] = $rm->getReportName($report_id, true).'.'.$export_format;
            
            \xd_controller\returnJSON($returnData);
            
            exit;
            
         }
   
         $mailStatus = $rm->mailReport($report_id, $report_filename, '', $build_response);
               
         $destination_email_address = $rm->getReportUserEmailAddress($report_id);
         
         $returnData['message'] = $mailStatus ? "Report built and sent to<br /><b>$destination_email_address</b>" : 'Problem mailing the report';
         $returnData['success'] = $mailStatus;
            
      }
      catch(\Exception $e) {
      
         $returnData['success'] = false;
         $returnData['message'] = $e->getMessage();

      }
      
      if (isset($working_dir) == true) {
         exec("rm -rf $working_dir");
      }   
   
      // ==========================================================================
   
      \xd_controller\returnJSON($returnData);

   }
   catch (SessionExpiredException $see) {
      // TODO: Refactor generic catch block below to handle specific exceptions,
      //       which would allow this block to be removed.
      throw $see;
   }
   catch (\Exception $e) {
   
      \xd_response\presentError($e->getMessage());
   	    
   }
   
?>
