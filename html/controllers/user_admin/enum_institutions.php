<?php

// Operation: user_admin->enum_institutions

\xd_security\assertParametersSet(array(
      'start' => RESTRICTION_NUMERIC_POS,
      'limit' => RESTRICTION_NUMERIC_POS
   ));

// -----------------------------

$xda = new XDAdmin();
            
$name_filter = (isset($_POST['query'])) ? $_POST['query'] : null;

    list($institutionCount, $institutions) = $xda->enumerateInstitutions($_POST['start'], $_POST['limit'], $name_filter);
            
   $institutionEntries = array();
   
foreach ($institutions as $institution) {
    $institutionEntries[] = array(
                    'id' => $institution['id'],
                    'name' => $institution['name']
                 );
}//foreach

    // -----------------------------

    $returnData['status'] = 'success';
    $returnData['success'] = true;
    $returnData['total_institution_count'] = $institutionCount;
    
    $returnData['institutions'] = $institutionEntries;
            
    \xd_controller\returnJSON($returnData);
