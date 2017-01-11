<?php

    // Operation: user_auth->session_check
    
try {
    if (isset($_REQUEST['session_user_id_type']) && $_REQUEST['session_user_id_type'] === 'Dashboard') {
        xd_security\getDashboardUser();
    } else {
        xd_security\detectUser(array(XDUser::PUBLIC_USER));
    }
} catch (SessionExpiredException $see) {
    // TODO: Use only specific exceptions in security functions so this
    //       block and generic catch block below can be refactored out.
    throw $see;
} catch (Exception $e) {
    xd_response\presentError($e);
}
    
    $returnData['success'] = true;
    $returnData['message'] = 'Session Alive';
    xd_controller\returnJSON($returnData);
