<?php

    // Operation: user_admin->enum_roles

   $xda = new XDAdmin();

   $roles = $xda->enumerateAcls();

foreach($roles as $currentRole) {
    // requiresCenter can only be true iff the current install supports
    // multiple service providers.
    if ($currentRole['name'] !== 'pub') {
        $roleEntries[] = array(
            'acl' => $currentRole['display'],
            'acl_id' => $currentRole['name'],
            'include' => false,
            'primary' => false,
            'displays_center' => false,
            'requires_center' => false
        );
    }
}//foreach

// -----------------------------

   $returnData['success'] = true;
   $returnData['status'] = 'success';
   $returnData['acls'] = $roleEntries;

   xd_controller\returnJSON($returnData);

?>
