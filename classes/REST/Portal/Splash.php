<?php

namespace Portal;

class Splash extends \aRestAction
{

   // --------------------------------------------------------------------------------
   // @see aRestAction::__call()
   // --------------------------------------------------------------------------------

   public function __call($target, $arguments)
   {
         
      // Verify that the target method exists and call it.

      $method = $target . ucfirst($this->_operation);
    
      if ( ! method_exists($this, $method) )
      {
      
         if ($this->_operation == 'Help') {
           
            // The help method for this action does not exist, so attempt to generate a response
            // using that action's Documentation() method
            
            $documentationMethod = $target.'Documentation';
            
            if ( ! method_exists($this, $documentationMethod) ) {
               throw new \Exception("Help cannot be found for action '$target'");
            }
            
            return $this->$documentationMethod()->getRESTResponse();            
         
         }
         else if($this->_operation == "ArgumentSchema") {
         
            $schemaMethod = $target.'ArgumentSchema';
         
            if ( ! method_exists($this, $schemaMethod) ) {
               throw new \Exception("Argument schema information cannot be found for action '$target'");
            }        
         
            return $this->$schemaMethod(); 
                     
         }
         else {
            throw new \Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
         }
         
      }
         
      return $this->$method($arguments);
  
   }//__call

   // --------------------------------------------------------------------------------
   // @see aRestAction::factory()
   // --------------------------------------------------------------------------------

   public static function factory($request)
   {
      return new Splash($request); 
   }

   // ACTION: getImage ================================================================================

   private function getGeneralDataVisibility() {
      
      return false;
   
   }//getGeneralDataVisibility
   
   // -----------------------------------------------------------

   private function getGeneralDataAction()
   {
                  
      $returnData = array();
      
      $actionParams = $this->_parseRestArguments();
      
      try
      {
      
         // By default, consider the most current month's worth of job data
         
         $end_date = date('Y-m-d');
         $start_date = date('Y-m-d',  mktime(0, 0, 0, date("m"), date("d")-30,   date("Y")));
   
         if(isset($actionParams['start_date']))
         {
            $start_date = $actionParams['start_date'];
         }
         
         if(isset($actionParams['end_date']))
         {
            $end_date = $actionParams['end_date'];
         }
   
         $aggregation_unit = 'auto';
         
         if(isset($actionParams['aggregation_unit']))
         {
            $aggregation_unit = $actionParams['aggregation_unit'];
         }
   
         $query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date,$end_date,'none', 'all');
         
         $results = $query->execute();
         
         $results['start_date'] = $start_date;
         $results['end_date'] = $end_date;
         
         $warehouse = new \XDWarehouse();
         //$results['total_users'] = array($warehouse->totalGridUsers());
        
         
         $results['publications_wrt_tg'] = array('TBD');
   
   //this query only has data for the last month.
	  $splashSummaryQuery = "select median_waitduration_hours,
									stddev_waitduration_hours, 	
									median_processor_count, 	
									stddev_processor_count,
									user_count from modw_aggregates.SplashSummary";
	  $splashResults = \DataWarehouse::connect()->query($splashSummaryQuery);
	   
		$results['median_waitduration_hours'] = array($splashResults[0]['median_waitduration_hours']);
		$results['stddev_waitduration_hours'] = array($splashResults[0]['stddev_waitduration_hours']);
		$results['median_processor_count'] = array($splashResults[0]['median_processor_count']);
		$results['stddev_processor_count'] = array($splashResults[0]['stddev_processor_count']);
   		$results['total_users'] = array($splashResults[0]['user_count']);
        
		 $returnData = array(
            'success' => true,
            'results' => $results
         );
   
      }
      catch(Exception $ex)
      {
         $returnData = array(
            'success' => false,
            'message' => $ex->getMessage(), 
         );
      }
      
      
      return $returnData;
      
   }//getGeneralDataAction

   // -----------------------------------------------------------

   private function getGeneralDataDocumentation()
   {
      
      $documentation = new \RestDocumentation();
      
      $documentation->setDescription('Get general usage data for '.ORGANIZATION_NAME);
       
      $documentation->setAuthenticationRequirement(false);
      
      $documentation->addArgument('start_date', 'A starting date for the timeframe of interest', false);
      $documentation->addArgument('end_date', 'An ending date for the timeframe of interest', false);
      $documentation->addArgument('aggregation_unit', 'The granularity in which the data within the timeframe can be viewed', false);
      
      return $documentation;
   
   }//getGeneralDataDocumentation
  
  
  }// class Splash

?>
