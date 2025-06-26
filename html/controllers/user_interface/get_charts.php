<?php

use DataWarehouse\Access\Usage;
use Models\Services\Tokens;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

$logger = new \CCR\RequestLogger();

// Attempt authentication by API token.
try {
    $user = Tokens::authenticateController();
} catch (UnauthorizedHttpException $e) {
    // If token authentication failed then fall back to the standard
    // session-based authentication method.
    $user = \xd_security\detectUser(array(\XDUser::PUBLIC_USER));
}

$start = microtime(true);

// Send the request and user to the Usage-to-Metric Explorer adapter.
$usageAdapter = new Usage($_REQUEST);
$chartResponse = $usageAdapter->getCharts($user);

$end = microtime(true);

$logger->log($start, $end);

// Set the headers returned in the charts response.
foreach ($chartResponse['headers'] as $headerName => $headerValue) {
    header("$headerName: $headerValue");
}

// Print out the response content.
echo $chartResponse['results'];
