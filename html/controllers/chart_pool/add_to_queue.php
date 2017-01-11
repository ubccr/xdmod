<?php

// Operation: chart_pool->add_to_queue

\xd_security\assertParametersSet(array(
        'chart_id'             => RESTRICTION_CHART_TYPE,
         //'chart_title'       => RESTRICTION_CHART_TITLE,
      'chart_drill_details'  => RESTRICTION_CHART_DETAILS,
        'chart_date_desc'      => RESTRICTION_CHART_DATE_DESC,
        'module'               => RESTRICTION_CHART_MODULE
));
   
   // -----------------------------

try {
       $user = \xd_security\getLoggedInUser();
        
       $chart_pool = new XDChartPool($user);

       $chart_title = (!empty($_POST['chart_title'])) ? $_POST['chart_title'] : 'Untitled Chart';
   
      //$_POST['chart_id'] = 'controller_module='.$_POST['module'].'&'.$_POST['chart_id'];
   
       $chart_pool->addChartToQueue($_POST['chart_id'], $chart_title, $_POST['chart_drill_details'], $_POST['chart_date_desc']);
       $returnData['success'] = true;
       $returnData['action'] = 'add';
} catch (SessionExpiredException $see) {
  // TODO: Refactor generic catch block below to handle specific exceptions,
  //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}
    
    \xd_controller\returnJSON($returnData);
