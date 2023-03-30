<?php

use DataWarehouse\Access\Usage;

// To enable API Token
$user = \Models\Services\Tokens::authenticateToken();

// If token authentication failed then fallback to the standard session based authentication method.
if ($user === null) {
    $user = \xd_security\detectUser(array(\XDUser::PUBLIC_USER));
}

// Send the request and user to the Usage-to-Metric Explorer adapter.
$usageAdapter = new Usage($_REQUEST);
$chartResponse = $usageAdapter->getCharts($user);

// Set the headers returned in the charts response.
foreach ($chartResponse['headers'] as $headerName => $headerValue) {
    header("$headerName: $headerValue");
}

// Print out the response content.
echo $chartResponse['results'];
