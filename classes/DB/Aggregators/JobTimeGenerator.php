<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
 
class JobTimeGenerator
{
	function __construct()  
	{
		
	}
	
	function execute($modwdb, $dest_schema)
	{
		$modwdb->handle()->prepare("truncate table job_times")->execute();
		
		$modwdb->handle()->prepare("INSERT INTO `job_times` (`id`, `min_duration`, `max_duration`, `description`) VALUES
									(0, 0, 0, '0 - 1s'),
									(1, 1, 29, '1 - 30s'),
									(2, 30, 1800-1, '30s - 30min'),
									(3, 1800, 3600-1, '30 - 60min'),
									(4, 3600, (3600*5) -1, '1 - 5hr'),
									(5, 3600*5, 36000 -1, '5 - 10hr'),
									(6, 36000, (3600*18) -1, '10 - 18hr'),
									(7, 3600*18, 2147483647 , '18+hr')")->execute();
												
	}		 
}

?>