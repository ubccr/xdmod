<?php

    // Operation: user_admin->enum_roles

   $xda = new XDAdmin();

   $roles = $xda->enumerateAcls();

try {
    $multipleServiceProviders = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
}
catch(Exception $e){
    $multipleServiceProviders = false;
}

foreach($roles as $currentRole) {
    // requiresCenter can only be true iff the current install supports
    // multiple service providers.
    if ($currentRole['name'] !== 'pub') {
        $requiresCenters = $currentRole['requires_center'];
        $displayCenters = $requiresCenters && $multipleServiceProviders;
        $roleEntries[] = array(
            'acl' => $currentRole['display'],
            'acl_id' => $currentRole['name'],
            'include' => false,
            'primary' => false,
            'displays_center' => (bool) $displayCenters,
            'requires_center' => (bool) $requiresCenters
        );
    }
}//foreach

// -----------------------------

   $returnData['success'] = true;
   $returnData['status'] = 'success';
   $returnData['acls'] = $roleEntries;

   xd_controller\returnJSON($returnData);

?>
