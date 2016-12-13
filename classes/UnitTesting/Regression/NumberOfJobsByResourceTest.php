<?php

@require_once dirname(__FILE__).'/../../../configuration/linker.php';


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This test is designed to test the values of number of jobs by resource in the jobs realm
* 
*/

class NumberOfJobsByResourceTest extends PHPUnit_Framework_TestCase
{

	function setUp()
    {
		
    }
	
	function tearDown() 
	{ 
		
	}
	   
	public function testAll()
    {
		$query = new \DataWarehouse\Query\Jobs\Aggregate(
			   'month', 
				'2011-01-01', 
				'2011-12-31', 
				null,
				null,
				array(),
				'tg_usage',
				 array(),
				false);
		
		$query->addGroupBy('resource');
		$query->addGroupBy('pi');
		$query->addStat('total_su');
		$query->addStat('job_count');
		$query->addOrderBy('resource','asc');
	//	echo $query->getQueryString(); 
		$results = $query->executeRaw();
		
		//print_r($results);
		//$this->assertEquals( $this->_scalableValue->get(false), 10, "This should pass" );
    }

}

?>
