<?php
require_once('common.php');

$returnData = [];

try
{

   $user = \xd_security\getLoggedInUser();
   
	$userProfile = $user->getProfile();
	$filters = $userProfile->fetchValue('filters');
	if($filters!= NULL) 
	{
		 $filtersArray = json_decode($filters);
		 $returnData = ['totalCount' => count($filtersArray), 'message' => 'success', 'data' => $filtersArray, 'success' => true];
	}
	else 
	{
		$returnData = ['totalCount' => 0, 'message' => 'success', 'data' => [], 'success' => true];
	}
	
}
catch(SessionExpiredException $see)
{
	// TODO: Refactor generic catch block below to handle specific exceptions,
	//       which would allow this block to be removed.
	throw $see;
}
catch(Exception $ex)
{
	$returnData = ['totalCount' => 0, 'message' => $ex->getMessage(), 'data' => [], 'success' => false];
}

xd_controller\returnJSON($returnData);
