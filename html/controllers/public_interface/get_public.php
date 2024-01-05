<?php
require_once __DIR__.'/../../configuration/linker.php';

$returnData = [];

try
{
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d',  mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

    if(isset($_GET['start_date'])){
        $start_date = $_GET['start_date'];
    }

    if(isset($_GET['end_date'])){
        $end_date = $_GET['end_date'];
    }

    $aggregation_unit = 'auto';
    $availableAggregationUnits = array_keys(\DataWarehouse\Query\TimeAggregationUnit::getRegsiteredAggregationUnits());
    if(isset($_GET['aggregation_unit'])){
        $key = array_search($_GET['aggregation_unit'], $availableAggregationUnits);
        if(!is_null($key)){
            $aggregation_unit = $availableAggregationUnits[$key];
        }
    }

    $query = new \DataWarehouse\Query\AggregateQuery(
        'Jobs',
        $aggregation_unit,
        $start_date,
        $end_date,
        'none',
        'all'
    );
    $results = $query->execute();

    $returnData =  ['totalCount' => 1, 'message' =>'', 'data' => [$results], 'success' => true];

}
catch(Exception $ex) {
    \xd_response\presentError($ex->getMessage());
}

\xd_controller\returnJSON($returnData);
