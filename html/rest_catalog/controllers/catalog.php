<?php

   // XDMoD REST API Catalog (controller)
   // Author: Ryan Gentner <rgentner@ccr.buffalo.edu>
   // Last Updated: Monday, February 7, 2011
   
   require_once dirname(__FILE__).'/../../../configuration/linker.php';
         
   $json = array();

if (isset($_POST['node']) && strpos($_POST['node'], 'category_') !== false) {
   // Node ID will be in the form: 'flag_realm_category'
      
    list($flag, $realm, $category) = explode('_', $_POST['node']);
               
    $actions = xd_rest\enumerateActions($realm, $category);
               
    foreach ($actions as $current_action) {
        $actionNode = new \ExtJS\TreeNode($current_action, true);
         
        $actionNode->setID($realm.'_'.$category.'_'.$current_action);
        $actionNode->setIconClass('iconAction');
                                          
        $json[] = $actionNode->getData();
    }//foreach
} else {
   // Generate JSON data for first and second level nodes of tree

    $realms = xd_rest\enumerateRealms();
            
    foreach ($realms as $realm) {
        $categories = xd_rest\enumerateCategories($realm);
   
        $totalActionCount = 0;

        // -------------------------------------

        foreach ($categories as $category) {
            $actions = xd_rest\enumerateActions($realm, $category);
            
            $totalActionCount += count($actions);
        }//foreach
   
        // -------------------------------------
   
        if ($totalActionCount > 0) {
            // This realm consists of categories which (in total) contain at least one (visible) action
            
            $realmNode = new \ExtJS\TreeNode($realm);
            $realmNode->setIconClass('iconRealm');

            if (count($categories) == 0) {
                $realmNode->setAsLeaf(true);
            }
            
            foreach ($categories as $category) {
                $categoryNode = new \ExtJS\TreeNode($category);
                $categoryNode->setID('category_'.$realm.'_'.$category);
                $categoryNode->setIconClass('iconCategory');
                     
                $realmNode->addChildNode($categoryNode);
            }//foreach
               
            $json[] = $realmNode->getData();
        }//if ($totalActionCount > 0)
    }//foreach
}
            
   echo json_encode($json);
