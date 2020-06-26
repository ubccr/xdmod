<?php

use \DataWarehouse\Access\ReportGenerator;

$filters = array(
    'build_only' => array(
        'filter' => FILTER_VALIDATE_BOOLEAN
    ),
    'report_id' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_ID_REGEX)
    ),
    'start_date' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_DATE_REGEX)
    ),
    'end_date' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_DATE_REGEX)
    ),
    'export_format' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => ReportGenerator::REPORT_FORMATS_REGEX)
    )
);

   try {

      $userdata = filter_input_array(INPUT_POST, $filters);
      
      $user = \xd_security\getLoggedInUser();
      
      $rm = new XDReportManager($user);
      	
      $report_id = $userdata['report_id'];
      if ($report_id === null) {
          \xd_response\presentError('Invalid value specified for report_id');
      }

      $build_only = $userdata['build_only'];
      if ($build_only === null) {
          \xd_response\presentError('Invalid value specified for build_only');
      }

      $export_format = $userdata['export_format'];
    if ($export_format === null) {
        $export_format = XDReportManager::DEFAULT_FORMAT;
    }

      $start_date = $userdata['start_date'];
      $end_date = $userdata['end_date'];

      $returnData['action'] = 'send_report';  
      $returnData['build_only'] = $build_only;
      
      try {
            
         $build_response = $rm->buildReport($report_id, $export_format, $start_date, $end_date);
         
         $working_dir = $build_response['template_path'];
         $report_filename = $build_response['report_file'];
         
         if ($build_only) {
   
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
