<?php

   class SVGFormat implements iBinaryFormat {

      public function toString() {
         
         return "svg";
         
      }//toString  
        
      // -----------------------------------
         
      public function getHeaders() {
         
         return array(
            'content-type' => 'image/svg+xml'
         );
         
      }//getHeaders   

      // -----------------------------------
      
      public function getDescription() {
         
         return "An XML-based file format for describing two-dimensional vector graphics";
         
      }//getDescription 
         
   }//SVGFormat
   
?>