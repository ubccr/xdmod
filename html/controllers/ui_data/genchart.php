<?php

	@session_start();

	@require_once dirname(__FILE__).'/../../../configuration/linker.php';
	
	try
	{
	
		$logged_in_user = \xd_security\getLoggedInUser();
	
		$chart_parameters = \DataWarehouse\Visualization::parseChartParameters($_GET, $logged_in_user);
		
		
		if(isset($chart_parameters['chart_type']))
		{
			$chart_type = $chart_parameters['chart_type'];
			
			$data_only = ($chart_parameters['data_only'] == true);// && isset($chart_parameters['format'])) ? ($chart_parameters['format']=='json'?true:0): 0;
			$c = \DataWarehouse\Visualization::getChartFromParameters($chart_parameters);
			//print_r($chart_parameters);

			if($data_only)
			{
				$format = isset($chart_parameters['format']) ? $chart_parameters['format']: 'csv';
				
				DataExporter::exportHeader($format, $chart_type.$c['start_date'].'to'.$c['end_date']);
				
				DataExporter::export($format, $c['title'], 'From: '.$c['start_date'].' To: '.$c['end_date'], array($chart_type => $c['chart_data']), isset($c['chart_png'])?$c['chart_png']:'');
			}
			else
			{
				$user = $logged_in_user;
				
				$chart_pool = new XDChartPool($user);
		
				$chart_query = substr($_SERVER["QUERY_STRING"], strpos($_SERVER["QUERY_STRING"], 'chart_type='));
				
				$proc_bucket_pattern = '/processor_bucket=(\d+)%20-%20(\d+)/';
				$proc_bucket_replacement = 'min_processors=$1&max_processors=$2';
				
				$chart_query = preg_replace($proc_bucket_pattern, $proc_bucket_replacement, $chart_query);
				
				
				// Return the argument string passed into this call so that if the user decides to include this particular
				// chart in their queue (chart pool), the argument can be easily 'cached' for later use.
				
            list($uri_path, $uri_args) = explode('?', $_SERVER['REQUEST_URI']);
               
            $uri_args = preg_replace('/_dc=(.+?)&/', '', $uri_args);
            $uri_args = preg_replace('/scale=(.+?)&/', '', $uri_args);
               
            $c['chart_args'] = $uri_args;

				$c['included_in_report'] = $chart_pool->chartExistsInQueue($uri_args) ? 'y' : 'n';
			
			
				unset($c['chart_data']); //no need to return the data since the data_only flag does that
				
				echo  json_encode(array('totalCount' => 1, 'success' => true, 
										'message' => 'success', 'charts' => $c));
			}
			
		}
		else if(isset($chart_parameters['menu_id']))
		{
			$menu_id = $chart_parameters['menu_id'];
			$query = "select 
							c.`type` 
					  from moddb.Charts c, 
						   moddb.MenuHasCharts mhc, 
						   moddb.ChartHasConfig chc 
					  where chc.chart_id = c.id 
						and chc.visible = 1 
						and c.id = mhc.chart_id 
						and mhc.menu_id = $menu_id
						order by mhc.`order`";
			
			$retCharts = array();
			
			$chart_parameters['data_only'] = false;
			
			$chart_types = DataWarehouse::connect()->query($query);
			
			foreach ($chart_types as $chart_type)
			{
				
				$chart_parameters['chart_type'] = $chart_type['type'];
				$c = \DataWarehouse\Visualization::getChartFromParameters($chart_parameters);
	
				$retCharts[] = $c;			
			}
				
			echo  json_encode(array('totalCount' => count($retCharts), 'success' => true, 
									'message' => 'success', 'charts' => $retCharts));
		}/*else  if(isset($_GET['summary_charts']))
		{
			$summary_charts = $_GET['summary_charts'];
			
			$query  = '';
			if($summary_charts = 'my_summary')
			{
				$query = "select 
								c.`type`, c.title
						  from Charts c, 
							   MenuHasCharts mhc, 
							   ChartHasConfig chc 
						  where chc.chart_id = c.id 
							and c.id = mhc.chart_id 
							and c.`type` in ('UsageSummariesByOrganizationTotalJobs', 
										   'UsageSummariesByOrganizationTotalJobSize', 
										   'UsageSummariesByOrganizationTotalAvgJobSize',
										   'UsageSummariesByOrganizationTotalSU')
							order by mhc.`order`";
			}else //tg_summary
			{
				$query = "select 
								c.`type`, c.title
						  from moddb.Charts c, 
							   moddb.MenuHasCharts mhc, 
							   moddb.ChartHasConfig chc 
						  where chc.chart_id = c.id 
							and c.id = mhc.chart_id 
							and c.`type` in ('UsageSummariesByOrganizationTotalJobs', 
										   'UsageSummariesByOrganizationTotalJobSize', 
										   'UsageSummariesByOrganizationTotalAvgJobSize',
										   'UsageSummariesByOrganizationTotalSU')
							order by mhc.`order`";
			}
			
			$chart_types =DataWarehouse::connect()->query($query);
			
				
			echo  json_encode(array('totalCount' => count($chart_types), 'success' => true,
									 'message' => 'success', 'charts' => $chart_types));
		}*/
	}
	catch(SessionExpiredException $see) 
	{
		// TODO: Refactor generic catch block below to handle specific exceptions,
		//       which would allow this block to be removed.
		throw $see;
	}
	catch(Exception $ex)
	{
		echo  json_encode(
		array('totalCount' => 0, 
			  'message' => $ex->getMessage(), 
			  'charts' => array(),
			  'success' => false));
	}
?>


