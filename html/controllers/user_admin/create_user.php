<?php

use CCR\MailWrapper;
use Models\Acl;
use Models\Services\Acls;
use Models\Services\Centers;

// Operation: user_admin->create_user

$creator = \xd_security\assertDashboardUserLoggedIn();

\xd_security\assertParametersSet(array(
    'username'      => RESTRICTION_USERNAME,
    'first_name'    => RESTRICTION_FIRST_NAME,
    'last_name'     => RESTRICTION_LAST_NAME,
  //  'assignment'    => RESTRICTION_ASSIGNMENT,
    'user_type'     => RESTRICTION_GROUP
));

\xd_security\assertEmailParameterSet('email_address');

// -----------------------------

if (isset($_POST['acls'])) {
    $acls = json_decode($_POST['acls'], true);
    if (count($acls) < 1){
        \xd_response\presentError("Acl information is required");
    }
    // Checking for an acl set that only contains feature acls.
    // Feature acls are acls that only provide access to an XDMoD feature and
    // are not used for data access.
    $aclNames = array();
    $featureAcls = Acls::getAclsByTypeName('feature');
    if (count($featureAcls) > 0) {
        $aclNames = array_reduce(
            $featureAcls,
            function ($carry, Acl $item) {
                $carry []= $item->getName();
                return $carry;
            },
            array()
        );
    }
    $found = false;
    foreach(array_keys($acls) as $acl) {
        if (!in_array($acl, $aclNames)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        \xd_response\presentError('Please include a non-feature acl ( i.e. User, PI etc. )');
    }
}
else {
    \xd_response\presentError("Acl information is required");
}

try {
    $password_chars = 'abcdefghijklmnopqrstuvwxyz!@#$%-_=+ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $max_password_chars_index = strlen($password_chars) - 1;
    $password = '';
    for ($i = 0; $i < CHARLIM_PASSWORD; $i++) {
        $password .= $password_chars[mt_rand(0, $max_password_chars_index)];
    }

    $newuser = new XDUser(
        $_POST['username'],
        $password,
        $_POST['email_address'],
        $_POST['first_name'],
        '',
        $_POST['last_name'],
        array_keys($acls),
        ROLE_ID_USER,
        NULL,
        $_POST['assignment']
    );
    $newuser->setUserType($_POST['user_type']);
    $newuser->saveUser();
    // =============================
    foreach ($acls as $acl => $centers) {
        if (count($centers) > 0) {
            $centerConfig = array();
            $count = 0;
            foreach($centers as $center) {
                if ($count === 0) {
                    $config = array('primary' => 1, 'active' => 1);
                } else {
                    $config = array('primary' => 0, 'active' => 0);
                }
                $centerConfig[$center] = $config;
                $count += 1;
            }
            $newuser->setOrganizations($centerConfig, $acl);
        } elseif (in_array($acl, array('cd', 'cs'))) {
            // This block pertains to OpenXDMoD. Specifically, no centers are
            // returned with acls in OpenXDMOD as there is only one to choose
            // from. So we catch that use case here and provide the one center
            // for 'center' related acls.
            $currentCenters = Centers::getCenters();
            if (count($currentCenters) > 0) {
                $center = $currentCenters[0]['id'];
                $newuser->setOrganizations(
                    array(
                        $center => array('primary' => 1, 'active' => 1)
                    ),
                    $acl
                );
            }
        }
    }
    // =============================

    if (isset($_POST['institution']) && $_POST['institution'] != -1) {
        $newuser->setInstitution($_POST['institution']);
    }

    // =============================

    $page_title = \xd_utilities\getConfiguration('general', 'title');
    $site_address = \xd_utilities\getConfigurationUrlBase('general', 'site_address');

    // -------------------

    $message = "Welcome to the $page_title.  Your account has been created.\n\n";
    $message .= "Your username is: ".$_POST['username']."\n\n";
    $message .= "Please visit the following page to create your password:\n\n";
    $message .= "${site_address}password_reset.php?mode=new&rid=".md5($newuser->getUsername().$newuser->getPasswordLastUpdatedTimestamp())."\n\n";
    $message .= "Once you have created a password, you will be directed to $site_address where you can then log in using your credentials.\n\n";

    $message .= "For assistance on using the portal, please consult the User Manual:\n";
    $message .= $site_address."user_manual\n\n";
    $message .= "The XDMoD Team";

    MailWrapper::sendMail(array(
        'body'      => $message,
        'subject'   => "$page_title: Account Created",
        'toAddress' => $_POST['email_address']
        )
    );
}
catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}

// -----------------------------

if (isset($_REQUEST['account_request_id']) && !empty($_REQUEST['account_request_id'])) {
    $xda = new XDAdmin();
    $xda->updateAccountRequestStatus($_REQUEST['account_request_id'], $creator->getUsername());
}

$returnData['success'] = true;
$returnData['user_type'] = $_POST['user_type'];
$returnData['message'] = 'User <b>'.$_POST['username'].'</b> created successfully';

\xd_controller\returnJSON($returnData);
