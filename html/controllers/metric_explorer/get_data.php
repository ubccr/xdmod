<?php

/* 
 * html/controllers/metric_explorer/get_data.php
 *
 * Glue code to call MetricExplorer::get_data(). All exceptions go to the
 * global exception handler.
 */

// 5 minute exec time max
ini_set('max_execution_time', 300);

$user = \xd_security\detectUser(
    array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER)
);

$m = new \DataWarehouse\Access\MetricExplorer($_REQUEST);

$result = $m->get_data($user);

foreach ($result['headers'] as $k => $v) {
    header($k . ": " . $v);
}

echo $result['results'];
