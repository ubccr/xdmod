<?php

    // Operation: user_profile->fetch_profile

    $user_to_fetch = \xd_security\getLoggedInUser();
    
    $returnData['first_name'] = $user_to_fetch->getFirstName();
    $returnData['last_name'] = $user_to_fetch->getLastName();
    $returnData['email_address'] = $user_to_fetch->getEmailAddress();
        
   $returnData['token'] = $user_to_fetch->getToken();
   $returnData['token_expiration'] = $user_to_fetch->getTokenExpiration();

    $returnData['field_of_science'] = $user_to_fetch->getFieldOfScience();
    
    $returnData['status'] = "success";

    xd_controller\returnJSON($returnData);
