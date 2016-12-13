<?php

// Operation: sab_user->get_mapping

$params = array(
    'use_default' => RESTRICTION_YES_NO
);

$isValid = xd_security\secureCheck($params, 'POST');

if (!$isValid) {
    $returnData = array(
        'success' =>  false,
        'status'  => 'invalid_params_specified',
        'message' => 'invalid_params_specified',
    );
    xd_controller\returnJSON($returnData);
};

$logged_in_user = \xd_security\getLoggedInUser();

$mapped_person_id = $logged_in_user->getPersonID($_POST['use_default'] == 'y');

$xdw = new XDWarehouse();
$mapped_person_name = $xdw->resolveName($mapped_person_id);

$returnData = array(
    'success'            =>  true,
    'status'             => 'success',
    'message'            => 'success',
    'mapped_person_id'   => $mapped_person_id,
    'mapped_person_name' => $mapped_person_name,
);

xd_controller\returnJSON($returnData);

