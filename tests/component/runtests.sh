#!/bin/bash
echo "Component tests beginging:" `date +"%a %b %d %H:%M:%S.%3N %Y"`
# Implode an array using the specified separator

function implode_array {
    local IFS="$1";
    shift;
    echo "$*";
}

# Array of test groups to include based on testing environment. Note that only these groups will be
# included.
declare -a INCLUDE_ONLY_GROUPS
# Array of test groups to exclude based on testing environment
declare -a EXCLUDE_GROUPS

PHPUNITARGS="$@"

cd $(dirname $0)
phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

# Determine the test suite to run, defaulting to the Open XDMoD if not specified
TEST_ENV=${TEST_ENV:=xdmod}

# If we are testing the XSEDE version do not include Open XDMoD specific tests
case $TEST_ENV in
    xdmod)
    EXCLUDE_GROUPS+=(XDMoD-XSEDE)
    ;;
    xdmod-xsede)
    EXCLUDE_GROUPS+=(XDMoD-shredder)
    ;;
    xdmod-supremm)
    ;;
    *)
    ;;
esac

echo "Test environment: $TEST_ENV"

EXCLUDE_GROUP_OPTION=
if [ 0 -ne ${#EXCLUDE_GROUPS[@]} ]; then
    echo "Exclude test groups: "$(implode_array , ${EXCLUDE_GROUPS[@]})
    EXCLUDE_GROUP_OPTION="--exclude-group "$(implode_array , ${EXCLUDE_GROUPS[@]})
fi

INCLUDE_GROUP_OPTION=
if [ 0 -ne ${#INCLUDE_GROUPS[@]} ]; then
    echo "Include test groups: "$(implode_array , ${INCLUDE_ONLY_GROUPS[@]})
    INCLUDE_GROUP_OPTION="--group "$(implode_array , ${INCLUDE_ONLY_GROUPS[@]})
fi

$phpunit ${PHPUNITARGS} --testsuite=Export -v $EXCLUDE_GROUP_OPTION $INCLUDE_GROUP_OPTION

# This test suite runs everything in lib/Roles
$phpunit ${PHPUNITARGS} --testsuite=Roles -v $EXCLUDE_GROUP_OPTION $INCLUDE_GROUP_OPTION

# This test suite will be everything *but* lib/Roles ( which includes
# new files / directories that may be added in the future ). We've split them out
# as XDUserTest dynamically generates new users. Some of which will be center staff,
# which messes with the tests in lib/Roles. Hence the splitting into two test
# suites.
$phpunit ${PHPUNITARGS} --testsuite=non-roles -v $EXCLUDE_GROUP_OPTION $INCLUDE_GROUP_OPTION
