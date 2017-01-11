<?php

try {
    $user = \xd_security\getLoggedInUser();

    $rm = new XDReportManager($user);
      
   // ----------------------------------
   
    \xd_security\assertParameterSet('selected_report');
    
    $flush_cache = isset($_POST['flush_cache']) ? $_POST['flush_cache'] : false;
      
    if ($flush_cache == true) {
        $rm->flushReportImageCache();
    }
      
    $data = $rm->loadReportData($_POST['selected_report']);
   
   // ----------------------------------
        
    if (isset($_POST['based_on_other']) && $_POST['based_on_other'] == 'true') {
        // The report to be retrieved is to be the basis for a new report.
        // In this case, overwrite the report_id and report name fields so when it comes time to save this
        // report, a new report will be created instead of the original being overwritten / updated.
         
        $data['report_id'] = '';
        $data['general']['name'] = $rm->generateUniqueName($data['general']['name']);
    } else {
        $data['report_id'] = $_POST['selected_report'];
    }
      
   // ----------------------------------
      
    $returnData['action'] = 'fetch_report_data';
    $returnData['success'] = true;
    $returnData['results'] = $data;
   
    \xd_controller\returnJSON($returnData);
} catch (SessionExpiredException $see) {
   // TODO: Refactor generic catch block below to handle specific exceptions,
   //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}
