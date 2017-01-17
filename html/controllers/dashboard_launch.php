<?php

   // When a manager clicks on the 'Dashboard' link in the portal,
   // they should not need to subsequently log into the Dashboard via the prompt.
   // The logic below allows for automatic log-on.
   
   require_once dirname(__FILE__).'/../../configuration/linker.php';

   @session_start();
   xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE));
   
   $response = array('action' => 'dashboard_launch');
   
try {
    $user = \xd_security\getLoggedInUser();
      
    if (isset($user) && $user->isManager()) {
        $_SESSION['xdDashboardUser'] = $user->getUserID();
        $response['success'] = true;
    } else {
        $response['success'] = false;
    }
} catch (SessionExpiredException $see) {
   // TODO: Refactor generic catch block below to handle specific exceptions,
   //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    unset($_SESSION['xdDashboardUser']);
    $response['success'] = false;
}
      
   echo json_encode($response);
