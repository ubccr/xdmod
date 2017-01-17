<?php

   namespace xd_debug;

   // --------------------------------

function breakpoint($label)
{

    $returnData = array('breakpoint' => $label);
    \xd_controller\returnJSON($returnData);
}//breakpoint

   // --------------------------------
      
function dumpArray(&$arr)
{
   
    print '<pre>'.print_r($arr, 1).'</pre>';
}//dumpArray

   // --------------------------------
      
function dumpQueryResultsAsTable(&$arr)
{
   
    print '<table border=1 cellpadding=10>';
      
    print '<tr><td>'.implode('</td><td>', array_keys($arr[0])).'</td></tr>';
      
    foreach ($arr as $entry) {
        print '<tr><td>';
        print implode('</td><td>', $entry);
        print '</td></tr>';
    }
      
    print '</table>';
}//dumpQueryResultsAsTable
