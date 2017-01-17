<?php
    
    // Operation: user_admin->enum_roles
    
   $xda = new XDAdmin();
            
   $roles = $xda->enumerateRoles();
            
foreach ($roles as $currentRole) {
    $roleEntries[] = array(
    'role' => $currentRole['description'],
    'role_id' => $currentRole['abbrev'],
    'include' => false,
    'primary' => false
    );
}//foreach

   // -----------------------------

   $returnData['success'] = true;
   $returnData['status'] = 'success';
   $returnData['roles'] = $roleEntries;
            
   xd_controller\returnJSON($returnData);
