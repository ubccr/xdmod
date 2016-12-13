<?php

   $returnData = array();
   
   try
   {
   
      $end_date = date('Y-m-d');
      $start_date = date('Y-m-d',  mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

      if(isset($_GET['start_date']))
      {
         $start_date = $_GET['start_date'];
      }
      
      if(isset($_GET['end_date']))
      {
         $end_date = $_GET['end_date'];
      }

      $aggregation_unit = 'auto';
      
      if(isset($_GET['aggregation_unit']))
      {
         $aggregation_unit = $_GET['aggregation_unit'];
      }

      $query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date,$end_date,'none', 'all');
      $results = $query->execute();

      $returnData =  array(
            'totalCount' => 1, 
            'message' =>'', 
            'data' => array($results),
            'success' => true
      );

   }
   catch(Exception $ex) {

      \xd_response\presentError($ex->getMessage());
      
   }

   \xd_controller\returnJSON($returnData);

?>