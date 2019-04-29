#!/usr/bin/env php
<?php

// Define a service provider organization ID that users will be associated with. Note that
// this needs to match the id used in any tests or the tests will fail. We are using 1 for
// Open XDMoD and 476 (TACC) for XSEDE XDMoD.

define('SERVICE_PROVIDER_ID', 1);
define('DEFAULT_SECRETS_FILE', '/root/assets/secrets.json');
define('DEFAULT_XDMOD_PATH', '/usr/share/xdmod');

$scriptOptions = array(
    'user-id' => null,
    'secrets-file' => DEFAULT_SECRETS_FILE,
    'service-provider-id' => SERVICE_PROVIDER_ID,
    'create-manager-users' => false,
    'xdmod-install-path' => DEFAULT_XDMOD_PATH
);

$options = array(
    'd:'  => 'delete-user-id',
    'h'   => 'help',
    'm'   => 'create-manager-users',
    'o:'  => 'service-provider-org:',
    's:'  => 'secrets-file:',
    'x:'  => 'xdmod-install-path:'
);

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
    switch ($arg) {

        case 'd':
        case 'delete-user-id':
            // Merge array because long and short options are grouped separately
            $scriptOptions['user-id'] = filter_var($value, FILTER_VALIDATE_INT, array('flags' => FILTER_NULL_ON_FAILURE));
            if ( null === $scriptOptions['user-id'] ) {
                usageAndExit("Invalid user id: '$value'");
            }
            break;

        case 'm':
        case 'create-manager-users':
            $scriptOptions['create-manager-users'] = true;
            break;

        case 'o':
        case 'service-provider-org':
            $scriptOptions['service-provider-id'] = filter_var($value, FILTER_VALIDATE_INT, array('flags' => FILTER_NULL_ON_FAILURE));
            if ( null === $scriptOptions['service-provider-id'] ) {
                usageAndExit("Invalid organization id: '$value'");
            }
            break;

        case 's':
        case 'secrets-file':
            $scriptOptions['secrets-file'] = $value;
            break;

        case 'x':
        case 'xdmod-install-path':
            $scriptOptions['xdmod-install-path'] = $value;
            break;

        case 'h':
        case 'help':
            usageAndExit();
            break;
    }
}

require_once($scriptOptions['xdmod-install-path'] . '/configuration/linker.php');

if ( null !== $scriptOptions['user-id'] ) {
    deleteUser($scriptOptions);
} else {
    createUsers($scriptOptions);
}

exit(0);

function createUsers(array $options)
{

    $userlist = json_decode(file_get_contents($options['secrets-file']), true);

    $xdw = new XDWarehouse();

    foreach ($userlist['role'] as $rolename => $credentials) {

        // Note that for Open XDMoD, the setup script creates the manager (mgr) user but for
        // XSEDE XDMoD we need to create it manually.

        if ( $rolename == 'mgr' && ! $options['create-manager-users'] ) {
            continue;
        }

        $searchCriteria = array(
            'last_name' => $credentials['surname'],
            'first_name' => $credentials['givenname']
        );

        $users = $xdw->searchUsers($searchCriteria);

        if ( 0 == count($users) ) {
            print sprintf("Could not find user '%s %s'", $credentials['givenname'], $credentials['surname']) . PHP_EOL;
            continue;
        } else {
            print sprintf("Creating user: %s", $credentials['username']) . PHP_EOL;
        }
        $defaultOrganization = 1;
        if (isset($options['service-provider-id'])) {
            $defaultOrganization = $options['service-provider-id'];
        }
        try {
            $newUser = new XDUser(
                $credentials['username'],
                $credentials['password'],
                $credentials['username'] . '@example.com',
                $credentials['givenname'],
                '',
                $credentials['surname'],
                array_unique(array($rolename, 'usr')),
                $rolename,
                $defaultOrganization,
                $users[0]['person_id']
            );

            $newUser->setUserType(1);
            $newUser->saveUser();
        } catch (Exception $e) {
            print sprintf("Error creating user '%s': %s", $credentials['username'], $e->getMessage()) . PHP_EOL;
            continue;
        }

        if ($rolename == 'cd') {
            $newUser->setOrganizations(array($options['service-provider-id'] => array('active' => true, 'primary' => true)), ROLE_ID_CENTER_DIRECTOR);
        }
        if ($rolename == 'cs') {
            $newUser->setOrganizations(array($options['service-provider-id']=> array('active' => true, 'primary' => true)), ROLE_ID_CENTER_STAFF);
        }
    }
}

function deleteUser(array $options)
{
    $user = XDUser::getUserByID($options['user-id']);

    if ( null == $user ) {
        print sprintf("Could not find user with id '%d'", $options['user-id']) . PHP_EOL;
        return false;
    } else {
        print sprintf("Deleting user: %s (%d)", $user->getUsername(), $options['user-id']) . PHP_EOL;
    }

    $user->removeUser();
    return true;
}

function usageAndExit($msg = null)
{
    global $argv;

    if ($msg !== null) {
        fwrite(STDERR, "\n$msg\n\n");
    }

    $defaultServiceProviderId = SERVICE_PROVIDER_ID;
    $defaultInstallPath = DEFAULT_XDMOD_PATH;
    $defaultSecretsFile = DEFAULT_SECRETS_FILE;

    fwrite(
        STDERR,
        <<<"EOMSG"
Usage: {$argv[0]}
    -h, --help
    Display this help

    -d, --delete-user-id <user_id>
    Rather than creating users, delete the XDMoD user with the specified id.

    -m, --create-manager-users
    Create users that are assigned the 'mgr' role. This is not necessary when running the xdmod-setup script.

    -o, --service-provider-org <organization_id>
    The identifier to use for the service provider assigned to center director and staff users. (default: $defaultServiceProviderId)

    -s, --secrets-file <file>
    The secrets file to use when creating users. (default: $defaultSecretsFile)

    -x, --xdmod-install-path
    The XDMoD install path. (default: $defaultInstallPath)

EOMSG
    );
    exit(1);
}
