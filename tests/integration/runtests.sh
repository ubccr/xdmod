#!/bin/sh
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $BASEDIR/../ci/runtest-include.sh
set -e

echo "Integration tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`

cd $(dirname $0)
phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

# Run the tests in UserAdminTest.createUsers
$phpunit --testsuite default --group UserAdminTest.createUsers $(log_opts "integration" "UserAdminTest.createUsers")

# Run everything else
$phpunit --testsuite default --exclude-group UserAdminTest.createUsers $(log_opts "integration" "All")

./email-subject-test.sh
