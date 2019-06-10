#!/usr/bin/env php
<?php

require_once '/usr/share/xdmod/configuration/linker.php';

$data = parse_ini_file('/etc/xdmod/portal_settings.ini', true);
$settings = array();
foreach ($data['database'] as $key => $value) {
    $settings['db_' . $key] = $value;
}

# Create Databases

$databases = array(
    'mod_shredder',
    'mod_hpcdb',
    'moddb',
    'modw',
    'modw_aggregates',
    'modw_filters',
    'mod_logger',
);

\OpenXdmod\Shared\DatabaseHelper::createDatabases(
    'root',
    '',
    $settings,
    $databases
);

# Create admin user

$user = new XDUser(
    'admin',
    'admin',
    'admin@example.com',
    'Admin',
    '',
    'User',
    array(ROLE_ID_MANAGER),
    ROLE_ID_MANAGER
);

// Internal user.
$user->setUserType(2);

$user->saveUser();
