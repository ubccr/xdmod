<?php

class PNGFormat implements iBinaryFormat
{

    public function toString()
    {
         
        return "png";
    }//toString
        
   // -----------------------------------
         
    public function getHeaders()
    {
         
        return array(
          'content-type' => 'image/png'
        );
    }//getHeaders

   // -----------------------------------
      
    public function getDescription()
    {
         
        return "A bitmapped image format that employs lossless data compression.";
    }//getDescription
}//PNGFormat
