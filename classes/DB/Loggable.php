<?php

/* 
 * @class Loggable
 * @author: Amin Ghadersohi 7/1/2012
 * The parent class for all loggable classes
 */
 
class Loggable
{
    protected $_logger = null;
    public function setLogger(Log $logger)
    {
        $this->_logger = $logger;
    }
} //Ingestor
