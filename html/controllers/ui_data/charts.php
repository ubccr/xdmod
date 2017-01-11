<?php
    @session_start();

    @require_once dirname(__FILE__).'/../../../configuration/linker.php';
    
if (!isset($_SESSION['xdUser'])) {
    echo  json_encode(array(
            'success' => false,
          'message' => 'Session Expired'));
    exit;
}
    
    $logged_in_user = \xd_security\getLoggedInUser();
    
    $person_id = $logged_in_user->getPersonID();
    
/*
	if(isset($_REQUEST['node']) and $_REQUEST['node'] == 'layouts')
	{
	
		$layoutsQuery = "
			SELECT * FROM  moddb.Layouts l
		";
		
		$layouts = DataWarehouse::connect()->query($layoutsQuery);
		
		$ret = array();
		
		$ret[] = array('text' => 'My Summary', 'id' => "my_summary", "iconCls" => 'my_summary');
		$ret[] = array('text' => 'XD Summary', 'id' => "tg_summary", "iconCls" => 'tg_summary');
		
		foreach ($layouts as $layout)
		{
			$ret[] = array('text' => $layout['description'], 
			'id' => "layout_{$layout['id']}", 
			"iconCls" => "layout_{$layout['id']}",
			'expanded' => true);
		}
		
		$ret[] = array('text' => 'Allocations', 'id' => "my_allocations", "iconCls" => 'my_allocations', 'filter' => false);
		$ret[] = array('text' => 'App Kernels', 'id' => "app_kernels", "iconCls" => 'app_kernels', 'filter' => false);
		
		echo json_encode($ret);
	}
	else*/

if (isset($_REQUEST['node']) and substr($_REQUEST['node'], 0, 12) == 'node_layout_') {
    $layout_id = intval(substr($_REQUEST['node'], 12));
    
    $menusQuery = "
			SELECT m.*, concat(l.description, ' ', m.name) as full_name FROM moddb.LayoutHasMenus lhm, moddb.Layouts l, moddb.Menus m 
			Where lhm.layout_id = l.id
			and m.id = lhm.menu_id
			and l.id = $layout_id
			order by lhm.`order`
		";
        
    $menus = DataWarehouse::connect()->query($menusQuery);
        
    $ret = array();
    foreach ($menus as $menu) {
        $ret[] = array('text' => $menu['name'],//.' '.$menu['id'],
                    'id' => "menu_{$menu['id']}",
                    "menu_id" => $menu['id'],"iconCls" => 'menu',
                    'description' => $menu['full_name'], 'filter' => false);
    }
        
    echo json_encode($ret);
} elseif (isset($_REQUEST['node']) and substr($_REQUEST['node'], 0, 5) == 'menu_') {
        $menu_id =  intval(substr($_REQUEST['node'], 5));
        
        $chartsQuery = "
			select mhc.chart_id, c.`type`, c.description, c.title, coalesce(ccom.comments,'') as comments
			from moddb.MenuHasCharts mhc, moddb.ChartHasConfig chc, moddb.ChartConfigs cc, moddb.Charts c left outer join moddb.ChartComments ccom on ccom.chart_id = c.id
			where mhc.menu_id = $menu_id
			and chc.chart_id = mhc.chart_id
			and chc.chart_config_id = cc.id
			and chc.visible = 1
			and c.id = mhc.chart_id
			order by `order`
			"
        ;
        $charts = DataWarehouse::connect()->query($chartsQuery);
        
        $ret = array();
    foreach ($charts as $chart) {
        $ret[] = array('text' => $chart['description'],//.' '.$chart['chart_id'],
                   'id' => "chart_{$chart['chart_id']}",
                   'iconCls' => "chart",
                   'leaf' => true,
                   'type' => $chart['type'],
                   'filter' => true,
                   'render_mode' => 'data');
    }
        
        echo json_encode($ret);
}
