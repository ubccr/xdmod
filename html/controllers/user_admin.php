<?php
/**
 * operation: params -----
 *
 *     list_users: group (valid values: 'house', 'imported')
 *     get_user_details: uid
 *     create_user: username, first_name, last_name, email_address, roles, primary_role, assignment
 *     update_user: uid + at least one of the following: first_name, last_name, email_address, roles, primary_role, assigned_user, is_active
 *     delete_user: uid
 *     pass_reset: uid
 */

@session_start();
session_write_close();

require_once __DIR__ . '/../../configuration/linker.php';

$returnData = array();

$controller = new XDController(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE));

$controller->registerOperation('enum_roles');
$controller->registerOperation('list_users');
$controller->registerOperation('get_user_details');
$controller->registerOperation('create_user');
$controller->registerOperation('update_user');
$controller->registerOperation('delete_user');
$controller->registerOperation('pass_reset');
$controller->registerOperation('enum_resource_providers');
$controller->registerOperation('enum_institutions');
$controller->registerOperation('enum_user_types');
$controller->registerOperation('enum_exception_email_addresses');
$controller->registerOperation('empty_report_image_cache');
$controller->registerOperation('search_users');

$controller->invoke('POST', 'xdDashboardUser');

