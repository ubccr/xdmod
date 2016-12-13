<?php

   class UniqueException extends XDException {
      
      private $_chained_exception_trace;

      // ------------------------------------
            
      public function __construct($unique_id, $exception) {
      
         $this->message = '[Unique ID '.$unique_id.'] --> '.$exception->getMessage();
         
         $this->code = $exception->getCode();
         $this->file = $exception->getFile();
         $this->line = $exception->getLine();
      
         $this->_chained_exception_trace = $exception->getTraceAsString();
         
      }//__construct
      
      // ------------------------------------
      
      public function getVerboseTrace() {
         
         return $this->_chained_exception_trace;
         
      }//getVerboseTrace
      
   }//UniqueException
   
?>