<?php

$data = file_get_contents('tmp.log');

$output = array();
foreach (explode('&', $data) as $line) {
    list($a, $b) = explode('=', $line);
    $output[$a] = $b;
}

echo json_encode($output, JSON_PRETTY_PRINT);
