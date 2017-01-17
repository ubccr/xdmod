<?php

// Operation: chart_pool->remove_from_queue
    
\xd_security\assertParametersSet(array(
        'chart_id' => RESTRICTION_CHART_TYPE,
        'module'   => RESTRICTION_CHART_MODULE
   ));
   
    // -----------------------------

try {
       $user = \xd_security\getLoggedInUser();
        
       $chart_pool = new XDChartPool($user);

       $chart_title = (!empty($_POST['chart_title'])) ? $_POST['chart_title'] : 'Untitled Chart';

      //$_POST['chart_id'] = 'controller_module='.$_POST['module'].'&'.$_POST['chart_id'];

       $_POST['chart_id'] = str_replace("title=".$chart_title, "title=".urlencode($chart_title), $_POST['chart_id']);
    
       $chart_pool->removeChartFromQueue($_POST['chart_id']);
       $returnData['success'] = true;
       $returnData['action'] = 'remove';
} catch (SessionExpiredException $see) {
        // TODO: Refactor generic catch block below to handle specific exceptions,
        //       which would allow this block to be removed.
        throw $see;
} catch (Exception $e) {
        \xd_response\presentError($e->getMessage());
}
    
\xd_controller\returnJSON($returnData);
