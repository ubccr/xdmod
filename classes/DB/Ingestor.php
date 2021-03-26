<?php
/*
 * @Interface Ingestor
 * The interface for all ingestor classes
 */

use Psr\Log\LoggerInterface;

interface Ingestor
{
	/*
	* @function ingest  (ingests the data, whatever that may mean)
	* @access public
	*/
    public function ingest();

    public function setLogger(LoggerInterface $logger);

} //Ingestor

?>
