<?php

// Operation: sab_user->assign_assumed_person

$params = array('person_id' => RESTRICTION_ASSIGNMENT);

$isValid = xd_security\secureCheck($params, 'POST');

if (!$isValid) {
    $returnData = array(
        'success' =>  false,
        'status'  => 'invalid_id_specified',
        'message' => 'invalid_id_specified',
    );
    xd_controller\returnJSON($returnData);
};

$xdw = new XDWarehouse();

if ($xdw->resolveName($_POST['person_id']) == NO_MAPPING) {
    $returnData = array(
        'success' =>  false,
        'status'  => 'no_person_mapping',
        'message' => 'no_person_mapping',
    );
    xd_controller\returnJSON($returnData);
}

$_SESSION['assumed_person_id'] = $_POST['person_id'];

$returnData = array(
    'success' =>  true,
    'status'  => 'success',
    'message' => 'success',
);

xd_controller\returnJSON($returnData);
