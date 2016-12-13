<?php

   namespace xd_date;

   // --------------------------------

   function getFormalTimeframe($timeframe, $type) {
   
      if ($type == 'previous')
         return "previous $timeframe";
      if ($type == "to_date")
         return "$timeframe to date";
   
   }//getFormalTimeframe
   
   // --------------------------------
      
   function getEndpoints($timeframe) {
   
      $start_date = date('Y-m-d');
      $end_date = date('Y-m-d');
      
      switch (strtolower($timeframe)) {

         case 'yesterday':
         
            $start_date = date('Y-m-d', strtotime('yesterday'));
            $end_date = $start_date;
            
            break;
                     
         case 'month to date':
         
            $start_date = date('Y-m-01');
            break;

         case 'quarter to date':

            $current_qdiff = (date('n') - 1) % 3;

            $start_date = date("Y-m", strtotime("$current_qdiff months ago")).'-01';
            
            break;
      
         case 'year to date':
         
            $start_date = date('Y-01-01');
            break;

         case 'previous month':
            
            $start_date = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1 ,date("Y"))); 
            $end_date = date("Y-m-d", mktime(0, 0, 0, date("m"), 0 ,date("Y")));        
               
            break;
  
         case 'previous quarter':

            $current_qdiff = (date('n') - 1) % 3;
            $previous_qdiff = $current_qdiff + 3;

            $start_date = date("Y-m", strtotime("$previous_qdiff months ago")).'-01';
            $end_date = date("Y-m-d", mktime(0, 0, 0, date("m") - $current_qdiff, 0 ,date("Y")));
                    
            break;
         
         case 'previous year':
         
            $previous_year = date('Y') - 1;
            
            $start_date = $previous_year.'-01-01';
            $end_date = $previous_year.'-12-31';
         
            break;
                    
         case (preg_match('/^\d{4}$/', $timeframe) ? true : false):
            
            //Year
            
            $start_date = $timeframe.'-01-01';
            $end_date = $timeframe.'-12-31';
            
            break;

         case (preg_match('/^\d{1,} day$/i', $timeframe) ? true : false):
            
            //# Day
            
            list($days, $dummy) = explode(' ', $timeframe);
            
            $start_date = date("Y-m-d", strtotime("$days days ago"));
            
            break;            

         case (preg_match('/^\d{1,} year$/i', $timeframe) ? true : false):
            
            //# Year
            
            list($years, $dummy) = explode(' ', $timeframe);
            
            $start_date = date("Y-m-d", strtotime("$years years ago"));
            
            break; 
            
         default:
         
            throw new \Exception("Invalid timeframe supplied ($timeframe)");
            break;
                        
      }//switch
            
      return array(
                     'start_date' => $start_date,
                     'end_date' => $end_date
      );
      
   }//getEndpoints

?>