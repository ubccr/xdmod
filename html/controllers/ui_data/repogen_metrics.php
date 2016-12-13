<?php


	$metrics = array(
	
		'Dashboard' => array(),
		
		'Utilization' => array (
										'Number Of Jobs', 
										'CPU Consumption', 
										'SUs Consumed',
										'Total Wall Time',
										'Average Wait Time'
						      ),	
		
		'Application Performance' => array (
										'Wall Clock Time', 
										'Scalability', 
										'CPU Utilization (CPU Time / Wall Clock Time Ratio)', 
										'Floating Point Operations Per Second',
										'Memory Usage',
										'Communication Overhead'
									      ),
									
		'Resource Performance' => array (
										'CPU Speed',
										'Memory Bandwidth',
										'Memory Latency',
										'Interconnect Bandwidth',
										'Interconnect Latency',
										'I/O Performance' 
									   ),
		
		'Science Gateways' => array (
										'Total Utilization By Field Of Science'
								   ),
								   
		'Publications' => array (),
		
		'Support Tickets' => array ()
		
	);
	
	foreach($metrics as $parent => $children) {
	
		$c = array();
		
		foreach($children as $child) {
			$c[] = array('text' => $child, 'iconCls' => 'metric_child', 'leaf' => true, 'checked' => false);
		}
		
		$e[] = array('text' => $parent, 'iconCls' => 'metric_parent', 'checked' => false, 'children' => $c, 'expanded' => true);
	
	}

	echo json_encode($e);
		
?>