<?php

	// Operation: user_profile->list_fields_of_science
	
	$db_host =     xd_utilities\getConfiguration('datawarehouse', 'host');
	$db_database = xd_utilities\getConfiguration('datawarehouse', 'database');
	$db_user =     xd_utilities\getConfiguration('datawarehouse', 'user');
	$db_pass =     xd_utilities\getConfiguration('datawarehouse', 'pass');

	mysql_connect($db_host, $db_user, $db_pass);
	
	$res = mysql_query("SELECT id, description FROM $db_database.fieldofscience ORDER BY description");
	
	$fields = array();
	
	while(list($fos_id, $fos_label) = mysql_fetch_array($res)) {
		$fields[] = array('field_id' => $fos_id, 'field_label' => $fos_label);
	}

	// -----------------------------

	$returnData['status'] = 'success';
	$returnData['fields_of_science'] = $fields;
			
	xd_controller\returnJSON($returnData);
			
?>
