#!/bin/bash
export LANG=C.UTF-8
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REPODIR=`realpath $BASEDIR/../../`
source $BASEDIR/../ci/runtest-include.sh
set -e

echo "Post Integration tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`

cd $(dirname $0)
phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

# Run module specific tests
$phpunit ${PHPUNITARGS} .

# Run manual tests

exitcode=0

# Check that the xdmod-admin command is present and can be used to add users
if !(xdmod-admin --users --load $REPODIR/tests/artifacts/xdmod/validate/sso_users.csv --force)
then
    echo "xdmod-admin load users failed"
    exitcode=1
fi

# Check the new user was added
ssousercount=$(echo 'SELECT count(*) FROM Users u LEFT JOIN UserTypes ut ON ut.id = u.user_type WHERE username = '"'"'ndent'"'"' AND first_name='"'"'Nörbert'"'"' AND ut.type = '"'"'Single Sign On'"'" | mysql -N moddb)

if [[ "${ssousercount}" -ne 1 ]]
then
    echo "xdmod-admin load user: error user account did not get added."
    exitcode=1
fi

# Check the user as usr and cd role
rolecount=$(echo 'select COUNT(*) from Users u LEFT JOIN user_acls ua ON ua.user_id = u.id LEFT JOIN acls a ON a.acl_id = ua.acl_id WHERE u.username = '"'"'ndent'"'"' AND a.name IN ('"'"'usr'"'"', '"'"'cd'"')" | mysql -N moddb)

if [[ "${rolecount}" -ne 2 ]]
then
    echo "xdmod-admin load user: error user account did not get correct acls."
    exitcode=1
fi

exit $exitcode
