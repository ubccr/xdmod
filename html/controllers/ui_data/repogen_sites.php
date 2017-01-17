<?php

    require_once dirname(__FILE__).'/../../../configuration/linker.php';
        
    $db_host =     xd_utilities\getConfiguration('database', 'host');
    $db_user =     xd_utilities\getConfiguration('database', 'user');
    $db_pass =     xd_utilities\getConfiguration('database', 'pass');
                
    mysql_connect($db_host, $db_user, $db_pass);
    
    $res = mysql_query('SELECT organization_abbrev, organization_id FROM moddb.TGOrganizations WHERE organization_id IN (SELECT DISTINCT t.organization_id FROM moddb.TGResources AS t, moddb.ResourceInformation AS i WHERE i.resource_id = t.resource_id ORDER BY t.resource_code ASC) ORDER BY organization_abbrev ASC');


    $p = array();

while (list($abbrev, $org_id) = mysql_fetch_array($res)) {
    $r = mysql_query("SELECT resource_code FROM moddb.TGResources WHERE organization_id = $org_id");
    
    $e = array();
        
    while (list($resource) = mysql_fetch_array($r)) {
        $e[] = array('text' => $resource, 'leaf' => true, 'checked' => true, 'iconCls' => 'site_child');
    }
        
    $p[] = array('text' => $abbrev, 'checked' => true, 'iconCls' => 'site_parent', 'children' => $e, 'expanded' => false);
}

    echo json_encode($p);
