<?php
/*
 * @Interface Ingestor
 * The interface for all ingestor classes
 */
 
interface Ingestor
{
	/*
	* @function ingest  (ingests the data, whatever that may mean)
	* @access public
	*/
    public function ingest();

    public function setLogger(Log $logger);

} //Ingestor

?>
