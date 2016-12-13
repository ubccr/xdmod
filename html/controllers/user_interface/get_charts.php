<?php

use DataWarehouse\Access\Usage;

// Get the user making the request.
$user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

// Send the request and user to the Usage-to-Metric Explorer adapter.
$usageAdapter = new Usage($_REQUEST);
$chartResponse = $usageAdapter->getCharts($user);

// Set the headers returned in the charts response.
foreach ($chartResponse['headers'] as $headerName => $headerValue) {
    header("$headerName: $headerValue");
}

// Print out the response content.
echo $chartResponse['results'];
