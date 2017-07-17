<?php

use CCR\XDReportManager;

   try {
         
      $user = \xd_security\getLoggedInUser();
      $rm = new XDReportManager($user);
   
      $base_path = \xd_utilities\getConfiguration('reporting', 'base_path');
      
      $map = array();
      
      // -----------------------------------
   
      \xd_security\assertParameterSet('phase');
   
      switch($_POST['phase']) {
      
         case 'create':
         
            $report_id = $user->getUserID()."-".time();
            
            break;
         
         case 'update':
         
            \xd_security\assertParameterSet('report_id');
         
            $report_id = $_POST['report_id'];
            
            // Cache the blobs so they can be re-introduced as necessary during the report update process
            $rm->buildBlobMap($report_id, $map);
            
            $rm->removeReportCharts($report_id);
            
            break;
      
      }//switch($_POST['phase'])
   	
      // -----------------------------------
      
      $report_name = $_POST['report_name'];
      $report_title = $_POST['report_title'];
      $report_header = $_POST['report_header'];
      $report_footer = $_POST['report_footer'];
   
      $rm->configureSelectedReport(
      
         $report_id,
         $report_name,
         $report_title,
         $report_header,
         $report_footer,
         $_POST['report_font'],
         $_POST['report_format'],
         $_POST['charts_per_page'],
         $_POST['report_schedule'],
         $_POST['report_delivery']
         
      );
   
      // -----------------------------------
   
      if ($rm->isUniqueName($report_name, $report_id) == false) {
      
         \xd_response\presentError('Another report you have created is already using this name.');
   
      }
   
      // -----------------------------------
         
      switch($_POST['phase']) {
      
         case 'create':
         
            $rm->insertThisReport("Manual");
            break;
         
         case 'update':
         
            $rm->saveThisReport();         
            break;
      
      }//switch($_POST['phase'])   
   
      // -----------------------------------
      
      foreach($_POST as $k => $v) {
      
         if (preg_match('/chart_data_(\d+)/', $k, $m) > 0) {
         
            $order = $m[1];
            
            list($chart_id, $chart_title, $chart_drill_details, $chart_date_description, $timeframe_type, $entry_type) = explode(';', $v);  
            
            $chart_title = str_replace('%3B', ';', $chart_title);
            $chart_drill_details = str_replace('%3B', ';', $chart_drill_details);
            
            $cache_ref_variable = 'chart_cacheref_'.$order;

            // Transfer blobs residing in the directory used for temporary files into the database as necessary for each chart which comprises the report.

            if (isset($_POST[$cache_ref_variable])) {
            
               list($start_date, $end_date, $ref, $rank) = explode(';', $_POST[$cache_ref_variable]);
   
               $location = sys_get_temp_dir() . "/{$ref}_{$rank}_{$start_date}_{$end_date}.png";

               // Generate chart blob if it doesn't exist.  This file
               // should have already been created by
               // report_image_renderer.php, but is not in Firefox.
               // See Mantis 0001336
               if (!is_file($location)) {
                  $insertion_rank = array(
                     'rank' => $rank,
                     'did'  => '',
                  );
                  $cached_blob = $start_date . ',' . $end_date . ';'
                     .  $rm->generateChartBlob('volatile', $insertion_rank, $start_date, $end_date);
               }
               else {
                  $cached_blob = $start_date.','.$end_date.';'.file_get_contents($location);
               }
               
               // ==========================
               
               //todo: consider refactoring !!!
               
               $chart_id_found = false;
               
               foreach ($map as &$e) {
               
                  if ($e['chart_id'] == $chart_id) {
                     
                     $e['image_data'] = $cached_blob;
                     $chart_id_found = true;
                     
                  }
                  
               }//foreach
               
               if ($chart_id_found == false) {
               
                  $map[] = array(
                     'chart_id' => $chart_id, 
                     'image_data' => $cached_blob
                  );
               
               }
               
               // ==========================
               
               //print "Cached blob for $order: ".$cached_blob;
               
            }
            
            $rm->saveCharttoReport($report_id, $chart_id, $chart_title, $chart_drill_details, $chart_date_description, $order, $timeframe_type, $entry_type, $map);       
              
         }//if
         
      }//foreach
   
      // -----------------------------------
         
      $returnData['action'] = 'save_report';
      $returnData['phase'] = $_POST['phase'];
      $returnData['report_id'] = $report_id;
      $returnData['success'] = true;
      $returnData['status'] = 'success';
   
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
	
