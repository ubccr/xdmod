<?php

// Operation: user_admin->enum_user_types

$xda = new XDAdmin();

$userTypes = $xda->enumerateUserTypes();

$userTypeEntries = [];

foreach ($userTypes as $type) {
    $userTypeEntries[] = ['id'   => $type['id'], 'type' => $type['type']];
}

$returnData['success']    = true;
$returnData['status']     = 'success';
$returnData['user_types'] = $userTypeEntries;

\xd_controller\returnJSON($returnData);

