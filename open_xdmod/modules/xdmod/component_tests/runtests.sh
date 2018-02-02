#!/bin/sh

PHPUNITARGS="$@"

cd $(dirname $0)
phpunit="$(readlink -f ../../../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

./artifacts/update-artifacts.sh

# This test suite runs everything in lib/Roles
$phpunit ${PHPUNITARGS} --testsuite=roles -v

# This test suite will be everything *but* lib/Roles ( which includes
# new files / directories that may be added in the future ). We've split them out
# as XDUserTest dynamically generates new users. Some of which will be center staff,
# which messes with the tests in lib/Roles. Hence the splitting into two test
# suites.
$phpunit ${PHPUNITARGS} --testsuite=non-roles -v
