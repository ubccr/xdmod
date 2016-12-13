<?php

   class JPGFormat implements iBinaryFormat {
 
      public function toString() {
         
         return "jpg";
         
      }//toString  
        
      // -----------------------------------
      
      public function getHeaders() {
         
         return array(
            'content-type' => 'image/jpeg'
         );
         
      }//getHeaders   

      // -----------------------------------
      
      public function getDescription() {
         
         return "A commonly used method of lossy compression for images.";
         
      }//getDescription  
         
   }//JPGFormat
   
?>