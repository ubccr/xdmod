<?php
	
	// Operation: user_admin->enum_resource_providers
	
	$xda = new XDAdmin();
			
	$resourceProviders = $xda->enumerateResourceProviders();
			
   $providers = array();

   //$providers[] = array('id' => '-1', 'organization' => 'No Service Provider');
   
	foreach($resourceProviders as $provider) {

		$providers[] = array(
                        'id' => $provider['id'], 
                        'organization' => $provider['organization'].' ('.$provider['name'].')',
                        'include' => false
		                );
		           
	}

	// -----------------------------

	$returnData['status'] = 'success';
	$returnData['success'] = true;
	$returnData['providers'] = $providers;
			
	xd_controller\returnJSON($returnData);
			
?>