<?php
/**
 * Controller helper functions.
 */

namespace xd_controller;

/**
 * Output an array as JSON.
 *
 * @param array $data
 */
function returnJSON($data = array())
{
    if (isset($_SERVER['SERVER_PROTOCOL'])) {
        header('Content-Type: application/json');
    }
    echo json_encode($data);

    exit;
}
