<?php

   // Binary (Raw Data) interface
   
interface iBinaryFormat
{

    public function toString();
    public function getDescription();
    public function getHeaders();
}//iBinaryFormat
