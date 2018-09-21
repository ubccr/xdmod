<?php

// Operation: user_admin->enum_institutions

$xda = new XDAdmin();

$name_filter = (isset($_POST['query'])) ? $_POST['query'] : NULL;

$query = $xda->enumerateInstitutions($name_filter);

// If there were records found based on the free-form `query` parameter -> organization name then go
// ahead and return them. If not, then by default retrieve / return the full list of organizations.
if (count($query) > 0) {
    $institutions = $query;
} else {
    $institutions = $xda->enumerateInstitutions(null);
}

$returnData['status'] = 'success';
$returnData['success'] = true;
$returnData['total_institution_count'] = count($institutions);

$returnData['institutions'] = $institutions;

\xd_controller\returnJSON($returnData);
