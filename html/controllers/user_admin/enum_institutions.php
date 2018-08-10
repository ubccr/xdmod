<?php

// Operation: user_admin->enum_institutions

$xda = new XDAdmin();

$name_filter = (isset($_POST['query'])) ? $_POST['query'] : NULL;

$institutions = $xda->enumerateInstitutions($name_filter);

$returnData['status'] = 'success';
$returnData['success'] = true;
$returnData['total_institution_count'] = count($institutions);

$returnData['institutions'] = $institutions;

\xd_controller\returnJSON($returnData);
