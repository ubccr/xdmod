<?php

// Operation: user_auth->update_pass

$params = array(
    'rid' => RESTRICTION_RID,
    'password' => RESTRICTION_PASSWORD
);

    $isValid = xd_security\secureCheck($params, 'POST');
    
if (!$isValid) {
    $returnData['status'] = 'invalid_params_specified';
    xd_controller\returnJSON($returnData);
};
    
    // -----------------------------
    
   $validationCheck = XDUser::validateRID($_POST['rid']);

if ($validationCheck['status'] == VALID) {
    $user_id = $validationCheck['user_id'];
      
    $user_to_update = XDUser::getUserById($user_id);
      
    $password = urldecode($_POST['password']);
      
    $user_to_update->setPassword($password);
      
    try {
        $user_to_update->saveUser();
    } catch (Exception $e) {
        $returnData['status'] = $e->getMessage();
        xd_controller\returnJSON($returnData);
    }
   
    $returnData['status'] = "success";
} else {
    $returnData['status'] = "invalid_rid";
}
   
   xd_controller\returnJSON($returnData);
